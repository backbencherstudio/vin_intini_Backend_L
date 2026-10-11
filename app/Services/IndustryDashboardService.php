<?php

namespace App\Services;

use App\Enums\IndustryJobPostStatus;
use App\Http\Resources\IndustryJobCardResource;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\IndustryJobPostView;
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

        // 4. Applicants Added / Trend Graph (weekly & monthly like analytics)
        $chartData = $this->calculateApplicantsChart($industryId, $jobPostIds, $chartRange);

        // 5. Activity Feed (Latest 3 notifications)
        $activityFeed = $this->getActivityFeed($user, $industryId);

        // 6. Applicant Overview (Latest 10 candidates)
        $recentApplicants = $this->getRecentApplicants($jobPostIds, 10);

        return [
            'cards' => $cards,
            'recent_jobs' => $recentJobsFormatted,
            'graph' => $chartData,
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
     * Calculate trend graph (weekly Mon-Sun 7 days, and monthly Jan-Dec 12 months)
     * matching job overview and advertisements analytics structure.
     */
    private function calculateApplicantsChart(int $industryId, $jobPostIds, string $chartRange = 'weekly'): array
    {
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
        $activeRange = match (strtolower(trim($chartRange))) {
            'monthly', 'month', 'yearly', 'year' => 'monthly',
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
     * Fetch recent candidate applicants (limit 10).
     */
    private function getRecentApplicants($jobPostIds, int $limit = 10): array
    {
        return IndustryJobApplication::whereIn('job_id', $jobPostIds)
            ->with([
                'applicant:id,first_name,last_name,username,email,profile_image',
                'job:id,job_id,job_title,position,network_type',
            ])
            ->latest('id')
            ->take($limit)
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

                $rawJobId = (string) ($job?->job_id ?? $job?->id ?? '');
                $formattedJobId = $rawJobId !== '' ? (str_starts_with($rawJobId, '#') ? $rawJobId : '#'.$rawJobId) : null;

                return [
                    'id' => $app->id,
                    'application_id' => (string) ($app->application_id ?: $app->id),
                    'job_post_id' => $job?->id,
                    'job_id' => $formattedJobId,
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
