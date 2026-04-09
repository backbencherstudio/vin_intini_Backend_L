<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Str;
use Tymon\JWTAuth\Facades\JWTAuth;

class SocialController extends Controller
{
    public function redirect($provider)
    {
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
            $socialUser = Socialite::driver($provider)->stateless()->user();

            $email = $socialUser->getEmail();

            if (!$email) {
                $email = $provider . '_' . $socialUser->getId() . '@noemail.com';
            }

            $user = User::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if (!$user) {
                $user = User::where('email', $email)->first();

                if ($user) {
                    $user->update([
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                    ]);
                } else {
                    $name = $socialUser->getName();

                    $firstName = null;
                    $lastName = null;

                    if ($name) {
                        $parts = explode(' ', $name);
                        $firstName = $parts[0] ?? null;
                        $lastName = $parts[1] ?? null;
                    }

                    $user = User::create([
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'email' => $email,
                        'is_verified' => 1,
                        'provider' => $provider,
                        'provider_id' => $socialUser->getId(),
                        'profile_image' => $socialUser->getAvatar() ?? null,
                        'password' => bcrypt(Str::random(16)),
                    ]);
                }
            }

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
