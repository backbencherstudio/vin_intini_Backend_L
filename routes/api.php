<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\IndustryApiController;
use App\Http\Controllers\Admin\PagesController;
use App\Http\Controllers\Api\ContactUsController;
use App\Http\Controllers\Api\ProductBoostController;
use App\Http\Controllers\Api\RevenueCatWebhookController;
use App\Http\Controllers\Api\StripeWebhookController;
use Illuminate\Support\Facades\Route;

// Public Static & Information Pages
Route::post('/contact-submit', [ContactUsController::class, 'store']);
Route::get('/pages/{slug}', [PagesController::class, 'getPageData']);
Route::get('/partners', [IndustryApiController::class, 'getAllPartners']);

// Public Webhooks
Route::post('/webhooks/stripe', [StripeWebhookController::class, 'handle']);
Route::post('/webhooks/revenuecat', [RevenueCatWebhookController::class, 'handle'])
    ->middleware('verify_revenuecat_webhook');

// Universal Ad Tracking (Client-side verified IAB impressions & clicks)
Route::post('/advertisements/impressions', [ProductBoostController::class, 'recordImpressions']);
Route::post('/advertisements/{id}/click', [ProductBoostController::class, 'recordClick']);

// Authentication Routes (Public & Authenticated 2FA/Session Lifecycle)
require __DIR__.'/api/auth.php';

// Protected Endpoints
Route::middleware(['auth:api', 'active_session'])->group(function (): void {
    // Authenticated User Features
    Route::middleware('role:user')->group(function (): void {
        require __DIR__.'/api/user.php';
    });

    // Admin API Endpoints
    Route::middleware('role:admin')->group(function (): void {
        require __DIR__.'/api/admin.php';
    });
});
