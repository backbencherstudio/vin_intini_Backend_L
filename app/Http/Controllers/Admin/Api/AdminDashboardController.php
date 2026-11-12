<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\AdCampaign;
use App\Models\ContactUs;
use App\Models\IndustryJobPost;
use App\Models\IndustryProduct;
use App\Models\Post;
use App\Models\Publication;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    /**
     * Get aggregate statistics, revenue charts, and recent activities for the admin dashboard.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['sometimes', 'nullable', 'date'],
            'date_to' => ['sometimes', 'nullable', 'date'],
        ]);

        $now = Carbon::now();
        $dateTo = ! empty($validated['date_to'])
            ? Carbon::parse($validated['date_to'])->endOfDay()
            : $now->copy()->endOfDay();

        $dateFrom = ! empty($validated['date_from'])
            ? Carbon::parse($validated['date_from'])->startOfDay()
            : $dateTo->copy()->subDays(29)->startOfDay();

        $diffDays = max(1, (int) $dateFrom->diffInDays($dateTo));
        $compDateTo = $dateFrom->copy()->subSecond();
        $compDateFrom = $compDateTo->copy()->subDays($diffDays);

        // Current Month vs Previous Month for Top Cards
        $prevMonthEnd = $now->copy()->subMonthNoOverflow()->endOfMonth();

        // 1. Top Status Cards
        $totalUsers = User::count();
        $prevMonthUsers = User::where('created_at', '<=', $prevMonthEnd)->count();
        $userGrowthMonthly = $this->calculatePercentageGrowth($totalUsers, $prevMonthUsers);

        $totalJobs = IndustryJobPost::count();
        $prevMonthJobs = IndustryJobPost::where('created_at', '<=', $prevMonthEnd)->count();
        $jobGrowthMonthly = $this->calculatePercentageGrowth($totalJobs, $prevMonthJobs);

        $totalPosts = Post::count();
        $prevMonthPosts = Post::where('created_at', '<=', $prevMonthEnd)->count();
        $postGrowthMonthly = $this->calculatePercentageGrowth($totalPosts, $prevMonthPosts);

        $totalAds = AdCampaign::count();
        $prevMonthAds = AdCampaign::where('created_at', '<=', $prevMonthEnd)->count();
        $adsGrowthMonthly = $this->calculatePercentageGrowth($totalAds, $prevMonthAds);

        $totalPublications = Publication::count();
        $prevMonthPublications = Publication::where('created_at', '<=', $prevMonthEnd)->count();
        $pubGrowthMonthly = $this->calculatePercentageGrowth($totalPublications, $prevMonthPublications);

        $statusCards = [
            [
                'key' => 'total_users',
                'title' => 'Total User',
                'value' => number_format($totalUsers),
                'raw_value' => $totalUsers,
                'percentage' => $userGrowthMonthly['percentage'],
                'direction' => $userGrowthMonthly['direction'],
                'link' => '/dashboard/user-management/basic-user',
            ],
            [
                'key' => 'total_jobs',
                'title' => 'Total Job',
                'value' => number_format($totalJobs),
                'raw_value' => $totalJobs,
                'percentage' => $jobGrowthMonthly['percentage'],
                'direction' => $jobGrowthMonthly['direction'],
                'link' => '/dashboard/academia/facilities',
            ],
            [
                'key' => 'total_posts',
                'title' => 'Total Post',
                'value' => number_format($totalPosts),
                'raw_value' => $totalPosts,
                'percentage' => $postGrowthMonthly['percentage'],
                'direction' => $postGrowthMonthly['direction'],
                'link' => null,
            ],
            [
                'key' => 'total_ads',
                'title' => 'Total Ads',
                'value' => number_format($totalAds),
                'raw_value' => $totalAds,
                'percentage' => $adsGrowthMonthly['percentage'],
                'direction' => $adsGrowthMonthly['direction'],
                'link' => '/dashboard/advertisement',
            ],
            [
                'key' => 'total_publications',
                'title' => 'Total Publications',
                'value' => number_format($totalPublications),
                'raw_value' => $totalPublications,
                'percentage' => $pubGrowthMonthly['percentage'],
                'direction' => $pubGrowthMonthly['direction'],
                'link' => '/dashboard/publications',
            ],
        ];

        // 2. Revenue Report
        $revenueReport = $this->buildRevenueReport($dateFrom, $dateTo, $compDateFrom, $compDateTo);

        // 3. User Growth (Last 6 Months)
        $userGrowth = $this->buildUserGrowth($now);

        // 4. Total Revenue Breakdown
        $revenueBreakdown = $this->buildRevenueBreakdown();

        // 5. Pending Approval Counts
        $pendingApprovals = [
            [
                'title' => 'Products Pending',
                'count' => IndustryProduct::where('status', 'pending')->count(),
                'link' => '/dashboard/psychology/biotechnology',
            ],
            [
                'title' => 'Jobs Pending',
                'count' => IndustryJobPost::where('status', 'pending')->count(),
                'link' => '/dashboard/academia/facilities',
            ],
            [
                'title' => 'Advertisements Pending',
                'count' => AdCampaign::where('status', 'pending')->count(),
                'link' => '/dashboard/advertisement',
            ],
            [
                'title' => 'Reports',
                'count' => ContactUs::count(),
                'link' => '/dashboard/contact-messages',
            ],
        ];

        // 6. Recent Activities
        $recentActivities = $this->buildRecentActivities($dateFrom, $dateTo);

        return response()->json([
            'success' => true,
            'data' => [
                'status_cards' => $statusCards,
                'revenue_report' => $revenueReport,
                'user_growth' => $userGrowth,
                'total_revenue_breakdown' => $revenueBreakdown,
                'pending_approvals' => $pendingApprovals,
                'recent_activities' => $recentActivities,
            ],
        ], 200);
    }

    /**
     * Calculate percentage change and direction between two numbers.
     *
     * @return array{percentage: string, direction: 'up'|'down'|'flat'}
     */
    private function calculatePercentageGrowth(int|float $current, int|float $previous): array
    {
        if ($previous == 0) {
            return [
                'percentage' => $current > 0 ? '100%' : '0%',
                'direction' => $current > 0 ? 'up' : 'flat',
            ];
        }

        $diff = $current - $previous;
        $pct = round(abs($diff / $previous) * 100, 1);

        return [
            'percentage' => $pct.'%',
            'direction' => $diff > 0 ? 'up' : ($diff < 0 ? 'down' : 'flat'),
        ];
    }

    /**
     * Compile Revenue Report for selected and comparison date ranges.
     *
     * @return array<string, mixed>
     */
    private function buildRevenueReport(Carbon $dateFrom, Carbon $dateTo, Carbon $compFrom, Carbon $compTo): array
    {
        $currentRevenue = (float) Transaction::where('status', 'succeeded')
            ->whereBetween('paid_at', [$dateFrom, $dateTo])
            ->sum('amount');

        $compRevenue = (float) Transaction::where('status', 'succeeded')
            ->whereBetween('paid_at', [$compFrom, $compTo])
            ->sum('amount');

        $diff = $currentRevenue - $compRevenue;
        $growthRate = $compRevenue > 0
            ? round(($diff / $compRevenue) * 100, 1)
            : ($currentRevenue > 0 ? 100.0 : 0.0);

        // Build 10-12 data points along the range
        $diffDays = max(1, (int) $dateFrom->diffInDays($dateTo));
        $stepDays = max(1, (int) ceil($diffDays / 10));

        $chartData = [];
        $cursor = $dateFrom->copy();
        $compCursor = $compFrom->copy();
        $index = 0;

        while ($cursor->lte($dateTo)) {
            $nextCursor = $cursor->copy()->addDays($stepDays)->subSecond();
            if ($nextCursor->gt($dateTo)) {
                $nextCursor = $dateTo->copy();
            }

            $nextCompCursor = $compCursor->copy()->addDays($stepDays)->subSecond();
            if ($nextCompCursor->gt($compTo)) {
                $nextCompCursor = $compTo->copy();
            }

            $bucketRevenue = (float) Transaction::where('status', 'succeeded')
                ->whereBetween('paid_at', [$cursor, $nextCursor])
                ->sum('amount');

            $compBucketRevenue = (float) Transaction::where('status', 'succeeded')
                ->whereBetween('paid_at', [$compCursor, $nextCompCursor])
                ->sum('amount');

            $isEdge = $index === 0 || $nextCursor->gte($dateTo);

            $chartData[] = [
                'dateLabel' => $isEdge ? $cursor->format('M d, Y') : '',
                'dateStr' => $cursor->format('F d, Y'),
                'comparisonDateStr' => $compCursor->format('F d, Y'),
                'currentPeriod' => round($bucketRevenue / 1000, 2),
                'lastPeriod' => round($compBucketRevenue / 1000, 2),
                'rawCurrentPeriod' => $bucketRevenue,
                'rawLastPeriod' => $compBucketRevenue,
            ];

            $cursor = $nextCursor->copy()->addSecond();
            $compCursor = $nextCompCursor->copy()->addSecond();
            $index++;
        }

        return [
            'totalRevenue' => $currentRevenue,
            'growthRate' => abs($growthRate),
            'direction' => $growthRate >= 0 ? 'up' : 'down',
            'dateRange' => $dateFrom->format('M d').' - '.$dateTo->format('M d, Y'),
            'chartData' => $chartData,
        ];
    }

    /**
     * Compile User Growth data over the last 6 months.
     *
     * @return array<string, mixed>
     */
    private function buildUserGrowth(Carbon $now): array
    {
        $totalUsers = User::count();
        $oneMonthAgo = $now->copy()->subMonth();
        $prevMonthUsers = User::where('created_at', '<=', $oneMonthAgo)->count();
        $diff = $totalUsers - $prevMonthUsers;
        $growthRate = $prevMonthUsers > 0 ? round(($diff / $prevMonthUsers) * 100, 1) : 0.0;

        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = $now->copy()->subMonths($i);
            $mStart = $monthDate->copy()->startOfMonth();
            $mEnd = $monthDate->copy()->endOfMonth();

            $monthUsersCount = User::whereBetween('created_at', [$mStart, $mEnd])->count();
            $chartData[] = [
                'month' => $monthDate->format('M'),
                'users' => $monthUsersCount,
                'raw_count' => $monthUsersCount,
            ];
        }

        return [
            'totalUsers' => $totalUsers,
            'growthRate' => abs($growthRate),
            'direction' => $growthRate >= 0 ? 'up' : 'down',
            'chartData' => $chartData,
        ];
    }

    /**
     * Compile breakdown of total revenue by source.
     *
     * @return array{total: float, items: array<int, array<string, mixed>>}
     */
    private function buildRevenueBreakdown(): array
    {
        $subscriptionRevenue = (float) Transaction::where('status', 'succeeded')
            ->where(function ($q): void {
                $q->whereNotNull('plan_id')->orWhereNotNull('subscription_id');
            })
            ->sum('amount');

        $advertisementRevenue = (float) Transaction::where('status', 'succeeded')
            ->whereNotNull('ad_campaign_id')
            ->sum('amount');

        $total = $subscriptionRevenue + $advertisementRevenue;

        $items = [
            [
                'name' => 'Subscriptions',
                'amount' => $subscriptionRevenue,
                'value' => $subscriptionRevenue,
                'percentage' => $total > 0 ? round(($subscriptionRevenue / $total) * 100, 2) : 0,
            ],
            [
                'name' => 'Advertisements',
                'amount' => $advertisementRevenue,
                'value' => $advertisementRevenue,
                'percentage' => $total > 0 ? round(($advertisementRevenue / $total) * 100, 2) : 0,
            ],
        ];

        return [
            'total' => $total,
            'items' => $items,
        ];
    }

    /**
     * Gather recent system activities.
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildRecentActivities(Carbon $dateFrom, Carbon $dateTo): array
    {
        $activities = [];

        // Recent users
        $users = User::with('profile')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->latest()
            ->take(6)
            ->get();

        foreach ($users as $user) {
            $name = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: $user->name ?: 'User';
            $profession = is_array($user->profile?->profession)
                ? implode(', ', $user->profile->profession)
                : ($user->profile?->profession ?? 'Professional');

            $activities[] = [
                'id' => 'user_'.$user->id,
                'type' => 'user_registered',
                'title' => 'User Registered',
                'desc' => "{$name} joined as {$profession}",
                'created_at' => $user->created_at->toIso8601String(),
                'time' => $user->created_at->format('h:i A'),
                'timestamp' => $user->created_at->timestamp,
            ];
        }

        // Recent ad campaigns
        $ads = AdCampaign::with('boostPackage')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->latest()
            ->take(5)
            ->get();

        foreach ($ads as $ad) {
            $pkgName = $ad->boostPackage?->name ?? 'Advertisement';
            $activities[] = [
                'id' => 'ad_'.$ad->id,
                'type' => 'ad_submitted',
                'title' => 'Advertisement Submitted',
                'desc' => "{$pkgName} submitted for approval",
                'created_at' => $ad->created_at->toIso8601String(),
                'time' => $ad->created_at->format('h:i A'),
                'timestamp' => $ad->created_at->timestamp,
            ];
        }

        // Recent transactions / subscriptions
        $transactions = Transaction::with(['user', 'plan'])
            ->where('status', 'succeeded')
            ->whereBetween('paid_at', [$dateFrom, $dateTo])
            ->latest('paid_at')
            ->take(5)
            ->get();

        foreach ($transactions as $tx) {
            $uName = trim(($tx->user?->first_name ?? '').' '.($tx->user?->last_name ?? '')) ?: 'User';
            $planName = $tx->plan?->name ?? 'Subscription';
            $paidAt = $tx->paid_at ?? $tx->created_at;

            $activities[] = [
                'id' => 'tx_'.$tx->id,
                'type' => 'subscription_purchased',
                'title' => 'Subscription Purchased',
                'desc' => "{$uName} purchased {$planName}",
                'created_at' => $paidAt->toIso8601String(),
                'time' => $paidAt->format('h:i A'),
                'timestamp' => $paidAt->timestamp,
            ];
        }

        // Recent publications
        $publications = Publication::with('creator')
            ->whereBetween('created_at', [$dateFrom, $dateTo])
            ->latest()
            ->take(5)
            ->get();

        foreach ($publications as $pub) {
            $cName = $pub->creator?->name ?? 'Creator';
            $activities[] = [
                'id' => 'pub_'.$pub->id,
                'type' => 'publication_created',
                'title' => 'Publication Uploaded',
                'desc' => "{$pub->title} uploaded by {$cName}",
                'created_at' => $pub->created_at->toIso8601String(),
                'time' => $pub->created_at->format('h:i A'),
                'timestamp' => $pub->created_at->timestamp,
            ];
        }

        // Recent contact inquiries
        $contacts = ContactUs::whereBetween('created_at', [$dateFrom, $dateTo])
            ->latest()
            ->take(5)
            ->get();

        foreach ($contacts as $contact) {
            $activities[] = [
                'id' => 'contact_'.$contact->id,
                'type' => 'contact_submitted',
                'title' => 'Contact Inquiry',
                'desc' => "Message from {$contact->name}: ".($contact->subject ?: 'Inquiry'),
                'created_at' => $contact->created_at->toIso8601String(),
                'time' => $contact->created_at->format('h:i A'),
                'timestamp' => $contact->created_at->timestamp,
            ];
        }

        usort($activities, fn ($a, $b) => $b['timestamp'] <=> $a['timestamp']);

        return array_slice($activities, 0, 10);
    }
}
