<?php

use App\Http\Controllers\Admin\Api\PageController;
use App\Http\Controllers\Admin\Api\AcademiaController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:admin')->prefix('admin')->group(function () {
    Route::get('/pages/{slug}', [PageController::class, 'getPageData']);
    Route::post('/pages/{slug}', [PageController::class, 'update']);

    // Academia.....
    Route::prefix('academia')->group(function () {

        // Universities.....
        Route::get('universities', [AcademiaController::class, 'indexUniversities']);
        Route::post('university/create', [AcademiaController::class, 'storeUniversity']);
        Route::put('university/update/{id}', [AcademiaController::class, 'updateUniversity']);
        Route::delete('university/delete/{id}', [AcademiaController::class, 'destroyUniversity']);

        // Residencies.....
        Route::get('residencies', [AcademiaController::class, 'indexResidencies']);
        Route::post('residency/create', [AcademiaController::class, 'storeResidency']);
        Route::put('residency/update/{id}', [AcademiaController::class, 'updateResidency']);
        Route::delete('residency/delete/{id}', [AcademiaController::class, 'destroyResidency']);

        // Facilities.....
        Route::get('facilities', [AcademiaController::class, 'indexFacilities']);
        Route::post('facility/create', [AcademiaController::class, 'storeFacility']);
        Route::put('facility/update/{id}', [AcademiaController::class, 'updateFacility']);
        Route::delete('facility/delete/{id}', [AcademiaController::class, 'destroyFacility']);

        // Employment.....
        Route::get('jobs', [AcademiaController::class, 'indexJobs']);
        Route::post('job/create', [AcademiaController::class, 'storeJob']);
    });
});


Route::middleware('role:user')->group(function () {
    Route::middleware('profile_completed')->group(function () {});
});
