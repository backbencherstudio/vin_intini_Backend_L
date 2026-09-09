<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PlanResource extends JsonResource
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
            'short_description' => $this->short_description,
            'billing_rate' => $this->billing_rate,
            'billing_cycle' => $this->billing_cycle,
            'discount_percent' => $this->discount_percent,
            'discount_duration' => $this->discount_duration,
            'badge_color' => $this->badge_color,
            'status' => $this->status,
            'features' => $this->features,
            'total_subscribers' => $this->subscriptions_count ?? 0,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
