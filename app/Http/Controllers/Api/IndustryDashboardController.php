<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\IndustryDashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndustryDashboardController extends Controller
{
    public function __construct(
        protected IndustryDashboardService $dashboardService
    ) {}

    /**
     * Get recruiter dashboard composite data.
     */
    public function index(Request $request): JsonResponse
    {
        $user = auth('api')->user() ?? $request->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $isAdmin = method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'));

        $industryId = $user->industry?->id ?? $user->company_id;

        if ($isAdmin && $request->filled('industry_id')) {
            $industryId = $request->integer('industry_id');
        }

        if (! $industryId) {
            return response()->json([
                'success' => false,
                'message' => 'Your account is not associated with any company profile. Please create a company profile first.',
            ], 422);
        }

        $chartRange = $request->query('chart_range', $request->query('range', 'weekly'));

        $data = $this->dashboardService->getRecruiterDashboard($user, (int) $industryId, (string) $chartRange);

        return response()->json([
            'success' => true,
            'message' => 'Recruiter dashboard data retrieved successfully.',
            'data' => $data,
        ]);
    }
}
