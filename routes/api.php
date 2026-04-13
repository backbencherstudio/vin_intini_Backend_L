<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\SocialController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return response()->json([
        'success' => false,
        'message' => 'Please login to continue',
    ], 401);
})->name('login');

Route::post('/login', [AuthController::class, 'login']);

// User Forgot Password Routes
Route::post('/send-otp', [ForgotPasswordController::class, 'sendOtp']);
Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp']);
Route::post('/password-reset', [ForgotPasswordController::class, 'resetPassword']);

// User Register Routes
Route::post('/register', [AuthController::class, 'register'])->name('api.register');
Route::post('/register/verify-otp', [AuthController::class, 'verifyRegisterOtp']);
Route::post('/register/resend-otp', [AuthController::class, 'resendRegisterOtp'])->middleware('throttle:3,1');
// Social Authentication Routes
Route::get('/auth/{provider}', [SocialController::class, 'redirect']);
Route::get('/auth/{provider}/callback', [SocialController::class, 'callback']);

Route::middleware('auth:api')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/refresh', [AuthController::class, 'refresh']);

    Route::post('/setup-profile', [UserProfileController::class, 'setupProfile'])
        ->middleware('verified_user');

    Route::middleware('profile_completed')->group(function () {
        Route::middleware(['role:admin'])->group(function () {
            Route::post('/update-password', [UserController::class, 'updatePass']);
            Route::put('/profile-update', [UserController::class, 'profileUpdate']);
        });

        require __DIR__.'/niaz.php';
        require __DIR__.'/shanto.php';
    });
});
