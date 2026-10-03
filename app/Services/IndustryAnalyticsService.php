<?php

namespace App\Services;

use App\Enums\IndustryJobPostStatus;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\IndustryJobPostView;
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

    public function getAdvertisementAnalytics(int $industryId, string $period = 'last_6_months'): array
    {
        return ['cards' => [], 'graph' => ['labels' => [], 'series' => []]];
    }

    public function getProfileInsightsAnalytics(int $industryId, string $period = 'last_6_months'): array
    {
        return ['cards' => [], 'graph' => ['labels' => [], 'series' => []]];
    }
}
