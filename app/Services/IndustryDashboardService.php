<?php

namespace App\Services;

use App\Enums\IndustryJobPostStatus;
use App\Http\Resources\IndustryJobCardResource;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class IndustryDashboardService
{
    /**
     * Compile complete recruiter dashboard data.
     */
    public function getRecruiterDashboard(User $user, int $industryId, string $chartRange = 'weekly'): array
    {
        // 1. JIT auto-sync: Automatically update past-deadline published jobs to expired
        IndustryJobPost::where('industry_id', $industryId)
            ->where('status', IndustryJobPostStatus::PUBLISHED->value)
            ->whereNotNull('announcement_end_date')
            ->whereDate('announcement_end_date', '<', today())
            ->update(['status' => IndustryJobPostStatus::EXPIRED]);

        $jobPostIds = IndustryJobPost::where('industry_id', $industryId)->select('id');

        // 2. Metrics Cards
        $cards = $this->calculateCards($industryId, $jobPostIds);

        // 3. Recent Job Listings (Latest 3 jobs)
        $recentJobs = IndustryJobPost::where('industry_id', $industryId)
            ->with([
                'industry:id,name,logo',
                'state:id,name',
                'city:id,name',
            ])
            ->withCount('applications')
            ->latest('id')
            ->take(3)
            ->get();

        $recentJobsFormatted = IndustryJobCardResource::collection($recentJobs)->resolve();

        // 4. Applicants Added (Graph / Trend Chart: Daily, Weekly, Monthly)
        $chartData = $this->calculateApplicantsChart($jobPostIds, $chartRange);

        // 5. Activity Feed (Latest 3 notifications)
        $activityFeed = $this->getActivityFeed($user, $industryId);

        // 6. Applicant Overview (Latest 5 candidates)
        $recentApplicants = $this->getRecentApplicants($jobPostIds);

        return [
            'cards' => $cards,
            'recent_jobs' => $recentJobsFormatted,
            'applicants_chart' => $chartData,
            'activity_feed' => $activityFeed,
            'recent_applicants' => $recentApplicants,
        ];
    }

    /**
     * Calculate top metric cards.
     */
    private function calculateCards(int $industryId, $jobPostIds): array
    {
        $now = Carbon::now();
        $startCurrent = $now->copy()->startOfMonth();
        $endCurrent = $now->copy()->endOfMonth();

        $startLast = $startCurrent->copy()->subMonthNoOverflow();
        $endLast = $startLast->copy()->endOfMonth();

        // 1. Active Positions (Current snapshot)
        $currentActivePositions = IndustryJobPost::where('industry_id', $industryId)
            ->where('status', IndustryJobPostStatus::PUBLISHED)
            ->where(function ($q) {
                $q->whereNull('announcement_end_date')
                    ->orWhere('announcement_end_date', '>=', now()->toDateString());
            })
            ->count();

        // Active Positions at end of last month
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

        $activePositionsGrowth = $currentActivePositions - $lastMonthActivePositions;

        // 2. Total Applicants
        $totalApplicants = IndustryJobApplication::whereIn('job_id', $jobPostIds)->count();

        $currentMonthApplicants = IndustryJobApplication::whereIn('job_id', $jobPostIds)
            ->whereBetween('created_at', [$startCurrent, $endCurrent])
            ->count();

        $lastMonthApplicants = IndustryJobApplication::whereIn('job_id', $jobPostIds)
            ->whereBetween('created_at', [$startLast, $endLast])
            ->count();

        $applicantsGrowth = $currentMonthApplicants - $lastMonthApplicants;

        // 3. Total Job Posts (all created jobs)
        $totalJobPosts = IndustryJobPost::where('industry_id', $industryId)->count();

        // 4. Total Archive Jobs
        $totalArchivedJobs = IndustryJobPost::where('industry_id', $industryId)
            ->where('status', IndustryJobPostStatus::ARCHIVE)
            ->count();

        // 5. Total Reject Jobs
        $totalRejectedJobs = IndustryJobPost::where('industry_id', $industryId)
            ->where('status', IndustryJobPostStatus::REJECTED)
            ->count();

        return [
            'active_positions' => [
                'total' => (int) $currentActivePositions,
                'growth' => ($activePositionsGrowth >= 0 ? "+{$activePositionsGrowth}" : (string) $activePositionsGrowth).' from last month',
                'growth_count' => $activePositionsGrowth,
                'is_positive' => $activePositionsGrowth >= 0,
            ],
            'total_applicants' => [
                'total' => (int) $totalApplicants,
                'growth' => ($applicantsGrowth >= 0 ? "+{$applicantsGrowth}" : (string) $applicantsGrowth).' from last month',
                'growth_count' => $applicantsGrowth,
                'is_positive' => $applicantsGrowth >= 0,
            ],
            'total_job_posts' => [
                'total' => (int) $totalJobPosts,
            ],
            'total_archived_jobs' => [
                'total' => (int) $totalArchivedJobs,
            ],
            'total_rejected_jobs' => [
                'total' => (int) $totalRejectedJobs,
            ],
        ];
    }

    /**
     * Calculate applicants graph across weekly (current week 7 days), monthly (current month 1-31 date-wise), and yearly (current year Jan-Dec).
     */
    private function calculateApplicantsChart($jobPostIds, string $chartRange = 'weekly'): array
    {
        $driver = DB::connection()->getDriverName();

        // --- 1. Weekly (Current week: 7 days Mon to Sun) ---
        $startOfWeek = Carbon::now()->startOfWeek(Carbon::MONDAY)->startOfDay();
        $endOfWeek = Carbon::now()->endOfWeek(Carbon::SUNDAY)->endOfDay();

        $weekDateFormat = match ($driver) {
            'sqlite' => "strftime('%Y-%m-%d', created_at)",
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
            default => "DATE_FORMAT(created_at, '%Y-%m-%d')",
        };

        $weeklyCounts = IndustryJobApplication::whereIn('job_id', $jobPostIds)
            ->whereBetween('created_at', [$startOfWeek, $endOfWeek])
            ->selectRaw("{$weekDateFormat} as period_key, COUNT(*) as count")
            ->groupBy('period_key')
            ->pluck('count', 'period_key')
            ->toArray();

        $weeklyLabels = [];
        $weeklySeries = [];
        for ($d = 0; $d < 7; $d++) {
            $dayCarbon = $startOfWeek->copy()->addDays($d);
            $key = $dayCarbon->format('Y-m-d');
            $weeklyLabels[] = $dayCarbon->format('D'); // e.g. Mon, Tue, Wed, Thu, Fri, Sat, Sun
            $weeklySeries[] = (int) ($weeklyCounts[$key] ?? 0);
        }

        // --- 2. Monthly (Current month: 1 to 28/29/30/31 date-wise) ---
        $startOfMonth = Carbon::now()->startOfMonth()->startOfDay();
        $endOfMonth = Carbon::now()->endOfMonth()->endOfDay();
        $daysInMonth = (int) Carbon::now()->daysInMonth;

        $monthDayFormat = match ($driver) {
            'sqlite' => "CAST(strftime('%d', created_at) AS INTEGER)",
            'pgsql' => 'EXTRACT(DAY FROM created_at)::INTEGER',
            default => "CAST(DATE_FORMAT(created_at, '%e') AS UNSIGNED)",
        };

        $monthlyCounts = IndustryJobApplication::whereIn('job_id', $jobPostIds)
            ->whereBetween('created_at', [$startOfMonth, $endOfMonth])
            ->selectRaw("{$monthDayFormat} as day_num, COUNT(*) as count")
            ->groupBy('day_num')
            ->pluck('count', 'day_num')
            ->toArray();

        $monthlyLabels = [];
        $monthlySeries = [];
        for ($day = 1; $day <= $daysInMonth; $day++) {
            $monthlyLabels[] = (string) $day; // e.g. "1", "2", ... "31"
            $monthlySeries[] = (int) ($monthlyCounts[$day] ?? 0);
        }

        // --- 3. Yearly (Current year: Jan to Dec 12 months) ---
        $startOfYear = Carbon::now()->startOfYear()->startOfDay();
        $endOfYear = Carbon::now()->endOfYear()->endOfDay();

        $yearMonthFormat = match ($driver) {
            'sqlite' => "CAST(strftime('%m', created_at) AS INTEGER)",
            'pgsql' => 'EXTRACT(MONTH FROM created_at)::INTEGER',
            default => 'MONTH(created_at)',
        };

        $yearlyCounts = IndustryJobApplication::whereIn('job_id', $jobPostIds)
            ->whereBetween('created_at', [$startOfYear, $endOfYear])
            ->selectRaw("{$yearMonthFormat} as month_num, COUNT(*) as count")
            ->groupBy('month_num')
            ->pluck('count', 'month_num')
            ->toArray();

        $yearlyLabels = [];
        $yearlySeries = [];
        for ($m = 1; $m <= 12; $m++) {
            $yearlyLabels[] = Carbon::create(null, $m, 1)->format('M'); // Jan, Feb, Mar...
            $yearlySeries[] = (int) ($yearlyCounts[$m] ?? 0);
        }

        $allCharts = [
            'weekly' => [
                'labels' => $weeklyLabels,
                'series' => $weeklySeries,
                'total' => array_sum($weeklySeries),
            ],
            'monthly' => [
                'labels' => $monthlyLabels,
                'series' => $monthlySeries,
                'total' => array_sum($monthlySeries),
            ],
            'yearly' => [
                'labels' => $yearlyLabels,
                'series' => $yearlySeries,
                'total' => array_sum($yearlySeries),
            ],
        ];

        // Keep 'daily' as alias to weekly for backward compatibility if any client requested daily
        $allCharts['daily'] = $allCharts['weekly'];

        $rangeKey = match (strtolower(trim($chartRange))) {
            'monthly', 'month' => 'monthly',
            'yearly', 'year', '12months', '12_months' => 'yearly',
            default => 'weekly', // default: weekly
        };

        return [
            'active' => $rangeKey,
            'active_range' => $rangeKey,
            'labels' => $allCharts[$rangeKey]['labels'],
            'series' => $allCharts[$rangeKey]['series'],
            'ranges' => $allCharts,
        ];
    }

    /**
     * Fetch recent activity notifications for this industry (limit 3).
     */
    private function getActivityFeed(User $user, int $industryId): array
    {
        return $user->notifications()
            ->where(function ($q) use ($industryId) {
                $q->where('data->industry_id', (int) $industryId)
                    ->orWhereNotNull('data->industry_id');
            })
            ->orderByDesc('created_at')
            ->take(3)
            ->get()
            ->map(function ($n) {
                $data = is_string($n->data) ? json_decode($n->data, true) : $n->data;

                $title = $data['title'] ?? match ($n->type) {
                    'App\Notifications\JobApplicationReceivedNotification' => 'New application received',
                    'App\Notifications\JobApplicationStatusUpdatedNotification' => 'Application status updated',
                    default => 'Notification',
                };

                $message = $data['message'] ?? $data['body'] ?? '';

                return [
                    'id' => $n->id,
                    'type' => $n->type,
                    'title' => $title,
                    'message' => $message,
                    'time' => $n->created_at?->format('h:i A') ?? '',
                    'created_at_human' => $n->created_at?->diffForHumans() ?? '',
                    'is_read' => $n->read_at !== null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * Fetch recent candidate applicants (limit 5).
     */
    private function getRecentApplicants($jobPostIds): array
    {
        return IndustryJobApplication::whereIn('job_id', $jobPostIds)
            ->with([
                'applicant:id,first_name,last_name,username,email,profile_image',
                'job:id,job_id,job_title,position,network_type',
            ])
            ->latest('id')
            ->take(5)
            ->get()
            ->map(function (IndustryJobApplication $app) {
                $applicant = $app->applicant;
                $job = $app->job;
                $avatar = null;
                if ($applicant) {
                    $rawImg = $applicant->profile_image_url ?? $applicant->profile_image;
                    if ($rawImg) {
                        $avatar = str_starts_with($rawImg, 'http') ? $rawImg : asset('storage/'.ltrim($rawImg, '/'));
                    }
                }

                return [
                    'id' => $app->id,
                    'application_id' => $app->application_id,
                    'job_id' => $job ? '#'.$job->job_id : null,
                    'job_title' => $job?->job_title,
                    'position' => $job?->position ?? $job?->job_title ?? 'N/A',
                    'applicant_name' => $app->full_name ?: trim(($applicant?->first_name ?? '').' '.($applicant?->last_name ?? '')),
                    'applicant_email' => $app->email ?: $applicant?->email,
                    'applicant_avatar' => $avatar,
                    'network' => $job?->network_type ? ucfirst((string) $job->network_type) : null,
                    'status' => $app->status instanceof \BackedEnum ? $app->status->value : (string) $app->status,
                    'applied_on' => $app->created_at?->format('M d, Y'),
                ];
            })
            ->values()
            ->all();
    }
}
