<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\Api\AcademiaController;
use App\Http\Controllers\Admin\Api\AdminDashboardController;
use App\Http\Controllers\Admin\Api\BoostPackageAdminController;
use App\Http\Controllers\Admin\Api\ContactUsController;
use App\Http\Controllers\Admin\Api\IndustryCategoryController;
use App\Http\Controllers\Admin\Api\InstitutionReportController;
use App\Http\Controllers\Admin\Api\IntegrationSettingController;
use App\Http\Controllers\Admin\Api\PageController;
use App\Http\Controllers\Admin\Api\PartnerManagementController;
use App\Http\Controllers\Admin\Api\PlanController;
use App\Http\Controllers\Admin\Api\PostBaccalaureateController;
use App\Http\Controllers\Admin\Api\PublicationManagementController;
use App\Http\Controllers\Admin\Api\SubscriptionManagementController;
use App\Http\Controllers\Admin\Api\TransactionController;
use App\Http\Controllers\Admin\Api\UserManagementController;
use App\Http\Controllers\Api\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->group(function (): void {
    // Admin Dashboard
    Route::get('/dashboard', [AdminDashboardController::class, 'index']);

    // Admin password update
    Route::post('/update-password', [UserController::class, 'updatePass']);

    // Integration settings routes
    Route::get('/integrations', [IntegrationSettingController::class, 'index']);
    Route::post('/integrations', [IntegrationSettingController::class, 'update']);

    // Plan routes
    Route::get('/plans', [PlanController::class, 'index']);
    Route::get('/plans/{plan}', [PlanController::class, 'show']);
    Route::get('/plan-features', [PlanController::class, 'features']);
    Route::post('/plans/create', [PlanController::class, 'store']);
    Route::patch('/plans/{plan}', [PlanController::class, 'update']);
    Route::patch('/plans/{plan}/status', [PlanController::class, 'toggleStatus']);

    // Boost package routes
    Route::get('/boost-packages', [BoostPackageAdminController::class, 'index']);
    Route::get('/boost-packages/{boost_package}/show', [BoostPackageAdminController::class, 'show']);
    Route::post('/boost-packages/store', [BoostPackageAdminController::class, 'store']);
    Route::patch('/boost-packages/{boost_package}/update', [BoostPackageAdminController::class, 'update']);
    Route::patch('/boost-packages/{boost_package}/status', [BoostPackageAdminController::class, 'toggleStatus']);
    Route::delete('/boost-packages/{boost_package}/delete', [BoostPackageAdminController::class, 'destroy']);

    // Boost system settings (custom slider, new release, pause policy)
    Route::get('/boost-settings', [BoostPackageAdminController::class, 'getSettings']);
    Route::match(['patch', 'put'], '/boost-settings', [BoostPackageAdminController::class, 'updateSettings']);

    // Transaction routes
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::get('/transactions/overview', [TransactionController::class, 'overview']);

    // Subscription management routes
    Route::get('/subscriptions', [SubscriptionManagementController::class, 'index']);
    Route::post('/subscriptions/{subscription}/cancel', [SubscriptionManagementController::class, 'cancel']);

    // User Management
    Route::prefix('user-manage')->group(function (): void {
        Route::get('users', [UserManagementController::class, 'index']);
        Route::get('users/{id}', [UserManagementController::class, 'show']);
        Route::post('users/{id}/toggle-suspend', [UserManagementController::class, 'toggleSuspend']);
        Route::get('leave-request', [UserManagementController::class, 'getDeletedAccountLogs']);
    });

    // Partners (Pro Industry Companies) Management
    Route::prefix('partners')->group(function (): void {
        Route::get('/', [PartnerManagementController::class, 'index']);
        Route::post('{id}/toggle-pause', [PartnerManagementController::class, 'togglePause']);
    });

    // Publications Management
    Route::prefix('publications')->group(function (): void {
        Route::get('/', [PublicationManagementController::class, 'index']);
        Route::get('{id}', [PublicationManagementController::class, 'show']);
        Route::post('{id}/toggle-status', [PublicationManagementController::class, 'toggleStatus']);
        Route::delete('{id}', [PublicationManagementController::class, 'destroy']);
    });

    // Academia Management
    Route::prefix('academia')->group(function (): void {
        // State
        Route::get('state', [AcademiaController::class, 'getState']);

        // Universities
        Route::get('universities', [AcademiaController::class, 'indexUniversities']);
        Route::post('university/create', [AcademiaController::class, 'storeUniversity']);
        Route::put('university/update/{id}', [AcademiaController::class, 'updateUniversity']);
        Route::delete('university/delete/{id}', [AcademiaController::class, 'destroyUniversity']);

        // Residencies
        Route::get('residencies', [AcademiaController::class, 'indexResidencies']);
        Route::post('residency/create', [AcademiaController::class, 'storeResidency']);
        Route::put('residency/update/{id}', [AcademiaController::class, 'updateResidency']);
        Route::delete('residency/delete/{id}', [AcademiaController::class, 'destroyResidency']);

        // Facilities
        Route::get('facilities', [AcademiaController::class, 'indexFacilities']);
        Route::post('facility/create', [AcademiaController::class, 'storeFacility']);
        Route::put('facility/update/{id}', [AcademiaController::class, 'updateFacility']);
        Route::delete('facility/delete/{id}', [AcademiaController::class, 'destroyFacility']);

        // Employment / Jobs
        Route::get('jobs', [AcademiaController::class, 'indexJobs']);
        Route::post('job/create', [AcademiaController::class, 'storeJob']);
        Route::put('job/update/{id}', [AcademiaController::class, 'updateJob']);
        Route::delete('job/delete/{id}', [AcademiaController::class, 'destroyJob']);

        // Post-Baccalaureate Categories & Programs
        Route::get('post-baccalaureate/categories', [PostBaccalaureateController::class, 'indexCategories']);
        Route::post('post-baccalaureate/category/create', [PostBaccalaureateController::class, 'storeCategory']);
        Route::put('post-baccalaureate/category/update/{id}', [PostBaccalaureateController::class, 'updateCategory']);
        Route::patch('post-baccalaureate/categories/reorder', [PostBaccalaureateController::class, 'reorderCategories']);
        Route::delete('post-baccalaureate/category/delete/{id}', [PostBaccalaureateController::class, 'destroyCategory']);

        Route::get('post-baccalaureate', [PostBaccalaureateController::class, 'indexPrograms']);
        Route::post('post-baccalaureate/create', [PostBaccalaureateController::class, 'storeProgram']);
        Route::put('post-baccalaureate/update/{id}', [PostBaccalaureateController::class, 'updateProgram']);
        Route::delete('post-baccalaureate/delete/{id}', [PostBaccalaureateController::class, 'destroyProgram']);
    });

    // Institution Report
    Route::get('institution-report', [InstitutionReportController::class, 'institutionReport']);
    Route::get('institution-report/students/{id}', [InstitutionReportController::class, 'showStudents']);

    // Contact Us
    Route::get('contact-us', [ContactUsController::class, 'getContactUs']);

    // Categories & Sections
    Route::prefix('categories')->group(function (): void {
        // Psychology section
        Route::get('psychology', [IndustryCategoryController::class, 'psychology']);
        Route::post('psychology/create', [IndustryCategoryController::class, 'storeSection']);
        Route::put('psychology/update/{id}', [IndustryCategoryController::class, 'updateSection']);

        // Neuroscience section
        Route::get('neuroscience', [IndustryCategoryController::class, 'neuroscience']);
        Route::post('neuroscience/create', [IndustryCategoryController::class, 'storeNeuroSection']);
        Route::put('neuroscience/update/{id}', [IndustryCategoryController::class, 'updateNeuroSection']);

        // Remove section
        Route::delete('section/delete/{id}', [IndustryCategoryController::class, 'destroySection']);

        // Sub Tabs / Categories
        Route::post('sub-category/create', [IndustryCategoryController::class, 'storeSubCategory']);
        Route::put('sub-category/update/{id}', [IndustryCategoryController::class, 'updateSubCategory']);
        Route::delete('sub-category/delete/{id}', [IndustryCategoryController::class, 'destroySubCategory']);

        // Backward compatibility for typo
        Route::post('sub-cateroy/create', [IndustryCategoryController::class, 'storeSubCategory']);
        Route::put('sub-cateroy/update/{id}', [IndustryCategoryController::class, 'updateSubCategory']);
        Route::delete('sub-cateroy/delete/{id}', [IndustryCategoryController::class, 'destroySubCategory']);
    });

    // Pages
    Route::get('/pages/{slug}', [PageController::class, 'getPageData']);
    Route::put('/pages/{slug}', [PageController::class, 'update']);
});
