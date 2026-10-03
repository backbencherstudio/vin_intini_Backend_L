<?php

namespace App\Http\Resources;

use App\Enums\IndustryJobPostStatus;
use App\Models\IndustryJobPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * @mixin IndustryJobPost
 */
class IndustryJobCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $logo = null;
        if ($this->industry) {
            $rawLogo = $this->industry->logo ?? null;
            if ($rawLogo) {
                $logo = str_starts_with($rawLogo, 'http') ? $rawLogo : asset('storage/'.ltrim($rawLogo, '/'));
            }
        }

        $location = array_filter([$this->state?->name, $this->city?->name]);
        $locationString = ! empty($location) ? implode(', ', $location) : 'N/A';

        $status = $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status;
        if ($status === IndustryJobPostStatus::PUBLISHED->value && $this->isExpired()) {
            $status = IndustryJobPostStatus::EXPIRED->value;
        }

        return [
            'id' => $this->id,
            'job_id' => $this->job_id,
            'slug' => $this->slug,
            'job_title' => $this->job_title,
            'industry_name' => $this->industry?->name,
            'industry_logo' => $logo,
            'status' => $status,
            'badges' => array_values(array_filter([
                $this->employment_type,
                $this->work_mode,
                $this->network_type,
            ])),
            'short_description' => Str::limit(strip_tags((string) $this->job_description), 110),
            'location' => $locationString,
            'views_count' => (int) ($this->views_count ?? 0),
            'applications_count' => (int) ($this->applications_count ?? 0),
        ];
    }
}
