<?php

namespace App\Http\Resources;

use App\Enums\IndustryJobPostStatus;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin IndustryJobPost
 */
class IndustryJobPostResource extends JsonResource
{
    public function __construct(
        $resource,
        protected ?array $likedJobIds = null,
        protected ?array $savedJobIds = null,
        protected ?array $appliedJobs = null,
        protected mixed $currentUser = null
    ) {
        parent::__construct($resource);
    }

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentUser = $this->currentUser ?? auth('api')->user();

        $isLiked = ($this->likedJobIds !== null)
            ? isset($this->likedJobIds[$this->id])
            : $this->isLikedBy($currentUser);

        $isSaved = ($this->savedJobIds !== null)
            ? isset($this->savedJobIds[$this->id])
            : $this->isSavedBy($currentUser);

        $isApplied = false;
        $applicationStatus = null;

        if ($currentUser) {
            if ($this->appliedJobs !== null) {
                if (isset($this->appliedJobs[$this->id])) {
                    $isApplied = true;
                    $rawStatus = $this->appliedJobs[$this->id];
                    $applicationStatus = $rawStatus instanceof \BackedEnum ? $rawStatus->value : $rawStatus;
                }
            } else {
                $application = IndustryJobApplication::where('job_id', $this->id)
                    ->where('applicant_id', $currentUser->id)
                    ->first(['status']);

                if ($application) {
                    $isApplied = true;
                    $applicationStatus = $application->status instanceof \BackedEnum ? $application->status->value : $application->status;
                }
            }
        }

        $logo = null;
        if ($this->industry) {
            $rawLogo = $this->industry->logo ?? null;
            if ($rawLogo) {
                $logo = str_starts_with($rawLogo, 'http') ? $rawLogo : asset('storage/'.ltrim($rawLogo, '/'));
            }
        }

        return [
            'id' => $this->id,
            'job_id' => $this->job_id,
            'slug' => $this->slug,
            'job_title' => $this->job_title,
            'position' => $this->position,
            'category' => $this->category,
            'sub_category' => $this->sub_category,
            'job_description' => $this->job_description,
            'work_mode' => $this->work_mode,
            'employment_type' => $this->employment_type,
            'network_type' => $this->network_type,
            'level' => $this->level,
            'experience' => $this->experience,
            'employment_offering' => $this->employment_offering,
            'email' => $this->email,
            'phone_number' => $this->phone_number,
            'website' => $this->website,
            'salary_min' => $this->salary_min,
            'salary_max' => $this->salary_max,
            'salary_type' => $this->salary_type,
            'location_url' => $this->location_url,
            'tags' => $this->tags ?? [],
            'status' => ($this->status instanceof \BackedEnum ? $this->status->value : (string) $this->status) === IndustryJobPostStatus::PUBLISHED->value && $this->isExpired()
                ? IndustryJobPostStatus::EXPIRED->value
                : ($this->status instanceof \BackedEnum ? $this->status->value : $this->status),
            'information_confirmed' => (bool) $this->information_confirmed,

            'views_count' => (int) ($this->views_count ?? 0),
            'likes_count' => (int) ($this->likes_count ?? 0),
            'applications_count' => (int) ($this->applications_count ?? 0),
            'is_liked' => (bool) $isLiked,
            'is_saved' => (bool) $isSaved,
            'is_applied' => (bool) $isApplied,
            'application_status' => $applicationStatus,

            'state' => $this->state ? [
                'id' => $this->state->id,
                'name' => $this->state->name,
            ] : null,
            'city' => $this->city ? [
                'id' => $this->city->id,
                'name' => $this->city->name,
            ] : null,
            'industry' => $this->industry ? [
                'id' => $this->industry->id,
                'name' => $this->industry->name ?? null,
                'slug' => $this->industry->slug ?? null,
                'logo' => $logo,
            ] : null,
            'creator' => $this->creator ? [
                'id' => $this->creator->id,
                'name' => trim(($this->creator->first_name ?? '').' '.($this->creator->last_name ?? '')),
                'username' => $this->creator->username,
                'profile_image' => $this->creator->profile_image_url ?? $this->creator->profile_image,
            ] : null,
            'announcement_start_date' => optional($this->announcement_start_date)?->toDateString(),
            'announcement_end_date' => optional($this->announcement_end_date)?->toDateString(),
            'submitted_at' => optional($this->submitted_at)?->toDateTimeString(),
            'reviewed_at' => optional($this->reviewed_at)?->toDateTimeString(),
            'rejection_reason' => $this->rejection_reason,
            'created_at' => optional($this->created_at)?->toDateTimeString(),
        ];
    }
}
