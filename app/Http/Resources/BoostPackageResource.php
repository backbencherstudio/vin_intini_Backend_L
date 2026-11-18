<?php

namespace App\Http\Resources;

use App\Models\BoostPackage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin BoostPackage
 */
class BoostPackageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'target_entity' => $this->target_entity instanceof \BackedEnum ? $this->target_entity->value : (string) $this->target_entity,
            'placements' => $this->resolvedPlacements(),
            'duration_days' => (int) $this->duration_days,
            'price' => (float) $this->price,
            'daily_price' => round((float) $this->price / max(1, (int) $this->duration_days), 2),
            'estimated_impressions' => $this->estimated_impressions,
            'badge' => $this->badge,
            'promotion_highlight' => $this->promotion_highlight,
            'description' => $this->description,
            'features' => $this->resolvedFeatures(),
            'priority_weight' => (int) $this->priority_weight,
            'sort_order' => (int) ($this->sort_order ?? 0),
            'status' => $this->status,
            'is_active' => $this->status === 'active',
            'checkout_type' => $this->isRevenueCat() ? 'revenuecat' : 'stripe',
            'revenuecat_store_identifier_ios' => $this->revenuecat_store_identifier_ios,
            'revenuecat_store_identifier_android' => $this->revenuecat_store_identifier_android,
            'revenuecat_entitlement_identifier' => $this->revenuecat_entitlement_identifier,
            'active_campaigns_count' => $this->whenCounted('activeCampaigns', (int) $this->active_campaigns_count),
            'total_campaigns_count' => $this->whenCounted('campaigns', (int) $this->campaigns_count),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
