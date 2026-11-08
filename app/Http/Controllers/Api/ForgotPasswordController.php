<?php

namespace App\Http\Controllers\Api;

use App\Enums\OtpType;
use App\Http\Controllers\Controller;
use App\Models\DeletedAccountLog;
use App\Models\LoginActivity;
use App\Models\Otp;
use App\Models\User;
use App\Notifications\PasswordOtpNotification;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ForgotPasswordController extends Controller
{
    public function sendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::withTrashed()->where('email', $request->email)->first();

        $otp = random_int(1000, 9999);

        Otp::updateOrCreate(
            ['user_id' => $user->id, 'type' => OtpType::PASSWORD_RESET->value],
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(3),
                'verified_at' => null,
            ]
        );

        // Queue notification
        $user->notify(new PasswordOtpNotification($otp));

        return response()->json([
            'status' => true,
            'message' => 'OTP sent to your email',
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|digits:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::withTrashed()->where('email', $request->email)->first();

        $otpRecord = Otp::where('user_id', $user->id)
            ->where('type', OtpType::PASSWORD_RESET->value)
            ->first();

        if (! $otpRecord || (string) $otpRecord->otp !== (string) $request->otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP',
            ], 400);
        }

        if (Carbon::now()->gt(Carbon::parse($otpRecord->expires_at))) {
            return response()->json([
                'status' => false,
                'message' => 'OTP expired',
            ], 400);
        }

        $resetToken = Str::random(64);

        return DB::transaction(function () use ($user, $otpRecord, $resetToken) {
            $otpRecord->delete();

            DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                [
                    'token' => Hash::make($resetToken),
                    'created_at' => Carbon::now(),
                ]
            );

            return response()->json([
                'status' => true,
                'message' => 'OTP verified successfully',
                'reset_token' => $resetToken,
            ]);
        });
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'new_password' => 'required|string|min:6|confirmed',
            'reset_token' => 'required_without:otp|string',
            'otp' => 'required_without:reset_token|string|digits:4',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::withTrashed()->where('email', $request->email)->first();

        // 1. Verify reset_token proof if provided
        if ($request->filled('reset_token')) {
            $record = DB::table('password_reset_tokens')->where('email', $request->email)->first();

            if (! $record || ! Hash::check($request->reset_token, $record->token)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid or expired password reset token.',
                ], 400);
            }

            if (Carbon::parse($record->created_at)->addMinutes(15)->isPast()) {
                DB::table('password_reset_tokens')->where('email', $request->email)->delete();

                return response()->json([
                    'status' => false,
                    'message' => 'Password reset token has expired.',
                ], 400);
            }
        } elseif ($request->filled('otp')) {
            // 2. Or verify direct OTP if caller submits it directly
            $otpRecord = Otp::where('user_id', $user->id)
                ->where('type', OtpType::PASSWORD_RESET->value)
                ->first();

            if (! $otpRecord || (string) $otpRecord->otp !== (string) $request->otp || Carbon::now()->gt(Carbon::parse($otpRecord->expires_at))) {
                return response()->json([
                    'status' => false,
                    'message' => 'OTP verification required or expired',
                ], 400);
            }
        }

        return DB::transaction(function () use ($user, $request) {
            // Atomically consume token and OTP
            DB::table('password_reset_tokens')->where('email', $request->email)->delete();
            Otp::where('user_id', $user->id)->where('type', OtpType::PASSWORD_RESET->value)->delete();

            if ($user->trashed()) {
                $user->restore();
                DeletedAccountLog::where('user_id', $user->id)->delete();
            }

            $user->password = Hash::make($request->new_password);
            $user->has_password = true;
            $user->save();

            // === previous active sessions inactivity ===
            LoginActivity::where('user_id', $user->id)
                ->where('is_active', true)
                ->update(['is_active' => false]);
            // ===========================================

            return response()->json([
                'status' => true,
                'message' => 'Password reset successfully',
            ]);
        });
    }
}
