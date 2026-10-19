<?php

use App\Http\Controllers\Admin\AcademiaAdminController;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\BiotechController;
use App\Http\Controllers\Admin\IndustryCategoryController;
use App\Http\Controllers\Admin\IndustryPharmaController;
use App\Http\Controllers\Admin\IndustryPublicationController;
use App\Http\Controllers\Admin\InstitutionReportController;
use App\Http\Controllers\Admin\PagesController;
use App\Http\Controllers\Admin\PartnerController;
use App\Http\Controllers\Api\SecuritySettingsController;
use App\Http\Resources\IndustryProductResource;
use App\Models\AdCampaign;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\Post;
use App\Models\SocialAccount;
use App\Models\User;
use App\Services\Advertising\NewsfeedAdInjector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

Route::redirect('/', '/welcome');
Route::get('/welcome', function () {
    return view('welcome');
})->name('welcome');

Route::get('/payment/success', function () {
    return view('payment-success');
})->name('payment.success');

Route::get('/payment/cancel', function () {
    return view('payment-cancel');
})->name('payment.cancel');

Route::get('/subscription-test', function () {
    return view('subscription-test');
})->name('subscription.test');

Route::get('/newsfeed-preview', function () {
    $posts = Post::query()
        ->whereHas('user', fn ($q) => $q->whereNull('deleted_at'))
        ->with([
            'user:id,username,first_name,last_name,profile_image,title',
            'media',
        ])
        ->whereNotNull('description')
        ->where('description', '!=', '')
        ->latest('id')
        ->take(15)
        ->get();

    $feedItems = collect($posts)->map(function ($post) {
        return [
            'id' => $post->id,
            'is_sponsored' => false,
            'user' => $post->user,
            'description' => $post->description,
            'visibility' => $post->visibility ?? 'public',
            'total_like' => $post->total_like ?? 0,
            'total_comment' => $post->total_comment ?? 0,
            'media' => $post->media,
            'created_at' => $post->created_at,
        ];
    });

    $injector = app(NewsfeedAdInjector::class);
    $finalFeed = $injector->injectAdsIntoFeed($feedItems, null);

    return view('newsfeed-preview', [
        'feed' => $finalFeed,
        'postsCount' => $posts->count(),
    ]);
})->name('newsfeed.preview');

Route::get('/product-feed-preview', function (Request $request) {
    $networkType = $request->input('network_type', 'psychology');
    $industryType = $request->input('industry_type', 'biotechnology');
    $sectionId = $request->input('section_id');
    $categoryId = $request->input('category_id');
    $search = $request->input('search');
    $viewMode = $request->input('view', 'grouped');

    $sections = IndustrySections::query()
        ->where('network_type', $networkType)
        ->where('industry_type', $industryType)
        ->with(['IndustryCategory' => fn ($q) => $q->select('id', 'section_id', 'category_name')->orderBy('category_name')])
        ->orderBy('id')
        ->get();

    $query = IndustryProduct::query()
        ->where('status', 'active')
        ->where('network_type', $networkType)
        ->where('industry_type', $industryType)
        ->with(['creator', 'industry', 'section', 'category', 'activeCampaign'])
        ->feedOrder();

    if ($sectionId) {
        $query->where('section_id', $sectionId);
    }
    if ($categoryId) {
        $query->where('category_id', $categoryId);
    }
    if ($search) {
        $query->where(function ($q) use ($search) {
            $q->where('product_name', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%");
        });
    }

    $allProducts = $query->take(30)->get();

    $transformedProducts = $allProducts->map(function ($p) {
        return (new IndustryProductResource($p))->resolve();
    });

    $groupedBySection = [];
    foreach ($sections as $sec) {
        $secProducts = $transformedProducts->where('section_id', $sec->id)->values();
        $groupedBySection[] = [
            'section' => $sec,
            'products' => $secProducts,
            'total' => $secProducts->count(),
        ];
    }

    $totalImpressions = (int) AdCampaign::where('status', 'active')
        ->where('boostable_type', IndustryProduct::class)
        ->sum('total_impressions');

    $totalClicks = (int) AdCampaign::where('status', 'active')
        ->where('boostable_type', IndustryProduct::class)
        ->sum('total_clicks');

    $avgCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 1) : 0.0;

    $stats = [
        'total_active' => $allProducts->count(),
        'boosted_count' => $allProducts->filter(fn ($p) => $p->isBoosted())->count(),
        'new_release_count' => $allProducts->filter(fn ($p) => $p->isNewRelease())->count(),
        'organic_count' => $allProducts->filter(fn ($p) => ! $p->isBoosted() && ! $p->isNewRelease())->count(),
        'total_impressions' => $totalImpressions,
        'total_clicks' => $totalClicks,
        'avg_ctr' => $avgCtr,
    ];

    return view('product-feed-preview', [
        'sections' => $sections,
        'groupedBySection' => $groupedBySection,
        'flatProducts' => $transformedProducts,
        'stats' => $stats,
        'currentNetwork' => $networkType,
        'currentIndustry' => $industryType,
        'currentSection' => $sectionId,
        'currentCategory' => $categoryId,
        'search' => $search,
        'viewMode' => $viewMode,
    ]);
})->name('product-feed.preview');

Route::get('/clear', function () {
    Artisan::call('optimize:clear');

    return 'Cleared!';
});

Route::middleware('guest:web')->group(function () {
    // Admin Routes (Temporary admin dashboard routes)
    Route::get('admin/login', [AdminAuthController::class, 'showLoginForm'])->name('admin.login');
    Route::post('admin/login-submit', [AdminAuthController::class, 'adminLogin'])->name('admin.login.submit');
});

// Security Routes when user clicks on the email link for suspicious login activities
Route::get('/security/email-resolve/{id}', [SecuritySettingsController::class, 'resolveFromEmail'])
    ->name('security.email-resolve')->middleware('signed');
// Security Routes when user clicks on the email link for suspicious login activities
Route::post('/security/update-password', [SecuritySettingsController::class, 'updatePasswordFromAlert'])
    ->name('security.update-password')->middleware('signed');

Route::group(['prefix' => 'admin', 'middleware' => ['auth:web', 'role:admin', 'active_session']], function () {

    Route::get('/user-management', [AdminAuthController::class, 'userManagement'])->name('admin.user.management');
    // Institution Report Routes
    Route::get('/institution-report', [InstitutionReportController::class, 'institutionReport'])->name('institution.index');
    Route::get('/institution/{id}/students', [InstitutionReportController::class, 'showStudents'])->name('institution.students');

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

    Route::post('/logout', [AdminAuthController::class, 'adminLogout'])->name('admin.logout');
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

// generate usernames for users who don't have one
// Route::get('/generate-usernames', function () {
//     $users = User::whereNull('username')->get();
//     $count = 0;

//     foreach ($users as $user) {
//         $baseSlug = Str::slug($user->first_name . ' ' . $user->last_name, '-');

//         if (empty($baseSlug)) {
//             $baseSlug = Str::slug(explode('@', $user->email)[0], '-');
//         }

//         $username = $baseSlug;
//         $counter = 1;

//         while (User::where('username', $username)->exists()) {
//             $username = $baseSlug . '-' . $counter;
//             $counter++;
//         }

//         $user->username = $username;
//         $user->save();
//         $count++;
//     }

//     return response()->json(['message' => "$count users updated with usernames!"]);
// });

// social users don't have passwords, so we need to set has_password to false for those users
// Route::get('/fix-social-passwords', function () {
//     $socialUserIds = SocialAccount::pluck('user_id')->unique()->toArray();

//     if (empty($socialUserIds)) {
//         return response()->json(['message' => "No social accounts found!"]);
//     }

//     $updatedCount = User::whereIn('id', $socialUserIds)
//         ->where('has_password', true)
//         ->update(['has_password' => false]);

//     return response()->json([
//         'status' => true,
//         'message' => "Successfully updated $updatedCount social users!",
//         'total_social_users' => count($socialUserIds)
//     ]);
// });

require __DIR__.'/auth.php';
