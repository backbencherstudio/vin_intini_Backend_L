<?php

namespace App\Services;

use App\Enums\IndustryJobPostStatus;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\IndustryJobPostView;
use App\Models\IndustryProduct;
use App\Models\IndustryProductLike;
use App\Models\IndustryProductView;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IndustryAnalyticsService
{
    public function getJobOverviewAnalytics(int $industryId, string $period = 'last_6_months'): array
    {
        // JIT auto-sync: Expire past-deadline published jobs for this industry
        IndustryJobPost::where('industry_id', $industryId)
            ->where('status', IndustryJobPostStatus::PUBLISHED->value)
            ->whereNotNull('announcement_end_date')
            ->whereDate('announcement_end_date', '<', today())
            ->update(['status' => IndustryJobPostStatus::EXPIRED]);

        $now = Carbon::now();
        $startOfCurrentMonth = $now->copy()->startOfMonth();
        $endOfCurrentMonth = $now->copy()->endOfMonth();

        // subMonthNoOverflow ensures safe previous-month navigation on days 28-31
        $startOfLastMonth = $startOfCurrentMonth->copy()->subMonthNoOverflow();
        $endOfLastMonth = $startOfLastMonth->copy()->endOfMonth();

        // 1. KPI Cards
        $cards = $this->calculateKpiCards(
            $industryId,
            $startOfCurrentMonth,
            $endOfCurrentMonth,
            $startOfLastMonth,
            $endOfLastMonth
        );

        // 2. Trend Graph
        $graph = $this->calculateTrendGraph($industryId, $period);

        return [
            'period' => $period,
            'cards' => $cards,
            'graph' => $graph,
        ];
    }

    private function calculateKpiCards(
        int $industryId,
        Carbon $startCurrent,
        Carbon $endCurrent,
        Carbon $startLast,
        Carbon $endLast
    ): array {
        $jobPostIds = IndustryJobPost::where('industry_id', $industryId)->select('id');

        // Total Views
        $storedViewsCount = (int) IndustryJobPost::where('industry_id', $industryId)->sum('views_count');
        $logViewsCount = IndustryJobPostView::whereIn('industry_job_post_id', $jobPostIds)->count();
        $totalViews = max($storedViewsCount, $logViewsCount);

        $currentMonthViews = IndustryJobPostView::whereIn('industry_job_post_id', $jobPostIds)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->count();

        $lastMonthViews = IndustryJobPostView::whereIn('industry_job_post_id', $jobPostIds)
            ->whereBetween('created_at', [$startLast, $endLast])
            ->count();

        $viewsGrowth = $this->calculatePercentageChange($currentMonthViews, $lastMonthViews);

        // Total Applicants
        $totalApplicants = IndustryJobApplication::whereIn('job_id', $jobPostIds)->count();

        $currentMonthApplicants = IndustryJobApplication::whereIn('job_id', $jobPostIds)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->count();

        $lastMonthApplicants = IndustryJobApplication::whereIn('job_id', $jobPostIds)
            ->whereBetween('created_at', [$startLast, $endLast])
            ->count();

        $applicantsGrowth = $this->calculatePercentageChange($currentMonthApplicants, $lastMonthApplicants);

        // Active Positions (Current snapshot: published and not expired)
        $currentActivePositions = IndustryJobPost::where('industry_id', $industryId)
            ->where('status', IndustryJobPostStatus::PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('announcement_end_date')
                    ->orWhere('announcement_end_date', '>=', now()->toDateString());
            })
            ->count();

        // Active Positions at the end of last month (Historical comparison)
        $lastMonthActivePositions = IndustryJobPost::where('industry_id', $industryId)
            ->where('created_at', '<=', $endLast)
            ->where(function ($q) use ($endLast) {
                $q->where('status', IndustryJobPostStatus::PUBLISHED)
                    ->orWhere(function ($sub) use ($endLast) {
                        $sub->whereIn('status', [IndustryJobPostStatus::EXPIRED, IndustryJobPostStatus::ARCHIVE])
                            ->where('updated_at', '>', $endLast);
                    });
            })
            ->where(function ($q) use ($endLast) {
                $q->whereNull('announcement_end_date')
                    ->orWhere('announcement_end_date', '>=', $endLast->toDateString());
            })
            ->count();

        $positionsGrowth = $this->calculatePercentageChange($currentActivePositions, $lastMonthActivePositions);

        return [
            'job_views' => [
                'total' => (int) $totalViews,
                'growth_percentage' => $viewsGrowth,
                'is_positive' => $viewsGrowth >= 0,
            ],
            'total_applicants' => [
                'total' => (int) $totalApplicants,
                'growth_percentage' => $applicantsGrowth,
                'is_positive' => $applicantsGrowth >= 0,
            ],
            'active_positions' => [
                'total' => (int) $currentActivePositions,
                'growth_percentage' => $positionsGrowth,
                'is_positive' => $positionsGrowth >= 0,
            ],
        ];
    }

    private function calculateTrendGraph(int $industryId, string $period): array
    {
        $monthsCount = match ($period) {
            'last_3_months' => 3,
            'last_12_months' => 12,
            default => 6, // last_6_months
        };

        $endDate = Carbon::now()->endOfMonth();
        $startDate = Carbon::now()->startOfMonth()->subMonthsNoOverflow($monthsCount - 1);

        $jobPostIds = IndustryJobPost::where('industry_id', $industryId)->select('id');

        $driver = DB::connection()->getDriverName();
        $dateFormat = match ($driver) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        // Month-wise Views
        $viewsData = IndustryJobPostView::whereIn('industry_job_post_id', $jobPostIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("{$dateFormat} as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Month-wise Applicants
        $applicantsData = IndustryJobApplication::whereIn('job_id', $jobPostIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("{$dateFormat} as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // Month-wise Positions posted/activated
        $positionsData = IndustryJobPost::where('industry_id', $industryId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotIn('status', [
                IndustryJobPostStatus::DRAFT->value,
                IndustryJobPostStatus::REJECTED->value,
                IndustryJobPostStatus::PENDING->value,
            ])
            ->selectRaw("{$dateFormat} as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $labels = [];
        $viewsSeries = [];
        $applicantsSeries = [];
        $positionsSeries = [];

        for ($i = $monthsCount - 1; $i >= 0; $i--) {
            $monthCarbon = Carbon::now()->startOfMonth()->subMonthsNoOverflow($i);
            $monthKey = $monthCarbon->format('Y-m');

            $labels[] = $monthCarbon->format('M'); // e.g. Jan, Feb, Mar...
            $viewsSeries[] = (int) ($viewsData[$monthKey] ?? 0);
            $applicantsSeries[] = (int) ($applicantsData[$monthKey] ?? 0);
            $positionsSeries[] = (int) ($positionsData[$monthKey] ?? 0);
        }

        return [
            'labels' => $labels,
            'series' => [
                [
                    'name' => 'Total Applicant',
                    'key' => 'total_applicants',
                    'data' => $applicantsSeries,
                ],
                [
                    'name' => 'Job Views',
                    'key' => 'job_views',
                    'data' => $viewsSeries,
                ],
                [
                    'name' => 'Active Positions',
                    'key' => 'active_positions',
                    'data' => $positionsSeries,
                ],
            ],
        ];
    }

    private function calculatePercentageChange(float $current, float $previous): float
    {
        if ($previous == 0.0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function getAdvertisementAnalytics(int $industryId, string $period = 'weekly'): array
    {
        $now = Carbon::now();
        $startOfCurrentMonth = $now->copy()->startOfMonth();
        $endOfCurrentMonth = $now->copy()->endOfMonth();

        $startOfLastMonth = $startOfCurrentMonth->copy()->subMonthNoOverflow();
        $endOfLastMonth = $startOfLastMonth->copy()->endOfMonth();

        // 1. KPI Cards (Total Products, Active Products, Product Views, Total Like)
        $cards = $this->calculateAdvertisementCards(
            $industryId,
            $startOfCurrentMonth,
            $endOfCurrentMonth,
            $startOfLastMonth,
            $endOfLastMonth
        );

        // 2. Top Performing Products (Table on the left: Products, Views, Click / Likes)
        $topProducts = $this->getTopPerformingProducts($industryId);

        // 3. Advertisement Performance Graph (Chart on the right: Views vs Like across Weekly, Monthly, Yearly)
        $graph = $this->calculateAdvertisementPerformanceGraph($industryId, $period);

        return [
            'cards' => $cards,
            'top_performing_products' => $topProducts,
            'graph' => $graph,
        ];
    }

    private function calculateAdvertisementCards(
        int $industryId,
        Carbon $startCurrent,
        Carbon $endCurrent,
        Carbon $startLast,
        Carbon $endLast
    ): array {
        $productIds = IndustryProduct::where('industry_id', $industryId)->select('id');

        // Total Products
        $totalProducts = IndustryProduct::where('industry_id', $industryId)->count();
        $lastMonthProducts = IndustryProduct::where('industry_id', $industryId)
            ->where('created_at', '<=', $endLast)
            ->count();
        $productsGrowth = $this->calculatePercentageChange($totalProducts, $lastMonthProducts);

        // Active Products (status = 'active')
        $activeProducts = IndustryProduct::where('industry_id', $industryId)
            ->where('status', 'active')
            ->count();
        $lastMonthActiveProducts = IndustryProduct::where('industry_id', $industryId)
            ->where('status', 'active')
            ->where('created_at', '<=', $endLast)
            ->count();
        $activeGrowth = $this->calculatePercentageChange($activeProducts, $lastMonthActiveProducts);

        // Product Views
        $storedViewsCount = (int) IndustryProduct::where('industry_id', $industryId)->sum('views_count');
        $logViewsCount = IndustryProductView::whereIn('industry_product_id', $productIds)->count();
        $totalViews = max($storedViewsCount, $logViewsCount);

        $currentMonthViews = IndustryProductView::whereIn('industry_product_id', $productIds)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->count();
        $lastMonthViews = IndustryProductView::whereIn('industry_product_id', $productIds)
            ->whereBetween('created_at', [$startLast, $endLast])
            ->count();
        $viewsGrowth = $this->calculatePercentageChange($currentMonthViews, $lastMonthViews);

        // Total Likes
        $storedLikesCount = (int) IndustryProduct::where('industry_id', $industryId)->sum('likes_count');
        $logLikesCount = IndustryProductLike::whereIn('industry_product_id', $productIds)->count();
        $totalLikes = max($storedLikesCount, $logLikesCount);

        $currentMonthLikes = IndustryProductLike::whereIn('industry_product_id', $productIds)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->count();
        $lastMonthLikes = IndustryProductLike::whereIn('industry_product_id', $productIds)
            ->whereBetween('created_at', [$startLast, $endLast])
            ->count();
        $likesGrowth = $this->calculatePercentageChange($currentMonthLikes, $lastMonthLikes);

        return [
            'total_products' => [
                'total' => (int) $totalProducts,
                'growth_percentage' => $productsGrowth,
                'growth' => ($productsGrowth >= 0 ? "+{$productsGrowth}%" : "{$productsGrowth}%").' from last month',
                'is_positive' => $productsGrowth >= 0,
            ],
            'active_products' => [
                'total' => (int) $activeProducts,
                'growth_percentage' => $activeGrowth,
                'growth' => ($activeGrowth >= 0 ? "+{$activeGrowth}%" : "{$activeGrowth}%").' from last month',
                'is_positive' => $activeGrowth >= 0,
            ],
            'product_views' => [
                'total' => (int) $totalViews,
                'growth_percentage' => $viewsGrowth,
                'growth' => ($viewsGrowth >= 0 ? "+{$viewsGrowth}%" : "{$viewsGrowth}%").' from last month',
                'is_positive' => $viewsGrowth >= 0,
            ],
            'total_likes' => [
                'total' => (int) $totalLikes,
                'growth_percentage' => $likesGrowth,
                'growth' => ($likesGrowth >= 0 ? "+{$likesGrowth}%" : "{$likesGrowth}%").' from last month',
                'is_positive' => $likesGrowth >= 0,
            ],
        ];
    }

    private function getTopPerformingProducts(int $industryId, int $limit = 7): array
    {
        return IndustryProduct::where('industry_id', $industryId)
            ->select([
                'id',
                'product_id',
                'product_name',
                'views_count',
                'likes_count',
            ])
            ->orderByDesc('views_count')
            ->orderByDesc('likes_count')
            ->take($limit)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'product_id' => $product->product_id,
                    'product_name' => $product->product_name,
                    'views_count' => (int) $product->views_count,
                    'likes_count' => (int) $product->likes_count,
                ];
            })
            ->values()
            ->all();
    }

    private function calculateAdvertisementPerformanceGraph(int $industryId, string $period): array
    {
        $productIds = IndustryProduct::where('industry_id', $industryId)->select('id');
        $driver = DB::connection()->getDriverName();

        // 1. Weekly Chart (Monday to Sunday, 7 days)
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $dayDateFormat = match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
            default => "DATE_FORMAT(created_at, '%Y-%m-%d')",
        };

        $weeklyViewsData = IndustryProductView::whereIn('industry_product_id', $productIds)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw("{$dayDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $weeklyLikesData = IndustryProductLike::whereIn('industry_product_id', $productIds)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw("{$dayDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $weeklyLabels = [];
        $weeklyViewsSeries = [];
        $weeklyLikesSeries = [];

        for ($d = 0; $d < 7; $d++) {
            $dayCarbon = $startOfWeek->copy()->addDays($d);
            $key = $dayCarbon->format('Y-m-d');
            $weeklyLabels[] = $dayCarbon->format('D'); // Mon, Tue, Wed, Thu, Fri, Sat, Sun
            $weeklyViewsSeries[] = (int) ($weeklyViewsData[$key] ?? 0);
            $weeklyLikesSeries[] = (int) ($weeklyLikesData[$key] ?? 0);
        }

        // 2. Monthly Chart (Current year: Jan to Dec 12 months)
        $monthDateFormat = match ($driver) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $startOfCurrentYear = Carbon::now()->startOfYear()->startOfDay();
        $endOfCurrentYear = Carbon::now()->endOfYear()->endOfDay();

        $monthlyViewsData = IndustryProductView::whereIn('industry_product_id', $productIds)
            ->whereBetween('created_at', [$startOfCurrentYear, $endOfCurrentYear])
            ->selectRaw("{$monthDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $monthlyLikesData = IndustryProductLike::whereIn('industry_product_id', $productIds)
            ->whereBetween('created_at', [$startOfCurrentYear, $endOfCurrentYear])
            ->selectRaw("{$monthDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $monthlyLabels = [];
        $monthlyViewsSeries = [];
        $monthlyLikesSeries = [];

        $currentYearNum = Carbon::now()->year;
        for ($m = 1; $m <= 12; $m++) {
            $monthCarbon = Carbon::create($currentYearNum, $m, 1);
            $key = $monthCarbon->format('Y-m');
            $monthlyLabels[] = $monthCarbon->format('M'); // Jan, Feb, Mar, Apr, May, Jun, Jul, Aug, Sep, Oct, Nov, Dec
            $monthlyViewsSeries[] = (int) ($monthlyViewsData[$key] ?? 0);
            $monthlyLikesSeries[] = (int) ($monthlyLikesData[$key] ?? 0);
        }

        $allRanges = [
            'weekly' => [
                'labels' => $weeklyLabels,
                'series' => [
                    [
                        'name' => 'Views',
                        'key' => 'views',
                        'data' => $weeklyViewsSeries,
                    ],
                    [
                        'name' => 'Like',
                        'key' => 'likes',
                        'data' => $weeklyLikesSeries,
                    ],
                ],
                'total_views' => array_sum($weeklyViewsSeries),
                'total_likes' => array_sum($weeklyLikesSeries),
            ],
            'monthly' => [
                'labels' => $monthlyLabels,
                'series' => [
                    [
                        'name' => 'Views',
                        'key' => 'views',
                        'data' => $monthlyViewsSeries,
                    ],
                    [
                        'name' => 'Like',
                        'key' => 'likes',
                        'data' => $monthlyLikesSeries,
                    ],
                ],
                'total_views' => array_sum($monthlyViewsSeries),
                'total_likes' => array_sum($monthlyLikesSeries),
            ],
        ];

        // Active range selection (default: weekly)
        $activeRange = match ($period) {
            'monthly' => 'monthly',
            default => 'weekly',
        };

        $activeLabels = $allRanges[$activeRange]['labels'];
        $activeSeries = $allRanges[$activeRange]['series'];

        return [
            'title' => 'Advertisement Performance',
            'subtitle' => 'Views vs Like',
            'period' => $activeRange,
            'active' => $activeRange,
            'labels' => $activeLabels,
            'series' => $activeSeries,
            'ranges' => $allRanges,
        ];
    }

    public function getProfileInsightsAnalytics(int $industryId, string $period = 'last_6_months'): array
    {
        return ['cards' => [], 'graph' => ['labels' => [], 'series' => []]];
    }
}
