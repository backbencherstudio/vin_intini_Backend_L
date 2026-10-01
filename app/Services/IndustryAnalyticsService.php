<?php

namespace App\Services;

use App\Enums\IndustryJobPostStatus;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\IndustryJobPostView;
use Carbon\Carbon;

class IndustryAnalyticsService
{
    public function getJobOverviewAnalytics(int $industryId, string $period = 'last_6_months'): array
    {
        $now = Carbon::now();
        $startOfCurrentMonth = $now->copy()->startOfMonth();
        $endOfCurrentMonth   = $now->copy()->endOfMonth();

        $startOfLastMonth    = $now->copy()->subMonth()->startOfMonth();
        $endOfLastMonth      = $now->copy()->subMonth()->endOfMonth();

        // ১. KPI Cards
        $cards = $this->calculateKpiCards(
            $industryId,
            $startOfCurrentMonth,
            $endOfCurrentMonth,
            $startOfLastMonth,
            $endOfLastMonth
        );

        // ২. Trend Graph 
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
        $totalViews = IndustryJobPost::where('industry_id', $industryId)->sum('views_count');

        $currentMonthViews = IndustryJobPostView::whereIn('industry_job_post_id', $jobPostIds)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->count();

        $lastMonthViews = IndustryJobPostView::whereIn('industry_job_post_id', $jobPostIds)
            ->whereBetween('created_at', [$startLast, $endLast])
            ->count();

        $viewsGrowth = $this->calculatePercentageChange($currentMonthViews, $lastMonthViews);

        // Applicants
        $totalApplicants = IndustryJobApplication::whereIn('job_id', $jobPostIds)->count();

        $currentMonthApplicants = IndustryJobApplication::whereIn('job_id', $jobPostIds)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->count();

        $lastMonthApplicants = IndustryJobApplication::whereIn('job_id', $jobPostIds)
            ->whereBetween('created_at', [$startLast, $endLast])
            ->count();

        $applicantsGrowth = $this->calculatePercentageChange($currentMonthApplicants, $lastMonthApplicants);

        // Active Positions (PUBLISHED ও ডেডলাইন শেষ না হওয়া পোস্টগুলো)
        $currentActivePositions = IndustryJobPost::where('industry_id', $industryId)
            ->where('status', IndustryJobPostStatus::PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('announcement_end_date')
                    ->orWhere('announcement_end_date', '>=', now()->toDateString());
            })
            ->count();

        $lastMonthActivePositions = IndustryJobPost::where('industry_id', $industryId)
            ->where('status', IndustryJobPostStatus::PUBLISHED)
            ->whereBetween('created_at', [$startLast, $endLast])
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
        // 3 ta filter option ache: last_3_months, last_6_months, last_12_months. Default hobe last_6_months.
        $monthsCount = match ($period) {
            'last_3_months'  => 3,
            'last_12_months' => 12,
            default          => 6, // last_6_months
        };

        $startDate = Carbon::now()->subMonths($monthsCount - 1)->startOfMonth();
        $endDate   = Carbon::now()->endOfMonth();

        $jobPostIds = IndustryJobPost::where('industry_id', $industryId)->select('id');

        // month Views
        $viewsData = IndustryJobPostView::whereIn('industry_job_post_id', $jobPostIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // month Applicants
        $applicantsData = IndustryJobApplication::whereIn('job_id', $jobPostIds)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        // month Active Positions
        $positionsData = IndustryJobPost::where('industry_id', $industryId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where('status', IndustryJobPostStatus::PUBLISHED)
            ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count")
            ->groupBy('month')
            ->pluck('count', 'month')
            ->toArray();

        $labels = [];
        $viewsSeries = [];
        $applicantsSeries = [];
        $positionsSeries = [];

        for ($i = $monthsCount - 1; $i >= 0; $i--) {
            $monthCarbon = Carbon::now()->subMonths($i);
            $monthKey = $monthCarbon->format('Y-m');

            $labels[] = $monthCarbon->format('M'); // like: Jan, Feb, Mar...
            $viewsSeries[] = (int) ($viewsData[$monthKey] ?? 0);
            $applicantsSeries[] = (int) ($applicantsData[$monthKey] ?? 0);
            $positionsSeries[] = (int) ($positionsData[$monthKey] ?? 0);
        }

        return [
            'labels' => $labels,
            'series' => [
                [
                    'name' => 'Total Applicant',
                    'key'  => 'total_applicants',
                    'data' => $applicantsSeries,
                ],
                [
                    'name' => 'Job Views',
                    'key'  => 'job_views',
                    'data' => $viewsSeries,
                ],
                [
                    'name' => 'Active Positions',
                    'key'  => 'active_positions',
                    'data' => $positionsSeries,
                ],
            ],
        ];
    }

    private function calculatePercentageChange(float $current, float $previous): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }

    public function getAdvertisementAnalytics(int $industryId, string $period = 'last_6_months'): array
    {
        return ['cards' => [], 'graph' => ['labels' => [], 'series' => []]];
    }

    public function getProfileInsightsAnalytics(int $industryId, string $period = 'last_6_months'): array
    {
        return ['cards' => [], 'graph' => ['labels' => [], 'series' => []]];
    }
}
