<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AcademiaPostBaccalaureateCategory;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin AcademiaPostBaccalaureateCategory
 */
class PostBaccalaureateCategoryResource extends JsonResource
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
            'sort_order' => (int) $this->sort_order,
            'is_active' => (bool) $this->is_active,
            'programs_count' => $this->whenCounted('programs', fn (): int => (int) $this->programs_count),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
