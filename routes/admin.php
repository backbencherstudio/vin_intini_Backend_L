<?php

use App\Http\Controllers\Admin\PlanController;
use App\Http\Controllers\Admin\SubscriptionManagementController;
use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    //change password route
    Route::post('/update-password', [UserController::class, 'updatePass']);
    // Route::put('/profile-update', [UserController::class, 'profileUpdate']);

    // plan routes
    Route::get('/plans', [PlanController::class, 'index']);
    Route::get('/plans/{plan}', [PlanController::class, 'show']);
    Route::get('/plan-features', [PlanController::class, 'features']);
    Route::post('/plans/create', [PlanController::class, 'store']);
    Route::patch('/plans/{plan}', [PlanController::class, 'update']);
    Route::patch('/plans/{plan}/status', [PlanController::class, 'toggleStatus']);

    // transaction routes
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/overview', [TransactionController::class, 'overview']);

    // subscription management routes
    Route::get('/subscriptions', [SubscriptionManagementController::class, 'index']);
    Route::post('/subscriptions/{subscription}/cancel', [SubscriptionManagementController::class, 'cancel']);
});
