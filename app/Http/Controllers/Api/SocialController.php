<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\ProfileImageService;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Facades\JWTAuth;

class SocialController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google', 'facebook', 'apple'];

    public function redirect($provider)
    {
        if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return response()->json(['success' => false, 'message' => 'Unsupported provider'], 422);
        }

        $platform = request('platform', 'app');

        return response()->json([
            'success' => true,
            'url' => Socialite::driver($provider)
                ->stateless()
                ->with(['state' => "platform={$platform}"])
                ->redirect()
                ->getTargetUrl()
        ]);
    }

    public function callback($provider, ProfileImageService $profileImageService)
    {
        try {
            if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
                return response()->json(['success' => false, 'message' => 'Unsupported provider'], 422);
            }

            $socialUser = Socialite::driver($provider)->stateless()->user();

            $state = request('state');
            parse_str($state, $result);
            $platform = $result['platform'] ?? 'app';

            $providerId = (string) $socialUser->getId();
            $avatarUrl = (string) ($socialUser->getAvatar() ?: '');

            $user = DB::transaction(function () use ($provider, $providerId, $socialUser, $avatarUrl) {
                $socialAccount = SocialAccount::with('user')
                    ->where('provider', $provider)
                    ->where('provider_id', $providerId)
                    ->first();

                if ($socialAccount && $socialAccount->user) {
                    return $socialAccount->user;
                }

                $email = $socialUser->getEmail();
                $user = $email ? User::where('email', $email)->first() : null;

                if (!$user) {
                    $name = trim((string) $socialUser->getName());
                    $parts = $name !== '' ? preg_split('/\s+/', $name) : [];

                    $user = User::create([
                        'first_name' => $parts[0] ?? null,
                        'last_name' => isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : null,
                        'email' => $email ?: sprintf('%s_%s@noemail.com', $provider, $providerId),
                        'is_verified' => true,
                        'profile_image' => $avatarUrl !== '' ? $avatarUrl : null,
                        'password' => Str::random(32),
                    ]);
                }

                // Role and Social Account logic
                $role = Role::where('name', 'user')->where('guard_name', 'api')->first();
                if ($role && !$user->hasRole('user')) {
                    $user->assignRole($role);
                }

                SocialAccount::updateOrCreate(
                    ['provider' => $provider, 'provider_id' => $providerId],
                    ['user_id' => $user->id]
                );

                return $user;
            });

            if ($avatarUrl !== '' && (!$user->profile_image || preg_match('/^https?:\\/\\//i', (string) $user->profile_image) === 1)) {
                $storedAvatarPath = $profileImageService->storeFromUrl($avatarUrl, $user->profile_image);
                if ($storedAvatarPath) {
                    $user->update(['profile_image' => $storedAvatarPath]);
                    $user->refresh();
                }
            }

            $token = JWTAuth::fromUser($user);

            if ($platform === 'web') {
                $frontendUrl = rtrim(config('app.frontend_url'), '/');

                $payload = [
                    'token'      => $token,
                    'id'         => $user->id,
                    'first_name' => $user->first_name,
                    'last_name'  => $user->last_name,
                    'image'      => $user->profile_image_url,
                    'role'       => $this->getRoleNames()->first(),
                    'is_onboarding' => $user->profile ? true : false,
                ];

                $encodedAuth = base64_encode(json_encode($payload));

                return redirect("{$frontendUrl}/mu/home?auth={$encodedAuth}");
            }

            // if ($platform === 'web') {
            //     $frontendUrl = rtrim(config('app.frontend_url'), '/');
            //     return redirect("{$frontendUrl}/mu/home?token={$token}");
            // }

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
                    'is_onboarding' => $user->profile ? true : false,
                    'user' => $user,
                    'token' => $token,
                    'token_type' => 'bearer'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Social login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    // public function redirect($provider)
    // {
    //     if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Unsupported social provider',
    //         ], 422);
    //     }

    //     $url = Socialite::driver($provider)
    //         ->stateless()
    //         ->redirect()
    //         ->getTargetUrl();

    //     return response()->json([
    //         'success' => true,
    //         'url' => $url
    //     ]);
    // }

    // public function callback($provider, ProfileImageService $profileImageService)
    // {
    //     try {
    //         if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Unsupported social provider',
    //             ], 422);
    //         }

    //         $socialUser = Socialite::driver($provider)->stateless()->user();
    //         $providerId = (string) $socialUser->getId();
    //         $avatarUrl = (string) ($socialUser->getAvatar() ?: '');

    //         $user = DB::transaction(function () use ($provider, $providerId, $socialUser, $avatarUrl) {
    //             $socialAccount = SocialAccount::with('user')
    //                 ->where('provider', $provider)
    //                 ->where('provider_id', $providerId)
    //                 ->first();

    //             if ($socialAccount && $socialAccount->user) {
    //                 return $socialAccount->user;
    //             }

    //             $email = $socialUser->getEmail();
    //             $user = null;

    //             if ($email) {
    //                 $user = User::where('email', $email)->first();
    //             }

    //             if (!$user) {
    //                 $name = trim((string) $socialUser->getName());
    //                 $parts = $name !== '' ? preg_split('/\s+/', $name) : [];

    //                 $user = User::create([
    //                     'first_name' => $parts[0] ?? null,
    //                     'last_name' => isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : null,
    //                     'email' => $email ?: sprintf('%s_%s@noemail.com', $provider, $providerId),
    //                     'is_verified' => true,
    //                     'profile_image' => $avatarUrl !== '' ? $avatarUrl : null,
    //                     'password' => Str::random(32),
    //                 ]);
    //             } elseif (!$user->profile_image && $avatarUrl !== '') {
    //                 $user->update(['profile_image' => $avatarUrl]);
    //             }

    //             $role = Role::where('name', 'user')->where('guard_name', 'api')->first();
    //             if ($role && !$user->hasRole('user')) {
    //                 $user->assignRole($role);
    //             }

    //             SocialAccount::updateOrCreate(
    //                 [
    //                     'provider' => $provider,
    //                     'provider_id' => $providerId,
    //                 ],
    //                 [
    //                     'user_id' => $user->id,
    //                 ]
    //             );

    //             return $user;
    //         });

    //         if ($avatarUrl !== '' && (!$user->profile_image || preg_match('/^https?:\\/\\//i', (string) $user->profile_image) === 1)) {
    //             $storedAvatarPath = $profileImageService->storeFromUrl($avatarUrl, $user->profile_image);
    //             if ($storedAvatarPath) {
    //                 $user->update(['profile_image' => $storedAvatarPath]);
    //                 $user->refresh();
    //             }
    //         }

    //         $token = JWTAuth::fromUser($user);

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Login successful',
    //             'data' => [
    //                 'user' => $user,
    //                 'token' => $token,
    //                 'token_type' => 'bearer'
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Social login failed',
    //             'error' => $e->getMessage()
    //         ], 500);
    //     }
    // }
}
