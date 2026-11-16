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

        $location = array_filter([$this->city?->name, $this->state?->name]);
        $locationString = $location !== [] ? implode(', ', $location) : 'N/A';

        $status = $this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status;
        if ($status === IndustryJobPostStatus::PUBLISHED->value) {
            $startDate = $this->announcement_start_date ? \Carbon\Carbon::parse($this->announcement_start_date)->startOfDay() : null;
            if ($startDate && $startDate->isFuture()) {
                $status = 'scheduled';
            } elseif ($this->isExpired()) {
                $status = IndustryJobPostStatus::EXPIRED->value;
            }
        }

        $currentUser = auth('api')->user();
        $isOwnIndustry = $currentUser && (
            ((int) $this->created_by === (int) $currentUser->id) ||
            ($this->industry && (int) $this->industry->created_by === (int) $currentUser->id) ||
            ($this->industry_id && $currentUser->industry && (int) $this->industry_id === (int) $currentUser->industry->id)
        );

        return [
            'id' => $this->id,
            'job_id' => $this->job_id,
            'slug' => $this->slug,
            'job_title' => $this->job_title,
            'industry_name' => $this->industry?->name,
            'industry_logo' => $logo,
            'status' => $status,
            'announcement_start_date' => optional($this->announcement_start_date)?->toDateString(),
            'announcement_end_date' => optional($this->announcement_end_date)?->toDateString(),
            'is_own_industry' => $isOwnIndustry,
            'badges' => array_values(array_filter([
                $this->formatBadge($this->employment_type),
                $this->formatBadge($this->work_mode),
                $this->formatBadge($this->network_type),
            ])),
            'short_description' => Str::limit(strip_tags((string) $this->job_description), 110),
            'location' => $locationString,
            'views_count' => (int) ($this->views_count ?? 0),
            'applications_count' => (int) ($this->applications_count ?? 0),
            'rejection_reason' => $this->rejection_reason,
            'reviewed_at' => optional($this->reviewed_at)?->toDateTimeString(),
        ];
    }

    private function formatBadge(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        $cleaned = trim($value);

        return match (strtolower($cleaned)) {
            'full_time', 'full time', 'full-time' => 'Full-time',
            'part_time', 'part time', 'part-time' => 'Part-time',
            'short_term', 'short term', 'short-term' => 'Short-term',
            'on_site', 'on site', 'on-site', 'onsite' => 'On-site',
            'remote' => 'Remote',
            'hybrid' => 'Hybrid',
            'contract' => 'Contract',
            'internship' => 'Internship',
            'psychology' => 'Psychology',
            'neuroscience' => 'Neuroscience',
            default => ucwords(str_replace(['_', '-'], ' ', $cleaned)),
        };
    }
}
