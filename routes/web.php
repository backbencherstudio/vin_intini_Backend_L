<?php

use App\Http\Controllers\Admin\AcademiaAdminController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\BiotechController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Admin\IndustryCategoryController;
use App\Http\Controllers\Admin\IndustryPharmaController;
use App\Http\Controllers\Admin\IndustryPublicationController;
use App\Http\Controllers\Admin\PagesController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/welcome');
Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome');

Route::get('/clear', function () {
    Artisan::call('optimize:clear');
    return "Cleared!";
});

Route::middleware('guest:web')->group(function () {
    // Admin Routes (Temporary admin dashboard routes)
    Route::get('admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('admin/login-submit', [AdminAuthController::class, 'adminLogin'])->name('admin.login.submit');
});

Route::group(['prefix' => 'admin', 'middleware' => ['auth:web', 'role:admin']], function () {

    Route::get('/user-management', [AdminAuthController::class, 'userManagement'])->name('admin.user.management');

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
        Route::get('/jobs', [AcademiaAdminController::class, 'indexJobs'])->name('admin.jobs.index');
        Route::post('/jobs', [AcademiaAdminController::class, 'storeJob'])->name('admin.jobs.store');
        Route::put('/jobs/{id}', [AcademiaAdminController::class, 'updateJob'])->name('admin.jobs.update');
        Route::delete('/jobs/{id}', [AcademiaAdminController::class, 'destroyJob'])->name('admin.jobs.destroy');
    });

    Route::prefix('partners')->group(function () {
        Route::get('/', [PartnerController::class, 'index'])->name('admin.partners.index');
        Route::post('/store', [PartnerController::class, 'store'])->name('admin.partners.store'); // Create & Update handles here
        Route::get('/delete/{id}', [PartnerController::class, 'destroy'])->name('admin.partners.delete');
    });

    Route::prefix('categories')->group(function () {
        Route::get('/psychology', [IndustryCategoryController::class, 'psychology'])->name('admin.categories.psychology');
        Route::get('/neuroscience', [IndustryCategoryController::class, 'neuroscience'])->name('admin.categories.neuroscience');
        Route::post('/section/store', [IndustryCategoryController::class, 'storeSection'])->name('admin.sections.store');
        Route::post('/category/store', [IndustryCategoryController::class, 'storeCategory'])->name('admin.categories.store');
        Route::get('/section/delete/{id}', [IndustryCategoryController::class, 'destroySection'])->name('admin.sections.delete');
        Route::get('/category/delete/{id}', [IndustryCategoryController::class, 'destroyCategory'])->name('admin.categories.delete');
    });

    Route::prefix('biotech')->group(function () {
        Route::get('/psychology', [BiotechController::class, 'psychology'])->name('admin.biotech.psychology');
        Route::get('/neuroscience', [BiotechController::class, 'neuroscience'])->name('admin.biotech.neuroscience');
        Route::post('/store', [BiotechController::class, 'store'])->name('admin.biotech.store');
        Route::get('/delete/{id}', [BiotechController::class, 'destroy'])->name('admin.biotech.delete');
    });

    Route::prefix('pharma')->group(function () {
        Route::get('/psychology', [IndustryPharmaController::class, 'psychology'])->name('admin.pharma.psychology');
        Route::get('/neuroscience', [IndustryPharmaController::class, 'neuroscience'])->name('admin.pharma.neuroscience');
        Route::post('/store', [IndustryPharmaController::class, 'store'])->name('admin.pharma.store');
        Route::get('/delete/{id}', [IndustryPharmaController::class, 'destroy'])->name('admin.pharma.delete');
    });

    Route::prefix('publications')->group(function () {
        Route::get('/psychology', [IndustryPublicationController::class, 'psychology'])->name('admin.publications.psychology');
        Route::get('/neuroscience', [IndustryPublicationController::class, 'neuroscience'])->name('admin.publications.neuroscience');
        Route::post('/store', [IndustryPublicationController::class, 'store'])->name('admin.publications.store');
        Route::get('/delete/{id}', [IndustryPublicationController::class, 'destroy'])->name('admin.publications.delete');
    });

    Route::prefix('pages')->group(function () {
        Route::get('/{slug}', [PagesController::class, 'edit'])->name('admin.pages.edit');
        Route::post('/update/{id}', [PagesController::class, 'update'])->name('admin.pages.update');
    });


    Route::post('admin/logout', [AdminAuthController::class, 'adminLogout'])->name('admin.logout');
});




// User Management Routes
// Route::get('/ni-az/users', [AdminAuthController::class, 'allUsers'])->name('users.index');
// Route::post('/ni-az/users/update', [AdminAuthController::class, 'userUpdate'])->name('users.update');
// Route::delete('/ni-az/users/{id}', [AdminAuthController::class, 'userDestroy'])->name('users.destroy');

// Route::get('/ni-az/groups', [AdminAuthController::class, 'groupIndex'])->name('groups.index');
// Route::post('/ni-az/groups/update', [AdminAuthController::class, 'groupUpdate'])->name('groups.update');
// Route::delete('/ni-az/groups/{id}', [AdminAuthController::class, 'groupDestroy'])->name('groups.destroy');

// Route::get('/ni-az/posts', [AdminAuthController::class, 'postIndex'])->name('posts.index');
// Route::post('/ni-az/posts/update', [AdminAuthController::class, 'postUpdate'])->name('posts.update');
// Route::delete('/ni-az/posts/{id}', [AdminAuthController::class, 'postDestroy'])->name('posts.destroy');


// Route::get('/ni-az/institutions', [AdminAuthController::class, 'institutionIndex'])->name('institutions.index');
// Route::put('/ni-az/institutions/{institution}', [AdminAuthController::class, 'institutionUpdate'])->name('institutions.update');

// Route::get('/ni-az/skills', [AdminAuthController::class, 'skillIndex'])->name('skills.index');
// Route::put('/ni-az/skills/{skill}', [AdminAuthController::class, 'skillUpdate'])->name('skills.update');
// Route::delete('/ni-az/skills/{skill}', [AdminAuthController::class, 'skillDestroy'])->name('skills.destroy');

// Route::get('/niaz-notifications/clear-all', function () {
//     DB::table('notifications')->delete();

//     return redirect()->back()->with('success', 'All notifications have been removed!');
// })->name('notifications.clearAll');

require __DIR__ . '/auth.php';
