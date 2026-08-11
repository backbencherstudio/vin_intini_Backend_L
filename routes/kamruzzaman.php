<?php

use App\Http\Controllers\Admin\Api\PageController;
use App\Http\Controllers\Admin\Api\AcademiaController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:admin')->prefix('admin')->group(function () {
    Route::get('/pages/{slug}', [PageController::class, 'getPageData']);
    Route::post('/pages/{slug}', [PageController::class, 'update']);

    //Universities.....
    Route::get('academia/universities', [AcademiaController::class, 'indexUniversities']);
    Route::post('academia/university/create', [AcademiaController::class, 'storeUniversity']);
    Route::put('academia/university/update/{id}', [AcademiaController::class, 'updateUniversity']);
});

Route::middleware('role:user')->group(function () {
    Route::middleware('profile_completed')->group(function () {});
});
