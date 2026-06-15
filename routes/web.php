<?php

use App\Http\Controllers\Admin\AcademiaAdminController;
use App\Http\Controllers\Admin\BiotechController;
use App\Http\Controllers\Admin\IndustryApiController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\IndustryCategoryController;
use App\Http\Controllers\Admin\IndustryPharmaController;
use App\Http\Controllers\Admin\IndustryPublicationController;
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


Route::prefix('/academia')->group(function () {

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

Route::prefix('admin/partners')->group(function () {
    Route::get('/', [PartnerController::class, 'index'])->name('admin.partners.index');
    Route::post('/store', [PartnerController::class, 'store'])->name('admin.partners.store'); // Create & Update handles here
    Route::get('/delete/{id}', [PartnerController::class, 'destroy'])->name('admin.partners.delete');
});

Route::prefix('admin/categories')->group(function () {
    Route::get('/psychology', [IndustryCategoryController::class, 'psychology'])->name('admin.categories.psychology');
    Route::get('/neuroscience', [IndustryCategoryController::class, 'neuroscience'])->name('admin.categories.neuroscience');
    Route::post('/section/store', [IndustryCategoryController::class, 'storeSection'])->name('admin.sections.store');
    Route::post('/category/store', [IndustryCategoryController::class, 'storeCategory'])->name('admin.categories.store');
    Route::get('/section/delete/{id}', [IndustryCategoryController::class, 'destroySection'])->name('admin.sections.delete');
    Route::get('/category/delete/{id}', [IndustryCategoryController::class, 'destroyCategory'])->name('admin.categories.delete');
});

Route::prefix('admin/biotech')->group(function () {
    Route::get('/psychology', [BiotechController::class, 'psychology'])->name('admin.biotech.psychology');
    Route::get('/neuroscience', [BiotechController::class, 'neuroscience'])->name('admin.biotech.neuroscience');
    Route::post('/store', [BiotechController::class, 'store'])->name('admin.biotech.store');
    Route::get('/delete/{id}', [BiotechController::class, 'destroy'])->name('admin.biotech.delete');
});

Route::prefix('admin/pharma')->group(function () {
    Route::get('/psychology', [IndustryPharmaController::class, 'psychology'])->name('admin.pharma.psychology');
    Route::get('/neuroscience', [IndustryPharmaController::class, 'neuroscience'])->name('admin.pharma.neuroscience');
    Route::post('/store', [IndustryPharmaController::class, 'store'])->name('admin.pharma.store');
    Route::get('/delete/{id}', [IndustryPharmaController::class, 'destroy'])->name('admin.pharma.delete');
});

Route::prefix('admin/publications')->group(function () {
    Route::get('/psychology', [IndustryPublicationController::class, 'psychology'])->name('admin.publications.psychology');
    Route::get('/neuroscience', [IndustryPublicationController::class, 'neuroscience'])->name('admin.publications.neuroscience');
    Route::post('/store', [IndustryPublicationController::class, 'store'])->name('admin.publications.store');
    Route::get('/delete/{id}', [IndustryPublicationController::class, 'destroy'])->name('admin.publications.delete');
});


Route::prefix('api/psychology-network/industry')->group(function () {
    Route::get('/biotech', [IndustryApiController::class, 'getPsychologyBiotech']);
    Route::get('/pharma', [IndustryApiController::class, 'getPsychologyPharma']);
    Route::get('/publications', [IndustryApiController::class, 'getPsychologyPublications']);
});

Route::prefix('api/neuroscience-network/industry')->group(function () {
    Route::get('/biotech', [IndustryApiController::class, 'getNeuroscienceBiotech']);
    Route::get('/pharma', [IndustryApiController::class, 'getNeurosciencePharma']);
    Route::get('/publications', [IndustryApiController::class, 'getNeurosciencePublications']);
});

require __DIR__ . '/auth.php';
