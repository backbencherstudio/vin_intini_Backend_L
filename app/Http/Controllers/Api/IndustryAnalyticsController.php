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
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated user.',
            ], 401);
        }

        $validated = $request->validate([
            'filter'      => ['nullable', 'string', 'in:last_3_months,last_6_months,last_12_months'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
        ]);

        $industryId = $user->industry?->id
            ?? $user->company_id
            ?? ($validated['industry_id'] ?? null);

        if (!$industryId) {
            return response()->json([
                'success' => false,
                'message' => 'Industry not found for this account.',
            ], 422);
        }

        $period = $validated['filter'] ?? 'last_6_months';

        $data = $this->analyticsService->getJobOverviewAnalytics((int) $industryId, $period);

        return response()->json([
            'success' => true,
            'message' => 'Job overview analytics retrieved successfully.',
            'data'    => $data,
        ]);
    }

    public function advertisements(Request $request): JsonResponse
    {
        $industryId = $request->user()?->industry?->id ?? $request->query('industry_id');
        if (!$industryId) {
            return response()->json(['success' => false, 'message' => 'Industry not found.'], 422);
        }

        $period = $request->query('filter', 'last_6_months');
        $data = $this->analyticsService->getAdvertisementAnalytics((int) $industryId, $period);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function profileInsights(Request $request): JsonResponse
    {
        $industryId = $request->user()?->industry?->id ?? $request->query('industry_id');
        if (!$industryId) {
            return response()->json(['success' => false, 'message' => 'Industry not found.'], 422);
        }

        $period = $request->query('filter', 'last_6_months');
        $data = $this->analyticsService->getProfileInsightsAnalytics((int) $industryId, $period);

        return response()->json(['success' => true, 'data' => $data]);
    }
}
