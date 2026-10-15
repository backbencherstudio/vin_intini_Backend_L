<?php

namespace App\Http\Controllers\Api;

use App\Enums\BoostTargetEntity;
use App\Http\Controllers\Controller;
use App\Http\Resources\BoostPackageResource;
use App\Models\BoostPackage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductBoostController extends Controller
{
    /**
     * Get active boost packages and custom slider configuration for advertisers.
     */
    public function packages(Request $request): JsonResponse
    {
        $target = $request->input('target_entity', 'product');

        $packages = BoostPackage::query()
            ->where('status', 'active')
            ->whereIn('target_entity', [$target, BoostTargetEntity::ALL->value])
            ->orderByDesc('priority_weight')
            ->orderBy('price')
            ->get();

        // Custom duration slider configuration ($3.00/day base rate)
        $customSliderConfig = [
            'enabled' => true,
            'daily_rate' => 3.00,
            'currency' => 'USD',
            'min_days' => 3,
            'max_days' => 60,
            'daily_estimated_impressions_min' => 700,
            'daily_estimated_impressions_max' => 1400,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'preset_packages' => BoostPackageResource::collection($packages),
                'custom_slider' => $customSliderConfig,
            ],
        ], 200);
    }
}
