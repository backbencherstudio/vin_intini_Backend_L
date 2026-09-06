<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeletedAccountLog;
use App\Models\FcmToken;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\ProfileImageService;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;

class SocialController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google', 'apple'];

    public function redirect($provider)
    {
        if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return response()->json(['success' => false, 'message' => 'Unsupported provider'], 422);
        }

        $platform = request('platform', 'app');
        $fcmToken = request('fcm_token');

        $state = "platform={$platform}";
        if ($fcmToken) {
            $state .= '&fcm_token='.urlencode($fcmToken);
        }

        return response()->json([
            'success' => true,
            'url' => Socialite::driver($provider)
                ->stateless()
                ->with(['state' => $state])
                ->redirect()
                ->getTargetUrl(),
        ]);
    }

    public function callback($provider, ProfileImageService $profileImageService)
    {
        try {
            if (! in_array($provider, self::ALLOWED_PROVIDERS, true)) {
                return response()->json(['success' => false, 'message' => 'Unsupported provider'], 422);
            }

            $socialUser = Socialite::driver($provider)->stateless()->user();

            $state = request('state');
            parse_str($state, $result);
            $platform = $result['platform'] ?? 'app';
            $fcmToken = $result['fcm_token'] ?? null;

            $providerId = (string) $socialUser->getId();
            $avatarUrl = (string) ($socialUser->getAvatar() ?: '');

            $user = DB::transaction(function () use ($provider, $providerId, $socialUser, $avatarUrl) {

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
                        'profile_image' => $avatarUrl !== '' ? $avatarUrl : null,
                        'password' => Str::random(32),
                        'has_password' => false,
                        'terms_accepted_at' => now(),
                    ]);
                }

                // Role and Social Account logic
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

            if ($user->trashed()) {
                $user->restore();
                DeletedAccountLog::where('user_id', $user->id)->delete();
            }

            if ($avatarUrl !== '' && (! $user->profile_image || preg_match('/^https?:\\/\\//i', (string) $user->profile_image) === 1)) {
                $storedAvatarPath = $profileImageService->storeFromUrl($avatarUrl, $user->profile_image);
                if ($storedAvatarPath) {
                    $user->update(['profile_image' => $storedAvatarPath]);
                    $user->refresh();
                }
            }

            $token = JWTAuth::fromUser($user);

            if ($fcmToken) {
                FcmToken::assignTo($user, urldecode($fcmToken));
            }

            // -----------------------------------------
            // Trigger the Login event to log the successful login activity
            $payload = auth('api')->setToken($token)->getPayload(); // Get the payload of the token
            $tokenId = $payload->get('jti'); // Get the token ID (jti) from the payload
            request()->merge(['current_token_id' => $tokenId]); // Merge the token ID into the request for later use
            event(new Login('api', $user, false)); // Trigger the Login event to log the successful login activity
            // -----------------------------------------

            if ($platform === 'web') {
                $frontendUrl = rtrim(config('app.frontend_url'), '/');

                $payload = [
                    'token' => $token,
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'image' => $user->profile_image_url,
                    'role' => $user->roles->first()?->name,
                    'is_onboarding' => $user->profile ? true : false,
                ];

                $encodedAuth = base64_encode(json_encode($payload));

                return redirect("{$frontendUrl}/mu/home?auth={$encodedAuth}");
            }

            // if ($platform === 'web') {
            //     $frontendUrl = rtrim(config('app.frontend_url'), '/');
            //     return redirect("{$frontendUrl}/mu/home?token={$token}");
            // }
            $roleName = $user->getRoleNames()->first();
            $user->makeHidden('roles');
            $user->role = $roleName;

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'is_onboarding' => $user->profile ? true : false,
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'bearer',
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Social login failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
