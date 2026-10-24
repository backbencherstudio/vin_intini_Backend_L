<?php

namespace App\Http\Controllers\Admin\Api;

use App\Enums\AdCampaignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreBoostPackageRequest;
use App\Http\Requests\UpdateBoostPackageRequest;
use App\Http\Requests\UpdateBoostSettingsRequest;
use App\Http\Resources\BoostPackageResource;
use App\Models\AdCampaign;
use App\Models\BoostPackage;
use App\Services\BoostSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BoostPackageAdminController extends Controller
{
    /**
     * Get current boost system settings (custom slider, new release hours, pause policy).
     */
    public function getSettings(BoostSettingService $settingsService): JsonResponse
    {
        return response()->json([
            'success' => true,
            'message' => 'Boost system settings retrieved successfully.',
            'data' => $settingsService->getSettings(),
        ], 200);
    }

    /**
     * Update boost system settings from admin dashboard.
     */
    public function updateSettings(UpdateBoostSettingsRequest $request, BoostSettingService $settingsService): JsonResponse
    {
        $updated = $settingsService->updateSettings($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Boost system settings updated successfully.',
            'data' => $updated,
        ], 200);
    }

    /**
     * Display a listing of boost packages with optional filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $targetEntity = $request->input('target_entity');
        $status = $request->input('status');
        $search = $request->input('search');

        $packages = BoostPackage::query()
            ->when($targetEntity, function ($query, $entity) {
                $query->where('target_entity', $entity);
            })
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->withCount(['campaigns', 'activeCampaigns'])
            ->orderByDesc('priority_weight')
            ->orderBy('id')
            ->get();

        $customCampaigns = AdCampaign::whereNull('boost_package_id');
        $presetCampaigns = AdCampaign::whereNotNull('boost_package_id');

        $overview = [
            'total_packages' => BoostPackage::count(),
            'active_packages' => BoostPackage::where('status', 'active')->count(),
            'total_ad_revenue' => (float) AdCampaign::sum('amount_paid'),
            'live_campaigns_count' => AdCampaign::where('status', AdCampaignStatus::ACTIVE)
                ->where('ends_at', '>', now())
                ->count(),
            'total_impressions_delivered' => (int) AdCampaign::sum('total_impressions'),
            'total_clicks_recorded' => (int) AdCampaign::sum('total_clicks'),
            'custom_packages' => [
                'total_purchased_count' => (int) (clone $customCampaigns)->count(),
                'live_campaigns_count' => (int) (clone $customCampaigns)
                    ->where('status', AdCampaignStatus::ACTIVE)
                    ->where('ends_at', '>', now())
                    ->count(),
                'total_revenue' => (float) (clone $customCampaigns)->sum('amount_paid'),
            ],
            'preset_packages' => [
                'total_purchased_count' => (int) (clone $presetCampaigns)->count(),
                'live_campaigns_count' => (int) (clone $presetCampaigns)
                    ->where('status', AdCampaignStatus::ACTIVE)
                    ->where('ends_at', '>', now())
                    ->count(),
                'total_revenue' => (float) (clone $presetCampaigns)->sum('amount_paid'),
            ],
        ];

        return response()->json([
            'success' => true,
            'overview' => $overview,
            'data' => BoostPackageResource::collection($packages),
        ], 200);
    }

    /**
     * Display the specified boost package.
     */
    public function show(BoostPackage $boostPackage): JsonResponse
    {
        $boostPackage->loadCount(['campaigns', 'activeCampaigns']);

        return response()->json([
            'success' => true,
            'data' => new BoostPackageResource($boostPackage),
        ], 200);
    }

    /**
     * Store a newly created boost package in storage.
     */
    public function store(StoreBoostPackageRequest $request): JsonResponse
    {
        $package = BoostPackage::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Boost package created successfully.',
            'data' => new BoostPackageResource($package),
        ], 201);
    }

    /**
     * Update the specified boost package in storage.
     */
    public function update(UpdateBoostPackageRequest $request, BoostPackage $boostPackage): JsonResponse
    {
        $boostPackage->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Boost package updated successfully.',
            'data' => new BoostPackageResource($boostPackage->fresh()),
        ], 200);
    }

    /**
     * Toggle active/inactive status of the boost package.
     */
    public function toggleStatus(BoostPackage $boostPackage): JsonResponse
    {
        $boostPackage->status = $boostPackage->status === 'active' ? 'inactive' : 'active';
        $boostPackage->save();

        return response()->json([
            'success' => true,
            'message' => "Boost package status updated to {$boostPackage->status}.",
            'data' => new BoostPackageResource($boostPackage),
        ], 200);
    }

    /**
     * Remove the specified boost package from storage.
     */
    public function destroy(BoostPackage $boostPackage): JsonResponse
    {
        if ($boostPackage->activeCampaigns()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete boost package with active campaigns. Please deactivate it instead.',
            ], 422);
        }

        $boostPackage->delete();

        return response()->json([
            'success' => true,
            'message' => 'Boost package deleted successfully.',
        ], 200);
    }
}
