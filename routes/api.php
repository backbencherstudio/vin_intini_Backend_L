<?php

use App\Http\Controllers\Api\AcademiaController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ContactUsController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\SocialController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\UserEducationController;
use App\Http\Controllers\Api\UserExperienceController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\Admin\PagesController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::get('/login', function () {
    return response()->json([
        'success' => false,
        'message' => 'Please login to continue',
    ], 401);
})->name('login');

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
// Contact Us Route
Route::post('/contact-submit', [ContactUsController::class, 'store']);
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
Route::post('/refresh', [AuthController::class, 'refresh']);

// pages routes
Route::get('/pages/{slug}', [PagesController::class, 'getPageData']);

Route::prefix('2fa')->group(function () {
    // 2FA Verification during Login
    Route::post('/verify-login', [TwoFactorController::class, 'verifyLogin'])->middleware('throttle:5,1');
    // Account Recovery when Authenticator App is lost
    Route::post('/recovery-init', [TwoFactorController::class, 'recoveryInit'])->middleware('throttle:5,1');
    Route::post('/recovery-send-otp', [TwoFactorController::class, 'recoverySendOtp'])->middleware('throttle:3,1');
    Route::post('/recovery-verify', [TwoFactorController::class, 'recoveryVerify'])->middleware('throttle:5,1');
});

Route::middleware('auth:api', 'active_session')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    Route::get('/login-activities', [SettingsController::class, 'getLoginActivities']);
    Route::post('/sessions/revoke/{id}', [SettingsController::class, 'revokeSession']);  //remove active session

    Route::prefix('2fa')->group(function () {
        Route::post('/setup', [TwoFactorController::class, 'setup']);
        Route::post('/confirm', [TwoFactorController::class, 'confirm']);
        Route::post('/disable', [TwoFactorController::class, 'disable']);
        Route::post('/regenerate-codes', [TwoFactorController::class, 'regenerateRecoveryCodes']);
        // Recovery Email Setup Routes
        Route::post('/recovery-email/update', [TwoFactorController::class, 'updateRecoveryEmail']);
        Route::post('/recovery-email/confirm', [TwoFactorController::class, 'confirmRecoveryEmail']);
    });

    Route::middleware('role:user')->group(function () {
        // group, company, skill and institution suggestions for dropdown
        Route::get('/company-suggestions', [UserExperienceController::class, 'companySuggestions']);
        Route::get('/skill-suggestions', [UserExperienceController::class, 'skillSuggestions']);
        Route::get('/institution-suggestions', [UserEducationController::class, 'institutionSuggestions']);
        Route::get('/groups-suggestions', [GroupController::class, 'groupSuggestions']);

        Route::post('/setup-profile', [UserProfileController::class, 'setupProfile'])->middleware('verified_user');

        Route::middleware('profile_completed')->group(function () {
            require __DIR__ . '/niaz.php';
            require __DIR__ . '/shanto.php';
        });
    });

    Route::middleware('role:admin')->group(function () {
        require __DIR__ . '/admin.php';
    });
});
