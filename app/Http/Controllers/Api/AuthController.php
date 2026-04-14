<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\RegisterOtpMail;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $credentials = $validator->validated();

        $user = User::where('email', $credentials['email'])->first();
        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        if (! $user->is_verified) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email with OTP before login.',
            ], 403);
        }

        // Attempt login (JWT token)
        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        $user = auth('api')->user();

        return $this->respondWithToken($token, $user);
    }

    public function me()
    {
        $user = auth('api')->user();

        $user->load(['roles', 'profile.currentPosition.company']);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'title' => $user->title,
                'email' => $user->email,
                'profile_image_url' => $user->profile_image_url,
                'cover_image_url' => $user->cover_image_url,
                'roles' => $user->roles->pluck('name')->implode(', '),

                'profile' => $user->profile ? [
                    'country' => $user->profile->country,
                    'postal_code' => $user->profile->postal_code,
                    'profession' => $user->profile->profession,
                    'highest_degree' => $user->profile->highest_degree,
                    'study_category' => $user->profile->study_category,
                    'study_subcategory' => $user->profile->study_subcategory,
                    'institution' => $user->profile->institution,
                    'graduation_year' => $user->profile->graduation_year,
                    'interests' => $user->profile->interests,
                    'skills_id' => $user->profile->skills_id,
                    'skills' => Skill::query()
                        ->whereIn('id', $user->profile->skills_id ?? [])
                        ->orderBy('name')
                        ->pluck('name')
                        ->values(),
                    'current_position_id' => $user->profile->current_position_id,
                    'current_position' => $user->profile->currentPosition ? [
                        'id' => $user->profile->currentPosition->id,
                        'title' => $user->profile->currentPosition->title,
                        'company_name' => $user->profile->currentPosition->company?->name,
                    ] : null,
                    'about' => $user->profile->about,
                ] : null,
            ],
        ]);
    }

    public function logout()
    {
        auth('api')->logout();

        return response()->json([
            'success' => true,
            'message' => 'Successfully logged out',
        ]);
    }

    public function refresh()
    {
        try {
            $token = auth('api')->refresh();

            $user = auth('api')->user();

            return $this->respondWithToken($token, $user);
        } catch (TokenExpiredException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Refresh token expired. Please login again.',
            ], 401);
        } catch (JWTException $e) {

            return response()->json([
                'success' => false,
                'message' => 'Token invalid or not provided',
            ], 401);
        }
    }

    protected function respondWithToken($token, $user)
    {
        return response()->json([
            'success' => true,
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $otp = random_int(1000, 9999);
            $role = Role::where('name', 'user')->first();

            $user = User::where('email', $request->email)->first();

            // ================= EXISTING USER =================
            if ($user) {

                if ($user->is_verified) {
                    return response()->json([
                        'status' => false,
                        'message' => 'This email is already registered.',
                    ], 409);
                }

                $user->update([
                    'password' => Hash::make($request->password),
                    'otp' => $otp,
                    'otp_expires_at' => now()->addMinutes(3),
                    'is_verified' => false,
                ]);

                if ($role && ! $user->hasRole('user')) {
                    $user->assignRole($role);
                }

                Mail::to($user->email)->queue(new RegisterOtpMail($otp));

                DB::commit();

                return response()->json([
                    'status' => true,
                    'message' => 'OTP resent to your email.',
                ], 200);
            }

            // ================= NEW USER =================
            $user = User::create([
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'otp' => $otp,
                'otp_expires_at' => now()->addMinutes(3), // consistent
                'is_verified' => false,
            ]);

            if ($role) {
                $user->assignRole($role);
            }

            Mail::to($user->email)->queue(new RegisterOtpMail($otp));

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Registration successful. OTP sent to your email.',
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => false,
                'message' => 'Registration failed.',
                // 'error' => $e->getMessage(), // enable in local
            ], 500);
        }
    }

    public function verifyRegisterOtp(Request $request)
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

        $user = User::where('email', $request->email)->first();

        if ($user->is_verified) {
            $token = auth('api')->login($user);

            return response()->json([
                'status' => true,
                'message' => 'Email already verified.',
                'user' => $user,
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60,
            ]);
        }

        if (! $user->otp || (string) $user->otp !== (string) $request->otp) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid OTP',
            ], 400);
        }

        if (! $user->otp_expires_at || now()->gt($user->otp_expires_at)) {
            return response()->json([
                'status' => false,
                'message' => 'OTP expired',
                'can_resend_otp' => true,
            ], 400);
        }

        $user->forceFill([
            'is_verified' => true,
            'otp' => null,
            'otp_expires_at' => null,
        ])->save();

        $token = auth('api')->login($user);

        return response()->json([
            'status' => true,
            'message' => 'Email verified successfully.',
            'user' => $user,
            'token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
        ]);
    }

    public function resendRegisterOtp(Request $request)
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

        $user = User::where('email', $request->email)->first();
        if (! $user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        if ($user->is_verified) {
            return response()->json([
                'status' => true,
                'message' => 'Email already verified.',
            ], 200);
        }

        if ($user->otp_expires_at && now()->lt($user->otp_expires_at)) {

            $remainingSeconds = (int) ceil(now()->diffInSeconds($user->otp_expires_at, false));

            return response()->json([
                'status' => false,
                'message' => "Please wait {$remainingSeconds} seconds before requesting a new OTP.",
            ], 429);
        }

        try {
            $otp = rand(1000, 9999);

            $user->forceFill([
                'otp' => $otp,
                'otp_expires_at' => now()->addMinutes(3),
                'is_verified' => false,
            ])->save();

            Mail::to($user->email)->queue(new RegisterOtpMail($otp));

            return response()->json([
                'status' => true,
                'message' => 'OTP resent to your email.',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Failed to resend OTP.',
            ], 500);
        }
    }

    // public function register(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name'     => 'required|string|max:255',
    //         'email'    => 'required|email|unique:users,email',
    //         'mobile'   => 'nullable|string|max:20',
    //         'password' => 'required|string|min:6|confirmed',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'Validation failed.',
    //             'errors'  => $validator->errors()
    //         ], 422);
    //     }

    //     $validated = $validator->validated();

    //     DB::beginTransaction();

    //     try {
    //         $user = User::create([
    //             'name'     => $validated['name'],
    //             'email'    => $validated['email'],
    //             'mobile'   => $validated['mobile'] ?? null,
    //             'password' => bcrypt($validated['password']),
    //         ]);
    //         // Assign role to user (API guard)
    //         $role = Role::where('name', 'user')
    //             ->where('guard_name', 'api')
    //             ->firstOrFail();

    //         $user->assignRole($role);
    //         DB::commit();

    //         return response()->json([
    //             'status'  => true,
    //             'message' => 'User registered successfully.',
    //             'data'    => [
    //                 'id'    => $user->id,
    //                 'name'  => $user->name,
    //                 'email' => $user->email,
    //                 'roles' => $user->getRoleNames(),
    //             ]
    //         ], 201);
    //     } catch (\Throwable $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'status'  => false,
    //             'message' => 'User registration failed.',
    //         ], 500);
    //     }
    // }
}
