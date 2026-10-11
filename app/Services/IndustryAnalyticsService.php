<?php

namespace App\Services;

use App\Enums\IndustryJobPostStatus;
use App\Models\Comment;
use App\Models\Industry;
use App\Models\IndustryFollow;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\IndustryJobPostView;
use App\Models\IndustryProduct;
use App\Models\IndustryProductLike;
use App\Models\IndustryProductView;
use App\Models\IndustryProfileView;
use App\Models\PostIndustry;
use App\Models\PostLike;
use App\Models\Publication;
use App\Models\PublicationLike;
use App\Models\PublicationView;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IndustryAnalyticsService
{
    public function getJobOverviewAnalytics(int $industryId, string $period = 'weekly'): array
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

        // 2. Trend Graph (Jobs Overview Graph)
        $graph = $this->calculateTrendGraph($industryId, $period);

        return [
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
                'growth_percentage' => abs($viewsGrowth),
                'growth' => sprintf('%s%s%% from last month', $viewsGrowth >= 0 ? '+' : '-', abs($viewsGrowth)),
                'is_positive' => $viewsGrowth >= 0,
            ],
            'total_applicants' => [
                'total' => (int) $totalApplicants,
                'growth_percentage' => abs($applicantsGrowth),
                'growth' => sprintf('%s%s%% from last month', $applicantsGrowth >= 0 ? '+' : '-', abs($applicantsGrowth)),
                'is_positive' => $applicantsGrowth >= 0,
            ],
            'active_positions' => [
                'total' => (int) $currentActivePositions,
                'growth_percentage' => abs($positionsGrowth),
                'growth' => sprintf('%s%s%% from last month', $positionsGrowth >= 0 ? '+' : '-', abs($positionsGrowth)),
                'is_positive' => $positionsGrowth >= 0,
            ],
        ];
    }

    private function calculateTrendGraph(int $industryId, string $period): array
    {
        $jobPostIds = IndustryJobPost::where('industry_id', $industryId)->select('id');
        $driver = DB::connection()->getDriverName();

        // 1. Weekly Chart (Monday to Sunday, 7 days)
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $dayDateFormat = match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
            default => "DATE_FORMAT(created_at, '%Y-%m-%d')",
        };

        $weeklyViewsData = IndustryJobPostView::whereIn('industry_job_post_id', $jobPostIds)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw("{$dayDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $weeklyApplicantsData = IndustryJobApplication::whereIn('job_id', $jobPostIds)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw("{$dayDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $weeklyPositionsData = IndustryJobPost::where('industry_id', $industryId)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->whereNotIn('status', [
                IndustryJobPostStatus::DRAFT->value,
                IndustryJobPostStatus::REJECTED->value,
                IndustryJobPostStatus::PENDING->value,
            ])
            ->selectRaw("{$dayDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $weeklyChartData = [];

        for ($d = 0; $d < 7; $d++) {
            $dayCarbon = $startOfWeek->copy()->addDays($d);
            $key = $dayCarbon->format('Y-m-d');

            $weeklyChartData[] = [
                'label' => $dayCarbon->format('D'), // Mon, Tue, Wed, Thu, Fri, Sat, Sun
                'total_applicants' => (int) ($weeklyApplicantsData[$key] ?? 0),
                'job_views' => (int) ($weeklyViewsData[$key] ?? 0),
                'active_positions' => (int) ($weeklyPositionsData[$key] ?? 0),
            ];
        }

        // 2. Monthly Chart (Current year: Jan to Dec 12 months)
        $monthDateFormat = match ($driver) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $startOfCurrentYear = Carbon::now()->startOfYear()->startOfDay();
        $endOfCurrentYear = Carbon::now()->endOfYear()->endOfDay();

        $monthlyViewsData = IndustryJobPostView::whereIn('industry_job_post_id', $jobPostIds)
            ->whereBetween('created_at', [$startOfCurrentYear, $endOfCurrentYear])
            ->selectRaw("{$monthDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $monthlyApplicantsData = IndustryJobApplication::whereIn('job_id', $jobPostIds)
            ->whereBetween('created_at', [$startOfCurrentYear, $endOfCurrentYear])
            ->selectRaw("{$monthDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $monthlyPositionsData = IndustryJobPost::where('industry_id', $industryId)
            ->whereBetween('created_at', [$startOfCurrentYear, $endOfCurrentYear])
            ->whereNotIn('status', [
                IndustryJobPostStatus::DRAFT->value,
                IndustryJobPostStatus::REJECTED->value,
                IndustryJobPostStatus::PENDING->value,
            ])
            ->selectRaw("{$monthDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $monthlyChartData = [];

        $currentYearNum = Carbon::now()->year;
        for ($m = 1; $m <= 12; $m++) {
            $monthCarbon = Carbon::create($currentYearNum, $m, 1);
            $key = $monthCarbon->format('Y-m');

            $monthlyChartData[] = [
                'label' => $monthCarbon->format('M'), // Jan, Feb, Mar, Apr, May, Jun, Jul, Aug, Sep, Oct, Nov, Dec
                'total_applicants' => (int) ($monthlyApplicantsData[$key] ?? 0),
                'job_views' => (int) ($monthlyViewsData[$key] ?? 0),
                'active_positions' => (int) ($monthlyPositionsData[$key] ?? 0),
            ];
        }

        // Active range selection (default: weekly)
        $activeRange = match ($period) {
            'monthly' => 'monthly',
            default => 'weekly',
        };

        return [
            'title' => 'Jobs Overview Graph',
            'subtitle' => 'Applications Over Time',
            'period' => $activeRange,
            'active' => $activeRange,
            'weekly' => $weeklyChartData,
            'monthly' => $monthlyChartData,
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

        $weeklyChartData = [];

        for ($d = 0; $d < 7; $d++) {
            $dayCarbon = $startOfWeek->copy()->addDays($d);
            $key = $dayCarbon->format('Y-m-d');

            $weeklyChartData[] = [
                'label' => $dayCarbon->format('D'), // Mon, Tue, Wed, Thu, Fri, Sat, Sun
                'views' => (int) ($weeklyViewsData[$key] ?? 0),
                'likes' => (int) ($weeklyLikesData[$key] ?? 0),
            ];
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

        $monthlyChartData = [];

        $currentYearNum = Carbon::now()->year;
        for ($m = 1; $m <= 12; $m++) {
            $monthCarbon = Carbon::create($currentYearNum, $m, 1);
            $key = $monthCarbon->format('Y-m');

            $monthlyChartData[] = [
                'label' => $monthCarbon->format('M'), // Jan, Feb, Mar, Apr, May, Jun, Jul, Aug, Sep, Oct, Nov, Dec
                'views' => (int) ($monthlyViewsData[$key] ?? 0),
                'likes' => (int) ($monthlyLikesData[$key] ?? 0),
            ];
        }

        // Active range selection (default: weekly)
        $activeRange = match ($period) {
            'monthly' => 'monthly',
            default => 'weekly',
        };

        return [
            'title' => 'Advertisement Performance',
            'subtitle' => 'Views vs Like',
            'period' => $activeRange,
            'active' => $activeRange,
            'weekly' => $weeklyChartData,
            'monthly' => $monthlyChartData,
        ];
    }

    public function getProfileInsightsAnalytics(int $industryId, string $period = 'weekly'): array
    {
        $now = Carbon::now();
        $startOfCurrentMonth = $now->copy()->startOfMonth();
        $endOfCurrentMonth = $now->copy()->endOfMonth();

        // subMonthNoOverflow ensures safe previous-month navigation on days 28-31
        $startOfLastMonth = $startOfCurrentMonth->copy()->subMonthNoOverflow();
        $endOfLastMonth = $startOfLastMonth->copy()->endOfMonth();

        // 1. KPI Cards: New Followers, Total Followers, Profile Visits
        $cards = $this->calculateProfileInsightsCards(
            $industryId,
            $startOfCurrentMonth,
            $endOfCurrentMonth,
            $startOfLastMonth,
            $endOfLastMonth
        );

        // 2. Trend Graph (Profile Insights Graph)
        $graph = $this->calculateProfileInsightsGraph($industryId, $period);

        return [
            'cards' => $cards,
            'graph' => $graph,
        ];
    }

    private function calculateProfileInsightsCards(
        int $industryId,
        Carbon $startCurrent,
        Carbon $endCurrent,
        Carbon $startLast,
        Carbon $endLast
    ): array {
        // 1. New Followers (Current month vs Last month)
        $currentMonthNewFollowers = IndustryFollow::where('industry_id', $industryId)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->count();

        $lastMonthNewFollowers = IndustryFollow::where('industry_id', $industryId)
            ->whereBetween('created_at', [$startLast, $endLast])
            ->count();

        $newFollowersGrowth = $this->calculatePercentageChange($currentMonthNewFollowers, $lastMonthNewFollowers);

        // 2. Post Engagement (Likes + Comments on industry posts: Current month vs Last month)
        $postIds = $this->getIndustryPostIdsQuery($industryId);

        $currentMonthLikes = PostLike::whereIn('post_id', $postIds)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->count();

        $currentMonthComments = Comment::whereIn('post_id', $postIds)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->count();

        $currentMonthEngagement = $currentMonthLikes + $currentMonthComments;

        $lastMonthLikes = PostLike::whereIn('post_id', $postIds)
            ->whereBetween('created_at', [$startLast, $endLast])
            ->count();

        $lastMonthComments = Comment::whereIn('post_id', $postIds)
            ->whereBetween('created_at', [$startLast, $endLast])
            ->count();

        $lastMonthEngagement = $lastMonthLikes + $lastMonthComments;

        $engagementGrowth = $this->calculatePercentageChange($currentMonthEngagement, $lastMonthEngagement);

        // 3. Profile Visits (Lifetime total vs Growth this month vs last month)
        $storedViewsCount = (int) Industry::where('id', $industryId)->value('views_count');
        $logViewsCount = IndustryProfileView::where('industry_id', $industryId)->count();
        $totalProfileVisits = max($storedViewsCount, $logViewsCount);

        $currentMonthVisits = IndustryProfileView::where('industry_id', $industryId)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->count();

        $lastMonthVisits = IndustryProfileView::where('industry_id', $industryId)
            ->whereBetween('created_at', [$startLast, $endLast])
            ->count();

        $visitsGrowth = $this->calculatePercentageChange($currentMonthVisits, $lastMonthVisits);

        return [
            'new_followers' => [
                'total' => (int) $currentMonthNewFollowers,
                'growth_percentage' => abs($newFollowersGrowth),
                'growth' => sprintf('%s%s%% from last month', $newFollowersGrowth >= 0 ? '+' : '-', abs($newFollowersGrowth)),
                'is_positive' => $newFollowersGrowth >= 0,
            ],
            'post_engagement' => [
                'total' => (int) $currentMonthEngagement,
                'growth_percentage' => abs($engagementGrowth),
                'growth' => sprintf('%s%s%% from last month', $engagementGrowth >= 0 ? '+' : '-', abs($engagementGrowth)),
                'is_positive' => $engagementGrowth >= 0,
            ],
            'profile_visits' => [
                'total' => (int) $totalProfileVisits,
                'growth_percentage' => abs($visitsGrowth),
                'growth' => sprintf('%s%s%% from last month', $visitsGrowth >= 0 ? '+' : '-', abs($visitsGrowth)),
                'is_positive' => $visitsGrowth >= 0,
            ],
        ];
    }

    private function calculateProfileInsightsGraph(int $industryId, string $period): array
    {
        $driver = DB::connection()->getDriverName();

        $dayDateFormat = match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
            default => "DATE_FORMAT(created_at, '%Y-%m-%d')",
        };

        $monthDateFormat = match ($driver) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $postIds = $this->getIndustryPostIdsQuery($industryId);

        // 1. Weekly Chart (Monday to Sunday, 7 days)
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $weeklyFollowersData = IndustryFollow::where('industry_id', $industryId)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw("{$dayDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $weeklyLikesData = PostLike::whereIn('post_id', $postIds)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw("{$dayDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $weeklyCommentsData = Comment::whereIn('post_id', $postIds)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw("{$dayDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $weeklyVisitsData = IndustryProfileView::where('industry_id', $industryId)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw("{$dayDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $weeklyChartData = [];

        for ($d = 0; $d < 7; $d++) {
            $dayCarbon = $startOfWeek->copy()->addDays($d);
            $key = $dayCarbon->format('Y-m-d');
            $dayLikes = (int) ($weeklyLikesData[$key] ?? 0);
            $dayComments = (int) ($weeklyCommentsData[$key] ?? 0);

            $weeklyChartData[] = [
                'label' => $dayCarbon->format('D'), // Mon, Tue, Wed, Thu, Fri, Sat, Sun
                'new_followers' => (int) ($weeklyFollowersData[$key] ?? 0),
                'post_engagement' => $dayLikes + $dayComments,
                'profile_visits' => (int) ($weeklyVisitsData[$key] ?? 0),
            ];
        }

        // 2. Monthly Chart (Current year: Jan to Dec 12 months)
        $startOfCurrentYear = Carbon::now()->startOfYear()->startOfDay();
        $endOfCurrentYear = Carbon::now()->endOfYear()->endOfDay();

        $monthlyFollowersData = IndustryFollow::where('industry_id', $industryId)
            ->whereBetween('created_at', [$startOfCurrentYear, $endOfCurrentYear])
            ->selectRaw("{$monthDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $monthlyLikesData = PostLike::whereIn('post_id', $postIds)
            ->whereBetween('created_at', [$startOfCurrentYear, $endOfCurrentYear])
            ->selectRaw("{$monthDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $monthlyCommentsData = Comment::whereIn('post_id', $postIds)
            ->whereBetween('created_at', [$startOfCurrentYear, $endOfCurrentYear])
            ->selectRaw("{$monthDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $monthlyVisitsData = IndustryProfileView::where('industry_id', $industryId)
            ->whereBetween('created_at', [$startOfCurrentYear, $endOfCurrentYear])
            ->selectRaw("{$monthDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $monthlyChartData = [];

        $currentYearNum = Carbon::now()->year;
        for ($m = 1; $m <= 12; $m++) {
            $monthCarbon = Carbon::create($currentYearNum, $m, 1);
            $key = $monthCarbon->format('Y-m');
            $monthLikes = (int) ($monthlyLikesData[$key] ?? 0);
            $monthComments = (int) ($monthlyCommentsData[$key] ?? 0);

            $monthlyChartData[] = [
                'label' => $monthCarbon->format('M'), // Jan, Feb, Mar, Apr, May, Jun, Jul, Aug, Sep, Oct, Nov, Dec
                'new_followers' => (int) ($monthlyFollowersData[$key] ?? 0),
                'post_engagement' => $monthLikes + $monthComments,
                'profile_visits' => (int) ($monthlyVisitsData[$key] ?? 0),
            ];
        }

        // Active range selection (default: weekly)
        $activeRange = match ($period) {
            'monthly' => 'monthly',
            default => 'weekly',
        };

        return [
            'title' => 'Profile Insights',
            'subtitle' => 'Profile Insights Per Month',
            'period' => $activeRange,
            'active' => $activeRange,
            'weekly' => $weeklyChartData,
            'monthly' => $monthlyChartData,
        ];
    }

    /**
     * Query builder for post IDs that strictly belong to the given industry,
     * explicitly excluding group posts and regular personal user profile posts.
     */
    private function getIndustryPostIdsQuery(int $industryId)
    {
        return PostIndustry::where('industry_id', $industryId)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('post_groups')
                    ->whereColumn('post_groups.post_id', 'post_industry.post_id');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('posts')
                    ->whereColumn('posts.id', 'post_industry.post_id')
                    ->where('posts.visibility', 'groups');
            })
            ->select('post_id');
    }

    public function getPublicationInsightsAnalytics(int $industryId, string $period = 'weekly'): array
    {
        $now = Carbon::now();
        $startOfCurrentMonth = $now->copy()->startOfMonth();
        $endOfCurrentMonth = $now->copy()->endOfMonth();

        $startOfLastMonth = $startOfCurrentMonth->copy()->subMonthNoOverflow();
        $endOfLastMonth = $startOfLastMonth->copy()->endOfMonth();

        // 1. KPI Cards (Total Publications, Active Publications, Publications Views, Total Publications Like)
        $cards = $this->calculatePublicationCards(
            $industryId,
            $startOfCurrentMonth,
            $endOfCurrentMonth,
            $startOfLastMonth,
            $endOfLastMonth
        );

        // 2. Performance Graph (Views vs Likes across Weekly, Monthly)
        $graph = $this->calculatePublicationPerformanceGraph($industryId, $period);

        return [
            'cards' => $cards,
            'graph' => $graph,
        ];
    }

    private function calculatePublicationCards(
        int $industryId,
        Carbon $startCurrent,
        Carbon $endCurrent,
        Carbon $startLast,
        Carbon $endLast
    ): array {
        $publicationIds = Publication::where('industry_id', $industryId)->select('id');

        // Total Publications
        $totalPublications = Publication::where('industry_id', $industryId)->count();
        $lastMonthPublications = Publication::where('industry_id', $industryId)
            ->where('created_at', '<=', $endLast)
            ->count();
        $publicationsGrowth = $this->calculatePercentageChange($totalPublications, $lastMonthPublications);

        // Active Publications (status = 'active')
        $activePublications = Publication::where('industry_id', $industryId)
            ->where('status', 'active')
            ->count();
        $lastMonthActive = Publication::where('industry_id', $industryId)
            ->where('status', 'active')
            ->where('created_at', '<=', $endLast)
            ->count();
        $activeGrowth = $this->calculatePercentageChange($activePublications, $lastMonthActive);

        // Publications Views
        $storedViewsCount = (int) Publication::where('industry_id', $industryId)->sum('views_count');
        $logViewsCount = PublicationView::whereIn('publication_id', $publicationIds)->count();
        $totalViews = max($storedViewsCount, $logViewsCount);

        $currentMonthViews = PublicationView::whereIn('publication_id', $publicationIds)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->count();
        $lastMonthViews = PublicationView::whereIn('publication_id', $publicationIds)
            ->whereBetween('created_at', [$startLast, $endLast])
            ->count();
        $viewsGrowth = $this->calculatePercentageChange($currentMonthViews, $lastMonthViews);

        // Total Publications Like
        $storedLikesCount = (int) Publication::where('industry_id', $industryId)->sum('likes_count');
        $logLikesCount = PublicationLike::whereIn('publication_id', $publicationIds)->count();
        $totalLikes = max($storedLikesCount, $logLikesCount);

        $currentMonthLikes = PublicationLike::whereIn('publication_id', $publicationIds)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->count();
        $lastMonthLikes = PublicationLike::whereIn('publication_id', $publicationIds)
            ->whereBetween('created_at', [$startLast, $endLast])
            ->count();
        $likesGrowth = $this->calculatePercentageChange($currentMonthLikes, $lastMonthLikes);

        return [
            'total_publications' => [
                'total' => (int) $totalPublications,
                'growth_percentage' => abs($publicationsGrowth),
                'growth' => sprintf('%s%s%% from last month', $publicationsGrowth >= 0 ? '+' : '-', abs($publicationsGrowth)),
                'is_positive' => $publicationsGrowth >= 0,
            ],
            'active_publications' => [
                'total' => (int) $activePublications,
                'growth_percentage' => abs($activeGrowth),
                'growth' => sprintf('%s%s%% from last month', $activeGrowth >= 0 ? '+' : '-', abs($activeGrowth)),
                'is_positive' => $activeGrowth >= 0,
            ],
            'publications_views' => [
                'total' => (int) $totalViews,
                'growth_percentage' => abs($viewsGrowth),
                'growth' => sprintf('%s%s%% from last month', $viewsGrowth >= 0 ? '+' : '-', abs($viewsGrowth)),
                'is_positive' => $viewsGrowth >= 0,
            ],
            'total_publications_like' => [
                'total' => (int) $totalLikes,
                'growth_percentage' => abs($likesGrowth),
                'growth' => sprintf('%s%s%% from last month', $likesGrowth >= 0 ? '+' : '-', abs($likesGrowth)),
                'is_positive' => $likesGrowth >= 0,
            ],
        ];
    }

    private function calculatePublicationPerformanceGraph(int $industryId, string $period): array
    {
        $driver = DB::connection()->getDriverName();

        $dayDateFormat = match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
            default => "DATE_FORMAT(created_at, '%Y-%m-%d')",
        };

        $publicationIds = Publication::where('industry_id', $industryId)->select('id');

        // 1. Weekly Chart (Monday to Sunday, 7 days)
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $weeklyViewsData = PublicationView::whereIn('publication_id', $publicationIds)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw("{$dayDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $weeklyLikesData = PublicationLike::whereIn('publication_id', $publicationIds)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw("{$dayDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $weeklyChartData = [];

        for ($d = 0; $d < 7; $d++) {
            $dayCarbon = $startOfWeek->copy()->addDays($d);
            $key = $dayCarbon->format('Y-m-d');
            $viewsCount = (int) ($weeklyViewsData[$key] ?? 0);
            $likesCount = (int) ($weeklyLikesData[$key] ?? 0);

            $weeklyChartData[] = [
                'label' => $dayCarbon->format('D'), // Mon, Tue, Wed, Thu, Fri, Sat, Sun
                'publications_views' => $viewsCount,
                'publications_likes' => $likesCount,
            ];
        }

        // 2. Monthly Chart (Current year: Jan to Dec 12 months)
        $monthDateFormat = match ($driver) {
            'sqlite' => "strftime('%Y-%m', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM')",
            default => "DATE_FORMAT(created_at, '%Y-%m')",
        };

        $startOfCurrentYear = Carbon::now()->startOfYear()->startOfDay();
        $endOfCurrentYear = Carbon::now()->endOfYear()->endOfDay();

        $monthlyViewsData = PublicationView::whereIn('publication_id', $publicationIds)
            ->whereBetween('created_at', [$startOfCurrentYear, $endOfCurrentYear])
            ->selectRaw("{$monthDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $monthlyLikesData = PublicationLike::whereIn('publication_id', $publicationIds)
            ->whereBetween('created_at', [$startOfCurrentYear, $endOfCurrentYear])
            ->selectRaw("{$monthDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $monthlyChartData = [];

        $currentYearNum = Carbon::now()->year;
        for ($m = 1; $m <= 12; $m++) {
            $monthCarbon = Carbon::create($currentYearNum, $m, 1);
            $key = $monthCarbon->format('Y-m');
            $viewsCount = (int) ($monthlyViewsData[$key] ?? 0);
            $likesCount = (int) ($monthlyLikesData[$key] ?? 0);

            $monthlyChartData[] = [
                'label' => $monthCarbon->format('M'), // Jan, Feb, Mar, Apr, May, Jun, Jul, Aug, Sep, Oct, Nov, Dec
                'publications_views' => $viewsCount,
                'publications_likes' => $likesCount,
            ];
        }

        // Active range selection (default: weekly)
        $activeRange = match ($period) {
            'monthly' => 'monthly',
            default => 'weekly',
        };

        return [
            'title' => 'Publications Insights',
            'subtitle' => 'Publications Insights Per Month',
            'period' => $activeRange,
            'active' => $activeRange,
            'weekly' => $weeklyChartData,
            'monthly' => $monthlyChartData,
        ];
    }
}
