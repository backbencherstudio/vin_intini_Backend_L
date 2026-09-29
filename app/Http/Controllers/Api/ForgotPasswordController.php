<?php

namespace App\Http\Controllers\Api;

use App\Enums\OtpType;
use App\Http\Controllers\Controller;
use App\Mail\PasswordOtpMail;
use App\Models\DeletedAccountLog;
use App\Models\LoginActivity;
use App\Models\Otp;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

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

        $otp = rand(1000, 9999);

        Otp::updateOrCreate(
            ['user_id' => $user->id, 'type' => OtpType::PASSWORD_RESET->value],
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(3),
                'verified_at' => null,
            ]
        );

        // Queue email
        Mail::to($user->email)->queue(new PasswordOtpMail($otp));

        return response()->json([
            'status' => true,
            'message' => 'OTP sent to your email',
        ]);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|digits:4',
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

        $otpRecord->update([
            'verified_at' => Carbon::now(),
        ]);

        return response()->json([
            'status' => true,
            'message' => 'OTP verified successfully',
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'new_password' => 'required|string|min:6|confirmed',
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

        if (! $otpRecord || ! $otpRecord->verified_at || Carbon::now()->gt(Carbon::parse($otpRecord->expires_at))) {
            return response()->json([
                'status' => false,
                'message' => 'OTP verification required or expired',
            ], 400);
        }

        if ($user->trashed()) {
            $user->restore();
            DeletedAccountLog::where('user_id', $user->id)->delete();
        }

        $user->password = Hash::make($request->new_password);
        $user->has_password = true;
        $user->save();

        Otp::where('user_id', $user->id)
            ->where('type', OtpType::PASSWORD_RESET->value)
            ->delete();

        // === previous active sessions inactivity ===
        LoginActivity::where('user_id', $user->id)
            ->where('is_active', true)
            ->update(['is_active' => false]);
        // ===========================================

        return response()->json([
            'status' => true,
            'message' => 'Password reset successfully',
        ]);
    }
}
