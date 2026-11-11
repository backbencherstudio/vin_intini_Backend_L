<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AcademiaPostBaccalaureateProgram;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AcademiaPostBaccalaureateProgram
 */
class PostBaccalaureateProgramResource extends JsonResource
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
            'state_id' => $this->state_id,
            'program_name' => $this->program_name,
            'location' => $this->location,
            'phone' => $this->phone,
            'website' => $this->website,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'map_pin' => ($this->latitude !== null && $this->longitude !== null)
                ? [
                    'latitude' => $this->latitude,
                    'longitude' => $this->longitude,
                ]
                : null,
            'state' => $this->whenLoaded('state', fn (): ?string => $this->state?->name),
            'state_name' => $this->whenLoaded('state', fn (): ?string => $this->state?->name),
            'state_code' => $this->whenLoaded('state', fn (): ?string => $this->state?->code),
            'categories' => $this->whenLoaded(
                'categories',
                fn (): array => PostBaccalaureateCategoryResource::collection($this->categories)->resolve($request),
                []
            ),
            'degree_recognitions' => $this->whenLoaded(
                'categories',
                fn (): array => $this->categories->pluck('name')->values()->all(),
                []
            ),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
