<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use App\Mail\RecoveryOtpMail;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Failed;

class TwoFactorController extends Controller
{
    protected $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    public function setup(Request $request)
    {
        $request->validate(['password' => 'required']);

        $user = auth('api')->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Incorrect password. Please try again.'
            ], 403);
        }

        if ($user->two_factor_confirmed_at) {
            return response()->json([
                'status' => false,
                'message' => '2FA is already enabled'
            ], 400);
        }

        $secret = $this->google2fa->generateSecretKey();
        $user->update(['two_factor_secret' => $secret]);

        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        return response()->json([
            'status' => true,
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl
        ]);
    }

    public function confirm(Request $request)
    {
        $request->validate(['code' => 'required']);
        $user = auth('api')->user();

        if (!$user->two_factor_secret) {
            return response()->json(['status' => false, 'message' => 'Please initiate 2FA setup first.'], 400);
        }

        if ($this->google2fa->verifyKey($user->two_factor_secret, $request->code)) {
            $plainRecoveryCodes = collect(range(1, 10))->map(fn() => Str::random(10))->toArray();

            $hashedCodes = array_map(fn($code) => bcrypt($code), $plainRecoveryCodes);

            $user->update([
                'two_factor_confirmed_at' => now(),
                'two_factor_recovery_codes' => encrypt(json_encode($hashedCodes))
            ]);

            return response()->json([
                'status' => true,
                'message' => '2FA has been successfully enabled.',
                'recovery_codes' => $plainRecoveryCodes
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Invalid OTP code from app'], 422);
    }

    public function verifyLogin(Request $request)
    {
        $request->validate(['email' => 'required|email', 'code' => 'required']);
        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->two_factor_confirmed_at) {
            return response()->json(['status' => false, 'message' => 'Invalid request'], 403);
        }

        $valid = $this->google2fa->verifyKey($user->two_factor_secret, $request->code);

        if (!$valid && $user->two_factor_recovery_codes) {
            $hashedCodes = json_decode(decrypt($user->two_factor_recovery_codes), true);

            foreach ($hashedCodes as $index => $hashedCode) {
                if (Hash::check($request->code, $hashedCode)) {
                    $valid = true;
                    unset($hashedCodes[$index]);

                    $user->update([
                        'two_factor_recovery_codes' => encrypt(json_encode(array_values($hashedCodes)))
                    ]);
                    break;
                }
            }
        }

        if ($valid) {
            $token = auth('api')->login($user);

            // -----------------------------------------
            // Trigger the Login event to log the successful login activity
            $payload = auth('api')->setToken($token)->getPayload(); // Get the payload of the token
            $tokenId = $payload->get('jti'); // Get the token ID (jti) from the payload
            request()->merge(['current_token_id' => $tokenId]); // Merge the token ID into the request for later use
            event(new Login('api', $user, false)); // Trigger the Login event to log the successful login activity
            // -----------------------------------------

            return $this->respondWithToken($token, $user);
        }

        // if login fails (Suspicious Activity tracking) ---
        event(new Failed('api', $user, ['code' => $request->code]));
        // --------------------------------------------------

        return response()->json(['status' => false, 'message' => 'Invalid verification code'], 401);
    }

    public function disable(Request $request)
    {
        $request->validate(['password' => 'required']);
        $user = auth('api')->user();

        if (Hash::check($request->password, $user->password)) {
            $user->update([
                'two_factor_secret' => null,
                'two_factor_confirmed_at' => null,
                'two_factor_recovery_codes' => null
            ]);
            return response()->json(['status' => true, 'message' => '2FA has been disabled.']);
        }

        return response()->json(['status' => false, 'message' => 'Incorrect password'], 403);
    }

    public function regenerateRecoveryCodes(Request $request)
    {
        $request->validate(['password' => 'required']);
        $user = auth('api')->user();

        if (!$user->two_factor_confirmed_at) {
            return response()->json([
                'status' => false,
                'message' => 'Please enable Two-Factor Authentication first.'
            ], 400);
        }

        if (Hash::check($request->password, $user->password)) {
            $plainRecoveryCodes = collect(range(1, 10))->map(fn() => Str::random(10))->toArray();
            $hashedCodes = array_map(fn($code) => bcrypt($code), $plainRecoveryCodes);

            $user->update([
                'two_factor_recovery_codes' => encrypt(json_encode($hashedCodes))
            ]);

            return response()->json([
                'status' => true,
                'message' => 'New recovery codes generated successfully.',
                'recovery_codes' => $plainRecoveryCodes
            ]);
        }

        return response()->json(['status' => false, 'message' => 'Incorrect password'], 403);
    }

    public function updateRecoveryEmail(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $user = auth('api')->user();

        if (!Hash::check($request->password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Incorrect password. Please try again.'
            ], 403);
        }

        if ($request->email === $user->email) {
            return response()->json([
                'status' => false,
                'message' => 'Recovery email cannot be the same as your primary email address.'
            ], 422);
        }

        $otp = rand(1000, 9999);

        $user->update([
            'recovery_email' => $request->email,
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(3),
            'recovery_email_verified_at' => null
        ]);

        Mail::to($request->email)->send(new RecoveryOtpMail($otp));

        return response()->json(['status' => true, 'message' => 'OTP sent to recovery email.']);
    }

    public function confirmRecoveryEmail(Request $request)
    {
        $request->validate(['otp' => 'required|digits:4']);
        $user = auth('api')->user();

        if ((string)$user->otp === (string)$request->otp && now()->lt($user->otp_expires_at)) {
            $user->update([
                'recovery_email_verified_at' => now(),
                'otp' => null,
                'otp_expires_at' => null
            ]);

            return response()->json(['status' => true, 'message' => 'Recovery email verified successfully.']);
        }

        return response()->json(['status' => false, 'message' => 'Invalid or expired OTP.'], 422);
    }

    public function recoveryInit(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();

        if (!$user || !$user->recovery_email || !$user->recovery_email_verified_at) {
            return response()->json(['status' => false, 'message' => 'No recovery options found for this account.'], 404);
        }

        $parts = explode('@', $user->recovery_email);
        $maskedEmail = substr($parts[0], 0, 3) . '****' . substr($parts[0], -2) . '@' . $parts[1];

        return response()->json([
            'status' => true,
            'masked_email' => $maskedEmail,
            'message' => 'Please enter your full recovery email to receive the code.'
        ]);
    }

    public function recoverySendOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'recovery_email' => 'required|email'
        ]);

        $user = User::where('email', $request->email)->first();

        if (
            !$user ||
            !$user->recovery_email ||
            $user->recovery_email !== $request->recovery_email ||
            !$user->recovery_email_verified_at
        ) {
            return response()->json([
                'status' => false,
                'message' => 'The recovery email address is incorrect or not verified.'
            ], 422);
        }

        $otp = rand(1000, 9999);
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => now()->addMinutes(10)
        ]);

        Mail::to($user->recovery_email)->send(new RecoveryOtpMail($otp));

        return response()->json([
            'status' => true,
            'message' => 'Verification code sent to your recovery email.'
        ]);
    }

    public function recoveryVerify(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'otp' => 'required|digits:4'
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && (string)$user->otp === (string)$request->otp && now()->lt($user->otp_expires_at)) {

            $user->update([
                // 'two_factor_confirmed_at' => null,
                // 'two_factor_secret' => null,
                // 'two_factor_recovery_codes' => null,
                'otp' => null,
                'otp_expires_at' => null
            ]);

            $token = auth('api')->login($user);

            // -----------------------------------------
            // Trigger the Login event to log the successful login activity
            $payload = auth('api')->setToken($token)->getPayload(); // Get the payload of the token
            $tokenId = $payload->get('jti'); // Get the token ID (jti) from the payload
            request()->merge(['current_token_id' => $tokenId]); // Merge the token ID into the request for later use
            event(new Login('api', $user, false)); // Trigger the Login event to log the successful login activity
            // -----------------------------------------

            return $this->respondWithToken($token, $user);
        }

        if ($user) {
            event(new Failed('api', $user, ['otp' => $request->otp]));
        }

        return response()->json(['status' => false, 'message' => 'Invalid or expired OTP.'], 422);
    }

    protected function respondWithToken($token, $user)
    {
        $roleName = $user->getRoleNames()->first();
        $user->makeHidden('roles');
        $user->role = $roleName;

        return response()->json([
            'success' => true,
            'is_onboarding' => $user->profile()->exists(),
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }
}
