<?php

use App\Http\Controllers\Admin\Api\PageController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:admin')->prefix('admin')->group(function () {
    Route::get('/pages/{slug}', [PageController::class, 'getPageData']);
    Route::post('/pages/{slug}', [PageController::class, 'update']);
    
});

Route::middleware('role:user')->group(function () {
    Route::middleware('profile_completed')->group(function () {

    });
});
