<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class SocialController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google', 'facebook', 'apple'];

    public function redirect($provider)
    {
        if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported social provider',
            ], 422);
        }

        $url = Socialite::driver($provider)
            ->stateless()
            ->redirect()
            ->getTargetUrl();

        return response()->json([
            'success' => true,
            'url' => $url
        ]);
    }

    public function callback($provider)
    {
        try {
            if (!in_array($provider, self::ALLOWED_PROVIDERS, true)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unsupported social provider',
                ], 422);
            }

            $socialUser = Socialite::driver($provider)->stateless()->user();
            $providerId = (string) $socialUser->getId();

            $user = DB::transaction(function () use ($provider, $providerId, $socialUser) {
                $socialAccount = SocialAccount::with('user')
                    ->where('provider', $provider)
                    ->where('provider_id', $providerId)
                    ->first();

                if ($socialAccount && $socialAccount->user) {
                    return $socialAccount->user;
                }

                $email = $socialUser->getEmail();
                $user = null;

                if ($email) {
                    $user = User::where('email', $email)->first();
                }

                if (!$user) {
                    $name = trim((string) $socialUser->getName());
                    $parts = $name !== '' ? preg_split('/\s+/', $name) : [];

                    $user = User::create([
                        'first_name' => $parts[0] ?? null,
                        'last_name' => isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : null,
                        'email' => $email ?: sprintf('%s_%s@noemail.com', $provider, $providerId),
                        'is_verified' => true,
                        'profile_image' => $socialUser->getAvatar() ?: null,
                        'password' => Str::random(32),
                    ]);
                } elseif (!$user->profile_image && $socialUser->getAvatar()) {
                    $user->update([
                        'profile_image' => $socialUser->getAvatar(),
                    ]);
                }

                SocialAccount::updateOrCreate(
                    [
                        'provider' => $provider,
                        'provider_id' => $providerId,
                    ],
                    [
                        'user_id' => $user->id,
                    ]
                );

                return $user;
            });

            $token = JWTAuth::fromUser($user);

            return response()->json([
                'success' => true,
                'message' => 'Login successful',
                'data' => [
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
}
