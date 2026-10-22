<?php

use App\Http\Controllers\Api\IndustryProductController;
use App\Http\Resources\IndustryProductResource;
use App\Models\AdCampaign;
use App\Models\Group;
use App\Models\IndustryProduct;
use App\Models\IndustryProductLike;
use App\Models\IndustrySections;
use App\Models\Post;
use App\Models\User;
use App\Services\Advertising\NewsfeedAdInjector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Boosting & Ads System — Developer Preview & Test Routes
|--------------------------------------------------------------------------
|
| These routes are temporary test/demonstration routes created to test and
| demonstrate the full ads & boosting lifecycle to Web and Mobile App
| developers using Blade views.
|
| When Web/App API integration is complete, this file and its associated
| preview Blade views (newsfeed-preview, product-feed-preview, partials)
| can be safely deleted without affecting core application routes.
|
*/

// =========================================================================
// 1. Home Newsfeed Preview (With Injected Ads & Cursor Pagination)
// =========================================================================
Route::get('/newsfeed-preview', function (Request $request) {
    $perPage = (int) $request->input('per_page', 8);
    $cursor = $request->input('cursor');

    $posts = Post::query()
        ->whereHas('user', fn ($q) => $q->whereNull('deleted_at'))
        ->with([
            'user:id,username,first_name,last_name,profile_image,title',
            'media',
        ])
        ->whereNotNull('description')
        ->where('description', '!=', '')
        ->orderByDesc('id')
        ->cursorPaginate(perPage: $perPage, cursor: $cursor);

    $feedItems = collect($posts->items())->map(function ($post) {
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
    // Client-side IAB viewable tracking: do not record impressions on blind page reload
    // For cursor batch of 8 posts, inject 1 ad per batch to maintain clean cadence
    $previewUser = auth()->user() ?? User::where('email', 'niaz.softvence@gmail.com')->first() ?? User::first();
    $maxAds = $perPage >= 15 ? 2 : 1;
    $seenCampaignIds = array_filter(array_map('intval', explode(',', (string) $request->input('seen_campaign_ids', ''))));
    $sessionSeen = session('newsfeed_seen_campaigns', []);
    if (empty($seenCampaignIds) && ! empty($sessionSeen)) {
        $seenCampaignIds = $sessionSeen;
    }

    $finalFeed = $injector->injectAdsIntoFeed(
        $feedItems,
        $previewUser,
        null,
        false,
        $maxAds,
        includeTestAnalytics: true,
        excludeCampaignIds: $seenCampaignIds
    );

    $nextCursor = $posts->nextCursor()?->encode();
    $prevCursor = $posts->previousCursor()?->encode();
    $hasMorePages = $posts->hasMorePages();

    $injectedCampaignIds = $finalFeed->where('is_sponsored', true)->pluck('ad_campaign_id')->filter()->values()->all();
    $mergedSeen = array_merge($seenCampaignIds, $injectedCampaignIds);
    $allSeenIds = array_values(array_reverse(array_unique(array_reverse($mergedSeen))));
    $allSeenIds = array_slice($allSeenIds, -30);
    session(['newsfeed_seen_campaigns' => $allSeenIds]);

    if ($request->ajax() || $request->wantsJson() || $request->boolean('load_more')) {
        $html = view('partials.newsfeed-post-items', ['feed' => $finalFeed])->render();

        return response()->json([
            'success' => true,
            'count' => $finalFeed->count(),
            'next_cursor' => $nextCursor,
            'prev_cursor' => $prevCursor,
            'has_more' => $hasMorePages,
            'seen_campaign_ids' => implode(',', $allSeenIds),
            'html' => $html,
        ]);
    }

    $sponsoredCards = $finalFeed->where('is_sponsored', true)->values();
    $campaignIds = $sponsoredCards->pluck('ad_campaign_id')->filter()->unique()->values();

    $activeCampaigns = AdCampaign::whereIn('id', $campaignIds)
        ->with(['boostPackage', 'industry', 'boostable'])
        ->get();

    $totalImpressions = (int) AdCampaign::where('status', 'active')->sum('total_impressions');
    $totalClicks = (int) AdCampaign::where('status', 'active')->sum('total_clicks');

    $suggestedGroups = Group::query()->latest('id')->take(3)->get();

    return view('newsfeed-preview', [
        'feed' => $finalFeed,
        'postsCount' => $posts->count(),
        'nextCursor' => $nextCursor,
        'prevCursor' => $prevCursor,
        'hasMorePages' => $hasMorePages,
        'seenCampaignIds' => implode(',', $allSeenIds),
        'perPage' => $perPage,
        'sponsoredCards' => $sponsoredCards,
        'activeCampaigns' => $activeCampaigns,
        'totalImpressions' => $totalImpressions,
        'totalClicks' => $totalClicks,
        'suggestedGroups' => $suggestedGroups,
    ]);
})->name('newsfeed.preview');

// =========================================================================
// 2. Industry Product Feed Preview (Boosted Products, Sections & Categories)
// =========================================================================
Route::get('/product-feed-preview', function (Request $request) {
    $networkType = $request->input('network_type', 'psychology');
    $industryType = $request->input('industry_type', 'biotechnology');
    $sectionId = $request->input('section_id');
    $categoryId = $request->input('category_id');
    $search = $request->input('search');
    $viewMode = $request->input('view', 'all');

    $networks = [
        'psychology' => 'Psychology Network',
        'neuroscience' => 'Neuroscience Network',
    ];

    $industries = [
        'biotechnology' => [
            'name' => 'Biotechnology',
            'desc' => 'Explore the latest biotech equipment releases advancing brain health research and treatment.',
            'icon' => 'biotech',
        ],
        'psychotropics' => [
            'name' => 'Psychotropics',
            'desc' => 'Explore the latest psychopharmacology and psychotropic releases advancing brain health research and treatment.',
            'icon' => 'psychotropics',
        ],
        'publications' => [
            'name' => 'Publications',
            'desc' => 'Explore the latest scientific literature, clinical papers, and published journal articles.',
            'icon' => 'publications',
        ],
    ];

    $sections = IndustrySections::query()
        ->where('network_type', $networkType)
        ->where('industry_type', $industryType)
        ->with(['IndustryCategory' => fn ($q) => $q->select('id', 'section_id', 'category_name')->orderBy('id')])
        ->orderBy('id')
        ->get();

    // Auto-expire products whose boost expired
    IndustryProduct::where('is_boosted', true)
        ->whereNotNull('boost_expires_at')
        ->where('boost_expires_at', '<=', now())
        ->update(['is_boosted' => false]);

    $currentUser = auth('api')->user() ?? auth('web')->user() ?? User::where('email', 'niaz.softvence@gmail.com')->first();

    // =========================================================================
    // CASE 1: SPECIFIC SECTION VIEW (Redirected from "See More" button or direct link)
    // =========================================================================
    $activeSection = null;
    $sectionPaginatedProducts = null;
    $totalSectionProducts = 0;

    if ($sectionId) {
        $activeSection = $sections->firstWhere('id', (int) $sectionId)
            ?? IndustrySections::with(['IndustryCategory' => fn ($q) => $q->select('id', 'section_id', 'category_name')->orderBy('id')])->find((int) $sectionId);

        if ($activeSection) {
            $secQuery = IndustryProduct::query()
                ->where('status', 'active')
                ->where('section_id', $activeSection->id)
                ->with(['creator', 'industry', 'section', 'category', 'activeCampaign.boostPackage'])
                ->feedOrder();

            if ($categoryId) {
                $secQuery->where('category_id', (int) $categoryId);
            }
            if ($search) {
                $secQuery->where(function ($q) use ($search) {
                    $q->where('product_name', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%")
                        ->orWhere('product_id', 'LIKE', "%{$search}%");
                });
            }

            $totalSectionProducts = (clone $secQuery)->count();
            $sectionPaginated = $secQuery->cursorPaginate(12)->withQueryString();

            $pIds = $sectionPaginated->pluck('id')->all();
            $likedIds = $currentUser
                ? IndustryProductLike::where('user_id', $currentUser->id)
                    ->whereIn('industry_product_id', $pIds)
                    ->pluck('industry_product_id')
                    ->flip()
                    ->all()
                : [];

            $transformedSec = $sectionPaginated->getCollection()->map(function ($p) use ($likedIds) {
                return (new IndustryProductResource($p, $likedIds))->resolve();
            });
            $sectionPaginated->setCollection($transformedSec);
            $sectionPaginatedProducts = $sectionPaginated;
        }
    }

    // =========================================================================
    // CASE 2: MAIN FEED (Section-wise Overview + All Products with Cursor Pagination)
    // =========================================================================
    $groupedBySection = [];
    foreach ($sections as $sec) {
        $secCount = IndustryProduct::where('status', 'active')
            ->where('section_id', $sec->id)
            ->count();

        $secTopProds = IndustryProduct::where('status', 'active')
            ->where('section_id', $sec->id)
            ->with(['creator', 'industry', 'section', 'category', 'activeCampaign.boostPackage'])
            ->feedOrder()
            ->take(6)
            ->get();

        $secProductIds = $secTopProds->pluck('id')->all();
        $secLikedIds = $currentUser
            ? IndustryProductLike::where('user_id', $currentUser->id)
                ->whereIn('industry_product_id', $secProductIds)
                ->pluck('industry_product_id')
                ->flip()
                ->all()
            : [];

        $transformedTop = $secTopProds->map(function ($p) use ($secLikedIds) {
            return (new IndustryProductResource($p, $secLikedIds))->resolve();
        });

        $groupedBySection[] = [
            'section' => $sec,
            'products' => $transformedTop,
            'total' => $secCount,
            'has_more' => $secCount > 6,
            'categories' => $sec->IndustryCategory,
        ];
    }

    // All Products View: Cursor-paginated (12 items per page)
    $allQuery = IndustryProduct::query()
        ->where('status', 'active')
        ->where('network_type', $networkType)
        ->where('industry_type', $industryType)
        ->with(['creator', 'industry', 'section', 'category', 'activeCampaign.boostPackage'])
        ->feedOrder();

    if ($search) {
        $allQuery->where(function ($q) use ($search) {
            $q->where('product_name', 'LIKE', "%{$search}%")
                ->orWhere('description', 'LIKE', "%{$search}%")
                ->orWhere('product_id', 'LIKE', "%{$search}%");
        });
    }

    $totalAllCount = (clone $allQuery)->count();
    $allPaginated = $allQuery->cursorPaginate(12)->withQueryString();

    $allProductIds = $allPaginated->pluck('id')->all();
    $allLikedIds = $currentUser
        ? IndustryProductLike::where('user_id', $currentUser->id)
            ->whereIn('industry_product_id', $allProductIds)
            ->pluck('industry_product_id')
            ->flip()
            ->all()
        : [];

    $transformedAll = $allPaginated->getCollection()->map(function ($p) use ($allLikedIds) {
        return (new IndustryProductResource($p, $allLikedIds))->resolve();
    });
    $allPaginated->setCollection($transformedAll);

    // Global Stats for monitor
    $totalImpressions = (int) AdCampaign::where('status', 'active')
        ->where('boostable_type', IndustryProduct::class)
        ->sum('total_impressions');

    $totalClicks = (int) AdCampaign::where('status', 'active')
        ->where('boostable_type', IndustryProduct::class)
        ->sum('total_clicks');

    $avgCtr = $totalImpressions > 0 ? round(($totalClicks / $totalImpressions) * 100, 1) : 0.0;

    $stats = [
        'total_active' => $totalAllCount,
        'boosted_count' => IndustryProduct::where('status', 'active')->where('network_type', $networkType)->where('industry_type', $industryType)->where('is_boosted', true)->count(),
        'new_release_count' => IndustryProduct::where('status', 'active')->where('network_type', $networkType)->where('industry_type', $industryType)->where('is_new_release', true)->count(),
        'organic_count' => IndustryProduct::where('status', 'active')->where('network_type', $networkType)->where('industry_type', $industryType)->where('is_boosted', false)->where('is_new_release', false)->count(),
        'total_impressions' => $totalImpressions,
        'totalClicks' => $totalClicks,
        'avg_ctr' => $avgCtr,
    ];

    return view('product-feed-preview', [
        'sections' => $sections,
        'groupedBySection' => $groupedBySection,
        'flatProducts' => $allPaginated->getCollection(),
        'allPaginated' => $allPaginated,
        'activeSection' => $activeSection,
        'sectionCategories' => $activeSection ? $activeSection->IndustryCategory : collect([]),
        'sectionPaginatedProducts' => $sectionPaginatedProducts,
        'totalSectionProducts' => $totalSectionProducts,
        'totalAllCount' => $totalAllCount,
        'stats' => $stats,
        'networks' => $networks,
        'industries' => $industries,
        'currentNetwork' => $networkType,
        'currentIndustry' => $industryType,
        'currentSection' => $sectionId,
        'currentCategory' => $categoryId,
        'search' => $search,
        'viewMode' => $viewMode,
    ]);
})->name('product-feed.preview');

// =========================================================================
// 3. Boosting UI/UX Interactive Showcase & API Documentation
// =========================================================================
Route::get('/boosting-showcase', function () {
    $path = base_path('docs/boosting_ui_showcase.html');
    if (file_exists($path)) {
        return response()->file($path, ['Content-Type' => 'text/html']);
    }

    abort(404, 'Boosting showcase documentation file not found.');
})->name('boosting.showcase');

// =========================================================================
// 4. Product Details & Like Simulation Endpoints (for Blade Preview)
// =========================================================================
Route::get('/preview/products/{id}/details', function (Request $request, int|string $id) {
    return app(IndustryProductController::class)->show($request, $id);
})->name('preview.product.details');

Route::post('/preview/products/{id}/like', function (Request $request, int|string $id) {
    return app(IndustryProductController::class)->toggleLike($request, $id);
})->name('preview.product.like');
