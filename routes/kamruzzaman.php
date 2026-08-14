<?php

use App\Http\Controllers\Admin\Api\PageController;
use App\Http\Controllers\Admin\Api\AcademiaController;
use App\Http\Controllers\Admin\Api\IndustryCategoryController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:admin')->prefix('admin')->group(function () {
    Route::get('/pages/{slug}', [PageController::class, 'getPageData']);
    Route::post('/pages/{slug}', [PageController::class, 'update']);

    // Academia.....
    Route::prefix('academia')->group(function () {

        // State.....
        Route::get('state', [AcademiaController::class, 'getState']);

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
        Route::put('job/update/{id}', [AcademiaController::class, 'updateJob']);
        Route::delete('job/delete/{id}', [AcademiaController::class, 'destroyJob']);
    });


    // Categories.....
    Route::prefix('categories')->group(function () {

        // Psychology section.....
        Route::get('psychology', [IndustryCategoryController::class, 'psychology']);
        Route::post('psychology/create', [IndustryCategoryController::class, 'storeSection']);
        Route::put('psychology/update/{id}', [IndustryCategoryController::class, 'updateSection']);

        // Neuroscience section.....
        Route::get('neuroscience', [IndustryCategoryController::class, 'neuroscience']);
        Route::post('neuroscience/create', [IndustryCategoryController::class, 'storeNeuroSection']);
        Route::put('neuroscience/update/{id}', [IndustryCategoryController::class, 'updateNeuroSection']);

        // Remove both section.....
        Route::delete('section/delete/{id}', [IndustryCategoryController::class, 'destroySection']);

        // Sub Tabs/Categories.....
        Route::post('sub-cateroy/create', [IndustryCategoryController::class, 'storeSubCategory']);
    });
});


Route::middleware('role:user')->group(function () {
    Route::middleware('profile_completed')->group(function () {});
});
