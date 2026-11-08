<?php

namespace App\Services\Job;

use App\Enums\JobApplicationStatus;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\Skill;
use App\Models\User;
use App\Notifications\JobApplicationReceivedNotification;
use App\Notifications\JobApplicationStatusUpdatedNotification;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class JobApplicationService
{
    /**
     * Submit an application for an industry job post with safe file handling.
     *
     * @param  array<string, mixed>  $validated
     */
    public function apply(User $user, IndustryJobPost $jobPost, array $validated, ?UploadedFile $resumeFile = null): IndustryJobApplication
    {
        $resumePath = null;
        if ($resumeFile instanceof UploadedFile) {
            $resumePath = $resumeFile->store('resumes/industry_jobs', 'public');
        }

        try {
            $applicationId = $this->generateUniqueApplicationId((int) $jobPost->id);
            $isQuick = ($validated['application_type'] ?? 'custom') === 'quick';

            if ($isQuick) {
                $user->loadMissing(['profile.state']);

                $profileName = trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
                $fullName = ! empty($profileName) ? $profileName : ($validated['full_name'] ?? $user->username);
                $aboutYourself = $user->profile?->about ?? ($validated['about_yourself'] ?? null);

                $rawSkillIds = $user->profile?->skills_id ?? [];
                if (! empty($rawSkillIds)) {
                    $numericIds = array_filter($rawSkillIds, is_numeric(...));
                    $stringSkills = array_values(array_filter($rawSkillIds, fn ($s): bool => ! is_numeric($s)));
                    $fetchedSkills = $numericIds !== []
                        ? Skill::whereIn('id', $numericIds)->pluck('name')->toArray()
                        : [];
                    $skills = array_values(array_unique(array_merge($stringSkills, $fetchedSkills)));
                } else {
                    $skills = $this->formatSkills($validated['skills'] ?? null);
                }

                $currentPosition = $validated['current_position'] ?? $user->title;
                $stateName = $user->profile?->state?->name;
                $country = $user->profile?->country;
                $profileLocation = implode(', ', array_filter([$stateName, $country])) ?: null;
                $location = $validated['location'] ?? $profileLocation;
            } else {
                $fullName = $validated['full_name'] ?? $user->username;
                $aboutYourself = $validated['about_yourself'] ?? null;
                $skills = $this->formatSkills($validated['skills'] ?? null);
                $currentPosition = $validated['current_position'] ?? null;
                $location = $validated['location'] ?? null;
            }

            $application = IndustryJobApplication::create([
                'application_id' => $applicationId,
                'job_id' => $jobPost->id,
                'applicant_id' => $user->id,
                'application_type' => $validated['application_type'] ?? 'custom',
                'full_name' => $fullName,
                'email' => $validated['email'],
                'phone_number' => $validated['phone_number'] ?? null,
                'experiences' => $validated['experiences'] ?? null,
                'current_position' => $currentPosition,
                'expected_salary' => $validated['expected_salary'] ?? null,
                'location' => $location,
                'linkedin_url' => $validated['linkedin_url'] ?? null,
                'portfolio_url' => $validated['portfolio_url'] ?? null,
                'cover_letter' => $validated['cover_letter'] ?? null,
                'about_yourself' => $aboutYourself,
                'skills' => $skills,
                'resume_path' => $resumePath,
                'status' => JobApplicationStatus::PENDING->value,
            ]);

            $creator = $jobPost->creator;
            if ($creator) {
                $creator->notify(new JobApplicationReceivedNotification($application, $jobPost, $user));
            }

            return $application;
        } catch (\Throwable $e) {
            if ($resumePath && Storage::disk('public')->exists($resumePath)) {
                Storage::disk('public')->delete($resumePath);
            }

            throw $e;
        }
    }

    /**
     * Determine if an actor is authorized to update an application's status.
     */
    public function canManageApplication(User $actor, IndustryJobApplication $application): bool
    {
        $isAdmin = method_exists($actor, 'hasRole') && ($actor->hasRole('admin') || $actor->hasRole('super-admin'));
        if ($isAdmin) {
            return true;
        }

        $job = $application->job;
        if (! $job) {
            return false;
        }

        $isJobCreator = $job->created_by && ((int) $job->created_by === (int) $actor->id);
        $industry = $job->industry;
        $isIndustryOwner = $industry && $industry->created_by && ((int) $industry->created_by === (int) $actor->id);

        return $isJobCreator || $isIndustryOwner;
    }

    /**
     * Update an application's status and notify the applicant.
     */
    public function updateStatus(User $actor, IndustryJobApplication $application, string $newStatus): IndustryJobApplication
    {
        $previousStatus = $application->status instanceof \BackedEnum
            ? $application->status->value
            : (string) $application->status;

        $application->update([
            'status' => $newStatus,
        ]);

        if ($previousStatus !== $newStatus && $application->applicant) {
            $application->applicant->notify(
                new JobApplicationStatusUpdatedNotification($application, $application->job, $newStatus, $actor)
            );
        }

        return $application;
    }

    /**
     * Generate unique application ID within a job scope.
     */
    public function generateUniqueApplicationId(int $jobId): string
    {
        do {
            $id = 'APP-'.str_pad((string) random_int(1, 999999), 6, '0', STR_PAD_LEFT);
            $exists = IndustryJobApplication::where('job_id', $jobId)
                ->where('application_id', $id)
                ->exists();
        } while ($exists);

        return $id;
    }

    /**
     * Format skills helper.
     *
     * @return array<int, string>
     */
    public function formatSkills(mixed $skills): array
    {
        if (empty($skills)) {
            return [];
        }

        if (is_string($skills)) {
            $decoded = json_decode($skills, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $skills = $decoded;
            } else {
                $skills = explode(',', $skills);
            }
        }

        if (! is_array($skills)) {
            return [];
        }

        return array_values(array_filter(array_map(trim(...), $skills)));
    }
}
