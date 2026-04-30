<?php

use App\Http\Controllers\Admin\AcademiaAdminController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/welcome');
Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome');

Route::get('/clear', function () {
    Artisan::call('optimize:clear');
    return "Cleared!";
});

Route::prefix('admin/academia')->group(function () {

    // 1. Universities (List, Edit, Update)
    Route::get('/universities', [AcademiaAdminController::class, 'indexUniversities'])->name('admin.universities.index');
    Route::post('/universities', [AcademiaAdminController::class, 'storeUniversity'])->name('admin.universities.store');
    Route::put('/universities/{id}', [AcademiaAdminController::class, 'updateUniversity'])->name('admin.universities.update');
    Route::delete('/universities/{id}', [AcademiaAdminController::class, 'destroyUniversity'])->name('admin.universities.destroy');

    // 2. Medical Residencies (List)
    Route::get('/residencies', [AcademiaAdminController::class, 'indexResidencies'])->name('admin.residencies.index');
    Route::post('/residencies', [AcademiaAdminController::class, 'storeResidency'])->name('admin.residencies.store');
    Route::put('/residencies/{id}', [AcademiaAdminController::class, 'updateResidency'])->name('admin.residencies.update');
    Route::delete('/residencies/{id}', [AcademiaAdminController::class, 'destroyResidency'])->name('admin.residencies.destroy');

    // 3. Hospitals & Facilities (List)
    Route::get('/facilities', [AcademiaAdminController::class, 'indexFacilities'])->name('admin.facilities.index');
    Route::post('/facilities', [AcademiaAdminController::class, 'storeFacility'])->name('admin.facilities.store');
    Route::put('/facilities/{id}', [AcademiaAdminController::class, 'updateFacility'])->name('admin.facilities.update');
    Route::delete('/facilities/{id}', [AcademiaAdminController::class, 'destroyFacility'])->name('admin.facilities.destroy');

    // ৪. Jobs (List)
    // Route::get('/jobs', [AcademiaAdminController::class, 'indexJobs'])->name('admin.jobs.index');

});


require __DIR__ . '/auth.php';
