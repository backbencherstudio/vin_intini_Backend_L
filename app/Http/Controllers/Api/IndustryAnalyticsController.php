<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IndustryAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndustryAnalyticsController extends Controller
{
    public function __construct(
        protected IndustryAnalyticsService $analyticsService
    ) {}

    public function jobOverview(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?? $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $isAdmin = method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'));

        $validated = $request->validate([
            'filter' => ['nullable', 'string'],
            'period' => ['nullable', 'string'],
            'range' => ['nullable', 'string'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
        ]);

        $industryId = $user->industry?->id ?? $user->company_id;

        if ($isAdmin && ! empty($validated['industry_id'])) {
            $industryId = (int) $validated['industry_id'];
        }

        if (! $industryId) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not associated with any company profile. Please create a company profile first.',
            ], 422);
        }

        // Support 'filter', 'period', or 'range' parameter (default: 'weekly')
        $rawFilter = $validated['filter'] ?? $validated['period'] ?? $validated['range'] ?? $request->query('filter', $request->query('period', $request->query('range', 'weekly')));

        $period = match (strtolower(trim((string) $rawFilter))) {
            'monthly', 'month' => 'monthly',
            default => 'weekly',
        };

        $data = $this->analyticsService->getJobOverviewAnalytics((int) $industryId, $period);

        return response()->json([
            'success' => true,
            'message' => 'Job overview analytics retrieved successfully.',
            'data' => $data,
        ]);
    }

    public function advertisements(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?? $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $isAdmin = method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'));

        $validated = $request->validate([
            'filter' => ['nullable', 'string'],
            'period' => ['nullable', 'string'],
            'range' => ['nullable', 'string'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
        ]);

        $industryId = $user->industry?->id ?? $user->company_id;

        if ($isAdmin && ! empty($validated['industry_id'])) {
            $industryId = (int) $validated['industry_id'];
        }

        if (! $industryId) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not associated with any company profile. Please create a company profile first.',
            ], 422);
        }

        $rawFilter = $validated['filter'] ?? $validated['period'] ?? $validated['range'] ?? $request->query('filter', $request->query('period', $request->query('range', 'weekly')));

        $period = match (strtolower(trim((string) $rawFilter))) {
            'monthly', 'month' => 'monthly',
            default => 'weekly', // default: weekly
        };

        $data = $this->analyticsService->getAdvertisementAnalytics((int) $industryId, $period);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement analytics retrieved successfully.',
            'data' => $data,
        ]);
    }

    public function profileInsights(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?? $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $isAdmin = method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'));
        $industryId = $user->industry?->id ?? $user->company_id;

        if ($isAdmin && $request->filled('industry_id')) {
            $industryId = (int) $request->input('industry_id');
        }

        if (! $industryId) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not associated with any company profile.',
            ], 422);
        }

        $period = $request->query('filter', $request->query('period', 'last_6_months'));
        $data = $this->analyticsService->getProfileInsightsAnalytics((int) $industryId, $period);

        return response()->json([
            'success' => true,
            'message' => 'Profile insights analytics retrieved successfully.',
            'data' => $data,
        ]);
    }
}
