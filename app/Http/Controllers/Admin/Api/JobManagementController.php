<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Api;

use App\Enums\IndustryJobPostStatus;
use App\Enums\JobApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use App\Models\User;
use App\Notifications\AdminCreatedJobNotification;
use App\Notifications\JobApplicationStatusUpdatedNotification;
use App\Notifications\JobRejectedNotification;
use App\Notifications\JobStatusUpdatedNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class JobManagementController extends Controller
{
    /**
     * List verified and active industries for admin job creation.
     */
    public function verifiedIndustries(Request $request): JsonResponse
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'industry_type' => ['nullable', 'string', 'max:50'],
        ]);

        $query = Industry::verifiedActive()
            ->with(['creator:id,first_name,last_name,username,email'])
            ->orderBy('name');

        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $query->where(function (Builder $q) use ($search): void {
                $q->where('name', 'LIKE', "%{$search}%")
                    ->orWhere('slug', 'LIKE', "%{$search}%")
                    ->orWhere('industry', 'LIKE', "%{$search}%");
            });
        }

        if ($request->filled('industry_type') && $request->input('industry_type') !== 'all') {
            $query->where('industry', 'LIKE', '%'.trim((string) $request->input('industry_type')).'%');
        }

        $industries = $query->get()->map(function (Industry $industry): array {
            $creator = $industry->creator;
            $ownerName = $creator
                ? trim(($creator->first_name ?? '').' '.($creator->last_name ?? ''))
                : null;
            $ownerName = $ownerName ?: ($creator?->username ?? 'Verified Partner');

            return [
                'id' => $industry->id,
                'name' => $industry->name,
                'slug' => $industry->slug,
                'industry' => $industry->industry,
                'logo_url' => $industry->logo,
                'website' => $industry->website,
                'owner' => $creator ? [
                    'id' => $creator->id,
                    'full_name' => $ownerName,
                    'email' => $creator->email,
                ] : null,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Verified industries retrieved successfully.',
            'data' => $industries,
        ]);
    }

    /**
     * List all jobs with stats, filters and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'status' => ['nullable', 'string'],
            'network_type' => ['nullable', 'string', 'in:all,psychology,neuroscience'],
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'work_mode' => ['nullable', 'string'],
            'state_id' => ['nullable'],
            'city_id' => ['nullable'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = IndustryJobPost::query()
            ->with([
                'industry:id,name,slug,logo,industry',
                'creator:id,first_name,last_name,username,email',
                'state:id,name',
                'city:id,name',
            ])
            ->withCount('applications');

        // Stats summary calculation before status filter
        $summary = $this->buildSummary();

        // Status Filter
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $status = strtolower(trim((string) $request->input('status')));
            $query->where('status', $status);
        }

        // Network Type Filter
        if ($request->filled('network_type') && $request->input('network_type') !== 'all') {
            $query->where('network_type', $request->input('network_type'));
        }

        // Industry Filter
        if ($request->filled('industry_id')) {
            $query->where('industry_id', $request->integer('industry_id'));
        }

        // Work Mode Filter
        if ($request->filled('work_mode') && $request->input('work_mode') !== 'all') {
            $mode = strtolower(trim((string) $request->input('work_mode')));
            if (in_array($mode, ['on_site', 'onsite', 'on-site'], true)) {
                $query->whereIn('work_mode', ['on_site', 'On-site', 'onsite', 'On-Site', 'Onsite']);
            } elseif ($mode === 'remote') {
                $query->whereIn('work_mode', ['remote', 'Remote']);
            } elseif ($mode === 'hybrid') {
                $query->whereIn('work_mode', ['hybrid', 'Hybrid']);
            } else {
                $query->where('work_mode', $request->input('work_mode'));
            }
        }

        // State Filter
        if ($request->filled('state_id') && $request->input('state_id') !== 'all') {
            $query->where('state_id', $request->input('state_id'));
        }

        // City Filter
        if ($request->filled('city_id') && $request->input('city_id') !== 'all') {
            $query->where('city_id', $request->input('city_id'));
        }

        // Search Filter
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $sanitized = addcslashes($search, '%_');

            $query->where(function (Builder $q) use ($search, $sanitized): void {
                $q->where('job_id', $search)
                    ->orWhere('job_id', 'LIKE', "{$sanitized}%")
                    ->orWhere('job_title', 'LIKE', "%{$sanitized}%")
                    ->orWhere('position', 'LIKE', "%{$sanitized}%")
                    ->orWhere('category', 'LIKE', "%{$sanitized}%")
                    ->orWhereHas('industry', function (Builder $iq) use ($sanitized): void {
                        $iq->where('name', 'LIKE', "%{$sanitized}%");
                    });
            });
        }

        $perPage = $request->integer('per_page', 15);
        $jobs = $query->orderByDesc('id')->paginate($perPage);

        return response()->json([
            'success' => true,
            'summary' => $summary,
            'data' => $jobs->items(),
            'meta' => [
                'current_page' => $jobs->currentPage(),
                'last_page' => $jobs->lastPage(),
                'per_page' => $jobs->perPage(),
                'total' => $jobs->total(),
            ],
        ]);
    }

    /**
     * Show single job details with applicant stats.
     */
    public function show(int|string $id): JsonResponse
    {
        $job = IndustryJobPost::with([
            'industry',
            'creator:id,first_name,last_name,username,email,profile_image',
            'state:id,name',
            'city:id,name',
        ])
            ->withCount('applications')
            ->find($id);

        if (! $job) {
            return response()->json([
                'success' => false,
                'message' => 'Job posting not found.',
            ], 404);
        }

        // Count applications per status
        $applicationCounts = IndustryJobApplication::where('job_id', $job->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return response()->json([
            'success' => true,
            'data' => $job,
            'application_stats' => [
                'total' => (int) array_sum($applicationCounts),
                'pending' => (int) ($applicationCounts['pending'] ?? 0),
                'reviewing' => (int) ($applicationCounts['reviewing'] ?? 0),
                'shortlisted' => (int) ($applicationCounts['shortlisted'] ?? 0),
                'interviewed' => (int) ($applicationCounts['interviewed'] ?? 0),
                'offered' => (int) ($applicationCounts['offered'] ?? 0),
                'hired' => (int) ($applicationCounts['hired'] ?? 0),
                'rejected' => (int) ($applicationCounts['rejected'] ?? 0),
            ],
        ]);
    }

    /**
     * Store new job post on behalf of an industry.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'industry_id' => ['required', 'integer', 'exists:industries,id'],
            'job_title' => ['required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'sub_category' => ['nullable', 'string', 'max:255'],
            'job_description' => ['required', 'string'],
            'work_mode' => ['required', 'string', 'in:remote,on_site,onsite,hybrid'],
            'employment_type' => ['required', 'string'],
            'level' => ['nullable', 'string'],
            'experience' => ['nullable', 'string'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'email' => ['required', 'email'],
            'phone_number' => ['nullable', 'string'],
            'website' => ['nullable', 'string'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0'],
            'salary_type' => ['nullable', 'string'],
            'location_url' => ['nullable', 'string', 'max:500'],
            'network_type' => ['nullable', 'string', 'in:psychology,neuroscience'],
            'employment_offering' => ['nullable', 'string'],
            'tags' => ['nullable'],
            'announcement_start_date' => ['nullable', 'date'],
            'announcement_end_date' => ['nullable', 'date'],
            'status' => ['nullable', 'string', 'in:published,pending,draft'],
        ]);

        $admin = $request->user();
        $industry = Industry::findOrFail($validated['industry_id']);

        $uniqueJobId = IndustryJobPost::generateUniqueJobId((int) $industry->id);
        $slug = Str::slug($validated['job_title']).'-'.$uniqueJobId;

        $tags = $this->formatTags($validated['tags'] ?? null);
        $status = $validated['status'] ?? 'published';

        $job = IndustryJobPost::create([
            'job_id' => $uniqueJobId,
            'industry_id' => $industry->id,
            'created_by' => $admin->id,
            'job_title' => $validated['job_title'],
            'slug' => $slug,
            'position' => $validated['position'] ?? null,
            'category' => $validated['category'] ?? null,
            'sub_category' => $validated['sub_category'] ?? null,
            'job_description' => $validated['job_description'],
            'work_mode' => $validated['work_mode'],
            'employment_type' => $validated['employment_type'],
            'level' => $validated['level'] ?? null,
            'experience' => $validated['experience'] ?? null,
            'state_id' => $validated['state_id'] ?? null,
            'city_id' => $validated['city_id'] ?? null,
            'email' => $validated['email'],
            'phone_number' => $validated['phone_number'] ?? null,
            'website' => $validated['website'] ?? null,
            'salary_min' => $validated['salary_min'] ?? null,
            'salary_max' => $validated['salary_max'] ?? null,
            'salary_type' => $validated['salary_type'] ?? null,
            'location_url' => $validated['location_url'] ?? null,
            'network_type' => $validated['network_type'] ?? null,
            'employment_offering' => $validated['employment_offering'] ?? null,
            'tags' => $tags,
            'announcement_start_date' => $validated['announcement_start_date'] ?? null,
            'announcement_end_date' => $validated['announcement_end_date'] ?? null,
            'status' => $status,
            'information_confirmed' => true,
            'submitted_at' => now(),
            'reviewed_at' => now(),
        ]);

        $job->load(['industry', 'creator', 'state:id,name', 'city:id,name']);

        // Notify industry owner
        $this->notifyIndustry($job, new AdminCreatedJobNotification($job, $admin));

        return response()->json([
            'success' => true,
            'message' => 'Job posted successfully on behalf of '.$industry->name.'. Industry owner has been notified.',
            'data' => $job,
        ], 201);
    }

    /**
     * Update job details by Admin.
     */
    public function update(Request $request, int|string $id): JsonResponse
    {
        $job = IndustryJobPost::findOrFail($id);

        $validated = $request->validate([
            'industry_id' => ['nullable', 'integer', 'exists:industries,id'],
            'status' => ['nullable', 'string', 'in:published,pending,draft,rejected,archive,expired'],
            'job_title' => ['sometimes', 'required', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:255'],
            'sub_category' => ['nullable', 'string', 'max:255'],
            'job_description' => ['sometimes', 'required', 'string'],
            'work_mode' => ['sometimes', 'required', 'string', 'in:remote,on_site,onsite,hybrid'],
            'employment_type' => ['sometimes', 'required', 'string'],
            'level' => ['nullable', 'string'],
            'experience' => ['nullable', 'string'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'email' => ['sometimes', 'required', 'email'],
            'phone_number' => ['nullable', 'string'],
            'website' => ['nullable', 'string'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0'],
            'salary_type' => ['nullable', 'string'],
            'location_url' => ['nullable', 'string', 'max:500'],
            'network_type' => ['nullable', 'string', 'in:psychology,neuroscience'],
            'employment_offering' => ['nullable', 'string'],
            'tags' => ['nullable'],
            'announcement_start_date' => ['nullable', 'date'],
            'announcement_end_date' => ['nullable', 'date'],
        ]);

        if (isset($validated['tags'])) {
            $validated['tags'] = $this->formatTags($validated['tags']);
        }

        if (isset($validated['job_title']) && $validated['job_title'] !== $job->job_title) {
            $validated['slug'] = Str::slug($validated['job_title']).'-'.$job->job_id;
        }

        $job->update($validated);
        $job->load(['industry', 'creator', 'state:id,name', 'city:id,name']);

        return response()->json([
            'success' => true,
            'message' => 'Job posting updated successfully.',
            'data' => $job,
        ]);
    }

    /**
     * Update job status (approve, publish, draft, archive, etc.).
     */
    public function updateStatus(Request $request, int|string $id): JsonResponse
    {
        $job = IndustryJobPost::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:published,pending,draft,rejected,archive,expired'],
        ]);

        $admin = $request->user();
        $previousStatus = (string) ($job->status->value ?? $job->status);
        $newStatus = $validated['status'];

        $job->status = $newStatus;
        $job->reviewed_at = now();

        if ($newStatus !== 'rejected') {
            $job->rejection_reason = null;
        }

        $job->save();
        $job->load(['industry', 'creator']);

        // Notify industry if restored from rejected or approved from pending
        if ($previousStatus !== $newStatus && ($previousStatus === 'rejected' || $previousStatus === 'pending')) {
            $this->notifyIndustry($job, new JobStatusUpdatedNotification($job, $previousStatus, $newStatus, $admin));
        }

        return response()->json([
            'success' => true,
            'message' => "Job status updated to {$newStatus} successfully.",
            'data' => $job,
        ]);
    }

    /**
     * Reject a job with reason.
     */
    public function reject(Request $request, int|string $id): JsonResponse
    {
        $job = IndustryJobPost::findOrFail($id);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $admin = $request->user();
        $reason = trim($validated['reason']);

        $job->status = IndustryJobPostStatus::REJECTED;
        $job->rejection_reason = $reason;
        $job->reviewed_at = now();
        $job->save();

        $job->load(['industry', 'creator']);

        // Send notification to industry owner
        $this->notifyIndustry($job, new JobRejectedNotification($job, $reason, $admin));

        return response()->json([
            'success' => true,
            'message' => 'Job posting has been rejected. The industry owner has been notified.',
            'data' => $job,
        ]);
    }

    /**
     * Delete job post.
     */
    public function destroy(int|string $id): JsonResponse
    {
        $job = IndustryJobPost::findOrFail($id);
        $job->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job posting deleted successfully.',
        ]);
    }

    /**
     * List applicants for a specific job with stage filters and stats.
     */
    public function applicants(Request $request, int|string $id): JsonResponse
    {
        $job = IndustryJobPost::with('industry:id,name,logo')->findOrFail($id);

        $request->validate([
            'status' => ['nullable', 'string'],
            'search' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = IndustryJobApplication::where('job_id', $job->id)
            ->with(['applicant:id,first_name,last_name,username,email,profile_image']);

        // Overall stats counts
        $rawCounts = IndustryJobApplication::where('job_id', $job->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $stats = [
            'all' => (int) array_sum($rawCounts),
            'pending' => (int) ($rawCounts['pending'] ?? 0),
            'reviewing' => (int) ($rawCounts['reviewing'] ?? 0),
            'shortlisted' => (int) ($rawCounts['shortlisted'] ?? 0),
            'interviewed' => (int) ($rawCounts['interviewed'] ?? 0),
            'offered' => (int) ($rawCounts['offered'] ?? 0),
            'hired' => (int) ($rawCounts['hired'] ?? 0),
            'rejected' => (int) ($rawCounts['rejected'] ?? 0),
        ];

        // Filter by stage / status
        if ($request->filled('status') && $request->input('status') !== 'all') {
            $query->where('status', strtolower(trim((string) $request->input('status'))));
        }

        // Search
        if ($request->filled('search')) {
            $search = trim((string) $request->input('search'));
            $sanitized = addcslashes($search, '%_');

            $query->where(function (Builder $q) use ($search, $sanitized): void {
                $q->where('full_name', 'LIKE', "%{$sanitized}%")
                    ->orWhere('email', 'LIKE', "%{$sanitized}%")
                    ->orWhere('current_position', 'LIKE', "%{$sanitized}%")
                    ->orWhere('application_id', $search);
            });
        }

        $perPage = $request->integer('per_page', 15);
        $applicants = $query->orderByDesc('id')->paginate($perPage);

        // Transform with resume url
        $items = collect($applicants->items())->map(function ($app) {
            $arr = $app->toArray();
            $arr['resume_url'] = $app->resume_url;
            return $arr;
        });

        return response()->json([
            'success' => true,
            'job' => [
                'id' => $job->id,
                'job_id' => $job->job_id,
                'job_title' => $job->job_title,
                'position' => $job->position,
                'work_mode' => $job->work_mode,
                'industry' => $job->industry,
            ],
            'stats' => $stats,
            'data' => $items,
            'meta' => [
                'current_page' => $applicants->currentPage(),
                'last_page' => $applicants->lastPage(),
                'per_page' => $applicants->perPage(),
                'total' => $applicants->total(),
            ],
        ]);
    }

    /**
     * Admin updates an applicant's stage and notifies the applicant.
     */
    public function updateApplicantStatus(Request $request, int|string $jobId, int|string $applicationId): JsonResponse
    {
        $application = IndustryJobApplication::where('job_id', $jobId)
            ->where('id', $applicationId)
            ->with(['job', 'applicant'])
            ->firstOrFail();

        $validated = $request->validate([
            'status' => ['required', Rule::enum(JobApplicationStatus::class)],
        ]);

        $admin = $request->user();
        $newStatus = $validated['status'] instanceof \BackedEnum
            ? $validated['status']->value
            : $validated['status'];

        $application->status = $newStatus;
        $application->save();

        // Notify applicant if user exists
        if ($application->applicant instanceof User) {
            $application->applicant->notify(
                new JobApplicationStatusUpdatedNotification($application, $newStatus, $admin)
            );
        }

        return response()->json([
            'success' => true,
            'message' => "Applicant stage updated to {$newStatus} successfully.",
            'data' => [
                'id' => $application->id,
                'application_id' => $application->application_id,
                'status' => $application->status instanceof \BackedEnum ? $application->status->value : $application->status,
                'updated_at' => $application->updated_at->toDateTimeString(),
            ],
        ]);
    }

    /**
     * Summary counts for dashboard metrics.
     */
    protected function buildSummary(): array
    {
        $counts = IndustryJobPost::query()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        return [
            'total' => (int) array_sum($counts),
            'published' => (int) ($counts['published'] ?? 0),
            'pending' => (int) ($counts['pending'] ?? 0),
            'rejected' => (int) ($counts['rejected'] ?? 0),
            'expired' => (int) ($counts['expired'] ?? 0),
            'draft' => (int) ($counts['draft'] ?? 0),
        ];
    }

    /**
     * Dispatch notification to industry owner and original job creator.
     */
    protected function notifyIndustry(IndustryJobPost $job, Notification $notification): void
    {
        $job->loadMissing(['creator', 'industry.creator']);

        $recipients = collect([$job->industry?->creator, $job->creator])
            ->filter(fn ($recipient): bool => $recipient instanceof User)
            ->unique('id');

        foreach ($recipients as $recipient) {
            $recipient->notify($notification);
        }
    }

    /**
     * Helper to format tags into array.
     */
    protected function formatTags(mixed $tags): ?array
    {
        if (is_array($tags)) {
            return array_values(array_filter(array_map('trim', $tags)));
        }

        if (is_string($tags)) {
            if (str_starts_with($tags, '[')) {
                $decoded = json_decode($tags, true);
                if (is_array($decoded)) {
                    return array_values(array_filter(array_map('trim', $decoded)));
                }
            }

            return array_values(array_filter(array_map('trim', explode(',', $tags))));
        }

        return null;
    }
}

