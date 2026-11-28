<?php

declare(strict_types=1);

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ForgotPasswordController;
use App\Http\Controllers\Api\SecuritySettingsController;
use App\Http\Controllers\Api\SocialController;
use App\Http\Controllers\Api\TwoFactorController;
use Illuminate\Support\Facades\Route;

// Public Authentication
Route::get('/login', fn () => response()->json([
    'success' => false,
    'message' => 'Please login to continue',
], 401))->name('login');

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:api-login');
Route::post('/admin/login', [AuthController::class, 'adminLogin'])->middleware('throttle:api-login');
Route::post('/send-otp', [ForgotPasswordController::class, 'sendOtp'])->middleware('throttle:api-send-otp');
Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOtp'])->middleware('throttle:api-verify-otp');
Route::post('/password-reset', [ForgotPasswordController::class, 'resetPassword'])->middleware('throttle:5,1');
Route::post('/account/restore', [SecuritySettingsController::class, 'restore'])->middleware('throttle:5,1');

Route::post('/register', [AuthController::class, 'register'])->name('api.register')->middleware('throttle:api-register');
Route::post('/register/verify-otp', [AuthController::class, 'verifyRegisterOtp'])->middleware('throttle:api-verify-otp');
Route::post('/register/resend-otp', [AuthController::class, 'resendRegisterOtp'])->middleware('throttle:api-resend-otp');

// Social Authentication Routes
Route::get('/auth/{provider}', [SocialController::class, 'redirect']);
Route::match(['get', 'post'], '/auth/{provider}/callback', [SocialController::class, 'callback']);
Route::post('/auth/social-login', [SocialController::class, 'socialLogin']);
Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('throttle:20,1');

// Public 2FA challenge & recovery routes
Route::prefix('2fa')->group(function (): void {
    Route::post('/verify-login', [TwoFactorController::class, 'verifyLogin'])->middleware('throttle:5,1');
    Route::post('/recovery-init', [TwoFactorController::class, 'recoveryInit'])->middleware('throttle:5,1');
    Route::post('/recovery-send-otp', [TwoFactorController::class, 'recoverySendOtp'])->middleware('throttle:3,1');
    Route::post('/recovery-verify', [TwoFactorController::class, 'recoveryVerify'])->middleware('throttle:5,1');
    Route::post('/recovery/resend-otp', [TwoFactorController::class, 'recoveryResendOtp'])->middleware('throttle:5,1');
});

// Authenticated session lifecycle & 2FA management
Route::middleware(['auth:api', 'active_session'])->group(function (): void {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);

    // User Login Activities and Active Sessions Security Routes
    Route::prefix('security')->group(function (): void {
        Route::get('/overview', [SecuritySettingsController::class, 'getSecurityOverview']);
        Route::get('/active-sessions', [SecuritySettingsController::class, 'getActiveSessions']);
        Route::get('/login-activities', [SecuritySettingsController::class, 'getLoginActivities']);
        Route::get('/login-activity/{id}', [SecuritySettingsController::class, 'getLoginActivityDetails']);
        Route::get('/suspicious-activities-list', [SecuritySettingsController::class, 'getSuspiciousActivitiesList']);
        Route::get('/audit-logs', [SecuritySettingsController::class, 'getSecurityAuditLogs']);

        Route::middleware('trusted_session')->group(function (): void {
            Route::post('/sessions/revoke/{id}', [SecuritySettingsController::class, 'revokeSession']);
            Route::post('/sessions/sign-out-all', [SecuritySettingsController::class, 'signOutAllSessions']);
            Route::post('/resolve-all', [SecuritySettingsController::class, 'resolveAllActivities']);
            Route::post('/resolve/{id}', [SecuritySettingsController::class, 'resolveSuspiciousLogin']);
            Route::post('/secure-account/{id}', [SecuritySettingsController::class, 'secureAccount']);
            Route::delete('/login-activities/clear-all', [SecuritySettingsController::class, 'deleteAllLoginActivities']);
            Route::post('/login-activities/bulk-delete', [SecuritySettingsController::class, 'bulkDeleteLoginActivities']);
            Route::delete('/login-activities/{id}', [SecuritySettingsController::class, 'deleteLoginActivity']);
        });
    });

    Route::prefix('2fa')->middleware('trusted_session')->group(function (): void {
        Route::post('/setup', [TwoFactorController::class, 'setup'])->middleware('throttle:5,1');
        Route::post('/confirm', [TwoFactorController::class, 'confirm'])->middleware('throttle:5,1');
        Route::post('/disable', [TwoFactorController::class, 'disable'])->middleware('throttle:5,1');
        Route::post('/regenerate-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->middleware('throttle:5,1');
        Route::post('/recovery-email/update', [TwoFactorController::class, 'updateRecoveryEmail'])->middleware('throttle:5,1');
        Route::post('/recovery-email/confirm', [TwoFactorController::class, 'confirmRecoveryEmail'])->middleware('throttle:5,1');
        Route::post('/recovery-email/resend-otp', [TwoFactorController::class, 'resendRecoveryEmailOtp'])->middleware('throttle:3,1');
    });
});
