<?php

use App\Http\Controllers\Admin\Api\AcademiaController;
use App\Http\Controllers\Admin\Api\ContactUsController;
use App\Http\Controllers\Admin\Api\IndustryCategoryController;
use App\Http\Controllers\Admin\Api\InstitutionReportController;
use App\Http\Controllers\Admin\Api\PageController;
use App\Http\Controllers\Admin\Api\UserManagementController;
use App\Http\Controllers\Auth\IndustryController;
use Illuminate\Support\Facades\Route;

Route::middleware('role:admin')->prefix('admin')->group(function () {

    // User Management.....
    Route::prefix('user-manage')->group(function () {
        Route::get('leave-request', [UserManagementController::class, 'getDeletedAccountLogs']);
    });

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

    // Institution Report.....
    Route::get('institution-report', [InstitutionReportController::class, 'institutionReport']);
    Route::get('institution-report/students/{id}', [InstitutionReportController::class, 'showStudents']);

    // Contact Us.....
    Route::get('contact-us', [ContactUsController::class, 'getContactUs']);

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
        Route::put('sub-cateroy/update/{id}', [IndustryCategoryController::class, 'updateSubCategory']);
        Route::delete('sub-cateroy/delete/{id}', [IndustryCategoryController::class, 'destroySubCategory']);
    });

    // Pages.....
    Route::get('/pages/{slug}', [PageController::class, 'getPageData']);
    Route::post('/pages/{slug}', [PageController::class, 'update']);
});


Route::middleware('role:user')->group(function () {

    // Recruiter Dashboard.....
    Route::prefix('industry')->group(function () {
        Route::post('create', [IndustryController::class, 'store']);
        Route::get('show', [IndustryController::class, 'show']);
        Route::post('update', [IndustryController::class, 'update']);

        // Recruiter post
        Route::post('post/create', [IndustryController::class, 'storePost']);
        Route::get('post/view', [IndustryController::class, 'indexPost']);
        Route::get('post/recent', [IndustryController::class, 'latestPosts']);
        Route::post('post/like/{postId}', [IndustryController::class, 'togglePostLike']);
        Route::post('post/comment/{postId}', [IndustryController::class, 'storeComment']);
        Route::post('post/comment/reply/{commentId}', [IndustryController::class, 'replyComment']);
    });


    Route::middleware('profile_completed')->group(function () {});
});
