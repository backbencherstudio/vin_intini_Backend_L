<?php

use App\Http\Controllers\Admin\Api\BoostPackageAdminController;
use App\Http\Controllers\Admin\Api\IntegrationSettingController;
use App\Http\Controllers\Admin\Api\PlanController;
use App\Http\Controllers\Admin\Api\SubscriptionManagementController;
use App\Http\Controllers\Admin\Api\TransactionController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function () {
    // change password route
    Route::post('/update-password', [UserController::class, 'updatePass']);
    // Route::put('/profile-update', [UserController::class, 'profileUpdate']);

    // integration settings routes
    Route::get('/integrations', [IntegrationSettingController::class, 'index']);
    Route::post('/integrations', [IntegrationSettingController::class, 'update']);

    // plan routes
    Route::get('/plans', [PlanController::class, 'index']);
    Route::get('/plans/{plan}', [PlanController::class, 'show']);
    Route::get('/plan-features', [PlanController::class, 'features']);
    Route::post('/plans/create', [PlanController::class, 'store']);
    Route::patch('/plans/{plan}', [PlanController::class, 'update']);
    Route::patch('/plans/{plan}/status', [PlanController::class, 'toggleStatus']);

    // boost package routes
    Route::get('/boost-packages', [BoostPackageAdminController::class, 'index']);
    Route::get('/boost-packages/{boost_package}/show', [BoostPackageAdminController::class, 'show']);
    Route::post('/boost-packages/store', [BoostPackageAdminController::class, 'store']);
    Route::patch('/boost-packages/{boost_package}/update', [BoostPackageAdminController::class, 'update']);
    Route::patch('/boost-packages/{boost_package}/status', [BoostPackageAdminController::class, 'toggleStatus']);
    Route::delete('/boost-packages/{boost_package}/delete', [BoostPackageAdminController::class, 'destroy']);

    // boost system settings (custom slider, new release, pause policy)
    Route::get('/boost-settings', [BoostPackageAdminController::class, 'getSettings']);
    Route::match(['patch', 'put'], '/boost-settings', [BoostPackageAdminController::class, 'updateSettings']);

    // transaction routes
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/overview', [TransactionController::class, 'overview']);

    // subscription management routes
    Route::get('/subscriptions', [SubscriptionManagementController::class, 'index']);
    Route::post('/subscriptions/{subscription}/cancel', [SubscriptionManagementController::class, 'cancel']);
});
