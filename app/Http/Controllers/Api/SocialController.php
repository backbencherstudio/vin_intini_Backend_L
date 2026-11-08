<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\FcmToken;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Auth\SessionIssuanceService;
use App\Services\ProfileImageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\User as SocialiteUser;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;

class SocialController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google', 'apple'];

    public function __construct(
        protected SessionIssuanceService $sessionService,
    ) {}

    public function redirect(string $provider): JsonResponse
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return response()->json(['success' => false, 'message' => 'Unsupported provider'], 422);
        }

        $platform = request('platform', 'app');
        $fcmToken = request('fcm_token');

        $stateKey = Str::random(40);
        $stateData = ['platform' => $platform, 'provider' => $provider];
        if ($fcmToken) {
            $stateData['fcm_token'] = $fcmToken;
        }

        // Store in cache for 10 minutes (Apple OAuth flow should complete within this time)
        Cache::put("socialite_state:{$stateKey}", $stateData, now()->addMinutes(10));

        $this->configureAppleClientId($platform);

        return response()->json([
            'success' => true,
            'url' => Socialite::driver($provider)
                ->stateless()
                ->with(['state' => $stateKey])
                ->redirect()
                ->getTargetUrl(),
        ]);
    }

    private function configureAppleClientId(string $platform): void
    {
        if ($platform !== 'ios') {
            return;
        }

        $iosClientId = config('services.apple.client_id_ios');
        if ($iosClientId) {
            config(['services.apple.client_id' => $iosClientId]);
        }
    }

    public function callback(string $provider, ProfileImageService $profileImageService): Response|JsonResponse|RedirectResponse
    {
        try {
            if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
                return response()->json(['success' => false, 'message' => 'Unsupported provider'], 422);
            }

            $stateKey = request('state');
            if (! $stateKey) {
                return response()->json(['success' => false, 'message' => 'Missing OAuth state parameter.'], 422);
            }

            $stateData = Cache::pull("socialite_state:{$stateKey}");
            if (! $stateData || ! is_array($stateData) || ($stateData['provider'] ?? null) !== $provider) {
                return response()->json(['success' => false, 'message' => 'Invalid or expired OAuth state.'], 422);
            }

            $platform = $stateData['platform'] ?? 'app';
            $fcmToken = $stateData['fcm_token'] ?? null;

            $this->configureAppleClientId($platform);

            $socialUser = Socialite::driver($provider)->stateless()->user();

            $response = $this->processSocialUser($socialUser, $provider, $platform, $profileImageService, $fcmToken);

            if ($platform === 'android' && $provider === 'apple') {
                return $this->buildAndroidIntentResponse($response);
            }

            return $response;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Social login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    private function buildAndroidIntentResponse(JsonResponse|RedirectResponse $response): Response
    {
        $data = $response instanceof JsonResponse ? $response->getData(true) : [];

        if (! ($data['success'] ?? false) || ! isset($data['data']['token'])) {
            return response()->view('auth.apple-callback-error', ['message' => 'Login failed']);
        }

        $token = $data['data']['token'];
        $user = $data['data']['user'];
        $isOnboarding = $data['data']['is_onboarding'] ?? false;

        $packageName = 'com.vinintini.mindunite';
        $scheme = 'signinwithapple';
        $intentUrl = "intent://callback?token={$token}&user_id={$user->id}&is_onboarding=".($isOnboarding ? 'true' : 'false')."#Intent;package={$packageName};scheme={$scheme};end";

        $html = <<<HTML
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="utf-8">
                    <title>Apple Sign In Callback</title>
                </head>
                <body>
                    <script>
                        const intentUrl = "{$intentUrl}";
                        window.location.href = intentUrl;
                    </script>
                    <noscript>
                        <p>Redirecting to app...</p>
                        <a href="{$intentUrl}">Click here if not redirected</a>
                    </noscript>
                </body>
                </html>
                HTML;

        return response($html, 200)->header('Content-Type', 'text/html');
    }

    public function socialLogin(Request $request, ProfileImageService $profileImageService)
    {
        $validated = $request->validate([
            'provider' => 'required|string|in:google,apple',
            'access_token' => 'required|string',
            'nonce' => 'nullable|string',
            'device_name' => 'nullable|string',
            'device_platform' => 'nullable|string',
            'fcm_token' => 'nullable|string',
        ]);

        try {
            $driver = Socialite::driver($validated['provider']);

            $nonce = $validated['nonce'] ?? null;
            $socialUser = $validated['provider'] === 'apple'
                ? ($nonce
                    ? $driver->userByIdentityToken($validated['access_token'], $nonce)
                    : $driver->userFromToken($validated['access_token']))
                : $driver->userFromToken($validated['access_token']);

            request()->merge([
                'custom_device' => $validated['device_name'] ?? null,
                'custom_platform' => $validated['device_platform'] ?? null,
            ]);

            $platform = $validated['device_platform'] ?? 'app';

            $this->configureAppleClientId($platform);

            return $this->processSocialUser($socialUser, $validated['provider'], $platform, $profileImageService, $validated['fcm_token'] ?? null);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Social login failed',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    private function processSocialUser(SocialiteUser $socialUser, string $provider, string $platform, ProfileImageService $profileImageService, ?string $fcmToken = null): JsonResponse|RedirectResponse
    {
        $providerId = (string) $socialUser->getId();
        $avatarUrl = (string) ($socialUser->getAvatar() ?: '');

        $user = DB::transaction(function () use ($provider, $providerId, $socialUser) {
            $socialAccount = SocialAccount::query()
                ->where('provider', $provider)
                ->where('provider_id', $providerId)
                ->first();

            if ($socialAccount) {
                $existingUser = User::withTrashed()->find($socialAccount->user_id);
                if ($existingUser) {
                    return $existingUser;
                }
            }

            $email = $socialUser->getEmail();
            $user = $email ? User::withTrashed()->where('email', $email)->first() : null;

            if (! $user) {
                $name = trim((string) $socialUser->getName());
                $parts = $name !== '' ? preg_split('/\s+/', $name) : [];

                $user = User::create([
                    'first_name' => $parts[0] ?? null,
                    'last_name' => isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : null,
                    'email' => $email ?: sprintf('%s_%s@noemail.com', $provider, $providerId),
                    'is_verified' => true,
                    'password' => Str::random(32),
                    'has_password' => false,
                    'terms_accepted_at' => now(),
                ]);
            }

            $role = Role::where('name', 'user')->where('guard_name', 'api')->first();
            if ($role && ! $user->hasRole('user')) {
                $user->assignRole($role);
            }

            SocialAccount::updateOrCreate(
                ['provider' => $provider, 'provider_id' => $providerId],
                ['user_id' => $user->id]
            );

            return $user;
        });

        if (($statusResponse = $this->sessionService->checkAccountStatus($user)) instanceof JsonResponse) {
            return $statusResponse;
        }

        // Register the device FCM token, mirroring the email/password login flow.
        if ($fcmToken) {
            FcmToken::assignTo($user, $fcmToken);
        }

        if ($avatarUrl !== '' && (! $user->profile_image || preg_match('/^https?:\\/\\//i', (string) $user->profile_image) === 1)) {
            $storedAvatarPath = $profileImageService->storeFromUrl($avatarUrl, $user->profile_image);
            if ($storedAvatarPath) {
                $user->update(['profile_image' => $storedAvatarPath]);
                $user->refresh();
            }
        }

        $session = $this->sessionService->issueSession($user);
        $token = $session['token'];

        if ($platform === 'web') {
            $frontendUrl = rtrim(config('app.frontend_url'), '/');
            $payload = [
                'token' => $token,
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'image' => $user->profile_image_url,
                'role' => $user->role,
                'is_onboarding' => $session['is_onboarding'],
            ];
            $encodedAuth = base64_encode(json_encode($payload));

            return redirect("{$frontendUrl}/mu/home?auth={$encodedAuth}");
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'is_onboarding' => $session['is_onboarding'],
                'user' => $session['user'],
                'token' => $token,
                'token_type' => 'bearer',
            ],
        ]);
    }
}
