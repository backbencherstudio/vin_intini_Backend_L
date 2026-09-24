<?php

namespace App\Http\Controllers\Api;

use App\Enums\PlanType;
use App\Enums\JobApplicationStatus;
use App\Http\Controllers\Controller;
use App\Models\IndustryJobApplication;
use App\Models\IndustryJobPost;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IndustryJobApplicationController extends Controller
{
    public function myApplications(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $status = $request->query('status'); // pending, shortlisted, rejected, hired
        $limit = min($request->integer('limit', 10), 100);

        $query = IndustryJobApplication::query()
            ->where('applicant_id', $user->id)
            ->with([
                'job' => function ($q) {
                    $q->select('id', 'job_id', 'slug', 'job_title', 'position', 'work_mode', 'employment_type', 'salary_min', 'salary_max', 'industry_id', 'state_id', 'city_id')
                        ->with(['industry:id,name,slug,logo', 'state:id,name', 'city:id,name']);
                }
            ])
            ->latest('id');

        if (!empty($status)) {
            $query->where('status', $status);
        }

        $paginated = $query->paginate($limit);

        $formattedData = $paginated->getCollection()->map(function (IndustryJobApplication $application) {
            $job = $application->job;

            $logo = null;
            if ($job && $job->industry) {
                $rawLogo = $job->industry->logo ?? null;
                if ($rawLogo) {
                    $logo = str_starts_with($rawLogo, 'http') ? $rawLogo : asset('storage/' . ltrim($rawLogo, '/'));
                }
            }

            return [
                'id'               => $application->id,
                'application_id'   => $application->application_id,
                'application_type' => $application->application_type,
                'expected_salary'  => $application->expected_salary,
                'status'           => $application->status,
                'resume_url'       => $application->resume_url,
                'applied_at'       => $application->created_at->toDateTimeString(),
                'job'              => $job ? [
                    'id'              => $job->id,
                    'job_id'          => $job->job_id,
                    'slug'            => $job->slug,
                    'job_title'       => $job->job_title,
                    'position'        => $job->position,
                    'work_mode'       => $job->work_mode,
                    'employment_type' => $job->employment_type,
                    'salary_min'      => $job->salary_min,
                    'salary_max'      => $job->salary_max,
                    'industry'        => $job->industry ? [
                        'id'   => $job->industry->id,
                        'name' => $job->industry->name,
                        'slug' => $job->industry->slug,
                        'logo' => $logo,
                    ] : null,
                    'state'           => $job->state?->name,
                    'city'            => $job->city?->name,
                ] : null,
            ];
        });

        return response()->json([
            'success'      => true,
            'message'      => $paginated->isEmpty() ? 'No job applications found.' : 'Applications retrieved successfully.',
            'status'       => 'success',
            'data'         => $formattedData,
            'total'        => $paginated->total(),
            'limit'        => $paginated->perPage(),
            'current_page' => $paginated->currentPage(),
            'total_page'   => $paginated->lastPage(),
            'last_page'    => $paginated->lastPage(),
        ], 200);
    }

    public function applyJob(Request $request, $jobId): JsonResponse
    {
        $user = auth('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        // 1. Premium Plan Check
        $premiumPlan = PlanType::tryFrom('premium');
        if (!$user->hasActiveSubscription($premiumPlan)) {
            return response()->json([
                'success' => false,
                'message' => 'You must have an active Premium subscription plan to apply for this job.',
            ], 403);
        }

        // 2. Job Post Check
        $jobPost = IndustryJobPost::find($jobId);
        if (!$jobPost) {
            return response()->json([
                'success' => false,
                'message' => 'Job post not found.',
            ], 404);
        }

        // Nijer job e nije apply korte parbe na
        if ((int) $jobPost->created_by === (int) $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot apply to your own job post.',
            ], 422);
        }

        // Already applied check
        $alreadyApplied = IndustryJobApplication::where('job_id', $jobPost->id)
            ->where('applicant_id', $user->id)
            ->exists();

        if ($alreadyApplied) {
            return response()->json([
                'success' => false,
                'message' => 'You have already applied for this job position.',
            ], 422);
        }

        // 3. Validation
        $validated = $request->validate([
            'application_type' => ['nullable', 'in:custom,quick'],
            'full_name'        => ['required', 'string', 'max:255'],
            'email'            => ['required', 'email', 'max:255'],
            'phone_number'     => ['nullable', 'string', 'max:30'],
            'experiences'      => ['nullable', 'string', 'max:255'],
            'current_position' => ['nullable', 'string', 'max:255'],
            'expected_salary'  => ['nullable', 'numeric', 'min:0'],
            'location'         => ['nullable', 'string', 'max:255'],
            'linkedin_url'     => ['nullable', 'url', 'max:255'],
            'portfolio_url'    => ['nullable', 'url', 'max:255'],
            'cover_letter'     => ['nullable', 'string', 'max:5000'],
            'about_yourself'   => ['nullable', 'string', 'max:5000'],
            'skills'           => ['nullable'], // String ba Array duto-e support korbe
            'resume'           => ['required', 'file', 'mimes:pdf,doc,docx,jpg,png', 'max:10240'], // 10MB max
        ]);

        // Skills parse kora
        $skills = $this->formatSkills($validated['skills'] ?? null);

        // Resume upload kora
        $resumePath = null;
        if ($request->hasFile('resume')) {
            $resumePath = $request->file('resume')->store('resumes/industry_jobs', 'public');
        }

        // Auto-generate unique 6-digit application_id
        $applicationId = $this->generateUniqueApplicationId();

        $application = IndustryJobApplication::create([
            'application_id'   => $applicationId,
            'job_id'           => $jobPost->id,
            'applicant_id'     => $user->id,
            'application_type' => $validated['application_type'] ?? 'custom',
            'full_name'        => $validated['full_name'],
            'email'            => $validated['email'],
            'phone_number'     => $validated['phone_number'] ?? null,
            'experiences'      => $validated['experiences'] ?? null,
            'current_position' => $validated['current_position'] ?? null,
            'expected_salary'  => $validated['expected_salary'],
            'location'         => $validated['location'] ?? null,
            'linkedin_url'     => $validated['linkedin_url'] ?? null,
            'portfolio_url'    => $validated['portfolio_url'] ?? null,
            'cover_letter'     => $validated['cover_letter'] ?? null,
            'about_yourself'   => $validated['about_yourself'] ?? null,
            'skills'           => $skills,
            'resume_path'      => $resumePath,
            'status'           => JobApplicationStatus::PENDING->value,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your job application has been submitted successfully.',
            'data'    => [
                'id'               => $application->id,
                'application_id'   => $application->application_id,
                'job_id'           => $application->job_id,
                'applicant_id'     => $application->applicant_id,
                'application_type' => $application->application_type,
                'expected_salary'  => $application->expected_salary,
                'resume_url'       => $application->resume_url,
                'created_at'       => $application->created_at->toDateTimeString(),
            ],
        ], 201);
    }

    private function generateUniqueApplicationId(): string
    {
        do {
            $applicationId = (string) random_int(100000, 999999);
        } while (IndustryJobApplication::where('application_id', $applicationId)->exists());

        return $applicationId;
    }

    private function formatSkills(mixed $skills): ?array
    {
        if (is_string($skills)) {
            return array_values(array_filter(array_map('trim', explode(',', $skills))));
        }

        return is_array($skills) ? $skills : null;
    }



    /**
     * Nirdisto job-er shob applicant-der list dekha.
     * Access: System Admin, Original Job Creator, ebong Industry-r bortoman Owner.
     */
    public function jobApplicants(Request $request, $jobId): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        // Header data sajate dorkari relation load
        $jobPost = IndustryJobPost::with([
            'industry:id,name,logo,created_by',
            'state:id,name',
            'city:id,name'
        ])
            ->withCount('applications')
            ->find($jobId);

        if (! $jobPost) {
            return response()->json(['success' => false, 'message' => 'Job post not found.'], 404);
        }

        // Permission check
        $isAdmin = method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'));
        $isJobCreator = $jobPost->created_by && ((int) $jobPost->created_by === (int) $user->id);
        $isIndustryOwner = $jobPost->industry && ((int) $jobPost->industry->created_by === (int) $user->id);

        if (! $isAdmin && ! $isJobCreator && ! $isIndustryOwner) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view applicants for this job post.',
            ], 403);
        }

        // ----------------------------------------------------
        // Status Counts (Backend Enum Values Matching)
        // ----------------------------------------------------
        $rawCounts = IndustryJobApplication::where('job_id', $jobPost->id)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->toArray();

        $statusCounts = [
            'all'         => (int) $jobPost->applications_count,
            'pending'     => (int) ($rawCounts['pending'] ?? 0),
            'reviewing'   => (int) ($rawCounts['reviewing'] ?? 0),
            'shortlisted' => (int) ($rawCounts['shortlisted'] ?? 0),
            'interviewed' => (int) ($rawCounts['interviewed'] ?? 0),
            'offered'     => (int) ($rawCounts['offered'] ?? 0),
            'hired'       => (int) ($rawCounts['hired'] ?? 0),
            'rejected'    => (int) ($rawCounts['rejected'] ?? 0),
        ];

        // ----------------------------------------------------
        // Filter & Search Logic (Default Status: all)
        // ----------------------------------------------------
        $status = strtolower(trim((string) $request->query('status', 'all')));
        $search = trim((string) $request->query('search', ''));
        $page   = max($request->integer('current_page', 1), 1);
        $limit  = min(max($request->integer('limit', 10), 1), 100);

        $query = IndustryJobApplication::where('job_id', $jobPost->id)
            ->with(['applicant:id,first_name,last_name,username,email,profile_image'])
            ->latest('id');

        if ($status !== 'all' && $status !== '') {
            $query->where('status', $status);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                    ->orWhere('email', 'LIKE', "%{$search}%")
                    ->orWhere('application_id', 'LIKE', "%{$search}%");
            });
        }

        $paginated = $query->paginate($limit, ['*'], 'page', $page);

        // Table Data Format
        $formattedApplicants = $paginated->getCollection()->map(function (IndustryJobApplication $app) use ($jobPost) {
            $avatar = null;
            if ($app->applicant) {
                $rawImg = $app->applicant->profile_image_url ?? $app->applicant->profile_image;
                if ($rawImg) {
                    $avatar = str_starts_with($rawImg, 'http') ? $rawImg : asset('storage/' . ltrim($rawImg, '/'));
                }
            }

            return [
                'id'             => $app->id,
                'job_id'         => '#' . ($jobPost->job_id ?? $jobPost->id),
                'application_id' => $app->application_id,
                'applicant_name' => $app->full_name ?: trim(($app->applicant->first_name ?? '') . ' ' . ($app->applicant->last_name ?? '')),
                'email'          => $app->email ?: $app->applicant?->email,
                'avatar'         => $avatar,
                'position'       => $app->current_position ?: ($app->experiences ?? 'N/A'),
                'applied_on'     => $app->created_at ? $app->created_at->format('M d, Y') : 'N/A',
                'network'        => $jobPost->network_type ? ucfirst($jobPost->network_type) : 'N/A',
                'status'         => $app->status instanceof \BackedEnum ? $app->status->value : $app->status,
            ];
        });

        // Header Summary Data
        $industryLogo = null;
        if ($jobPost->industry) {
            $rawLogo = $jobPost->industry->logo ?? null;
            if ($rawLogo) {
                $industryLogo = str_starts_with($rawLogo, 'http') ? $rawLogo : asset('storage/' . ltrim($rawLogo, '/'));
            }
        }

        $locationParts = array_filter([$jobPost->state?->name, $jobPost->city?->name]);
        $locationStr = !empty($locationParts) ? implode(', ', $locationParts) : 'Remote';

        $jobSummary = [
            'id'            => $jobPost->id,
            'job_id'        => $jobPost->job_id,
            'slug'          => $jobPost->slug,
            'title_header'  => "{$jobPost->job_title}- " . ($jobPost->industry->name ?? 'Company') . "- Job ID:{$jobPost->job_id}",
            'website'       => $jobPost->website ?? ($jobPost->industry->website ?? null),
            'meta_subtitle' => "{$locationStr} (" . ucfirst($jobPost->work_mode ?? 'On-site') . ") • " . ($jobPost->created_at ? $jobPost->created_at->diffForHumans() : '') . " • {$jobPost->applications_count}+ Applicants",
            'industry_name' => $jobPost->industry->name ?? null,
            'industry_logo' => $industryLogo,
            'badges'        => array_values(array_filter([
                $jobPost->work_mode ? ucfirst($jobPost->work_mode) : null,
                $jobPost->employment_type ? ucwords(str_replace(['-', '_'], ' ', $jobPost->employment_type)) : null,
            ])),
        ];

        return response()->json([
            'success'        => true,
            'message'        => 'Job applicants retrieved successfully.',
            'job'            => $jobSummary,
            'status_counts'  => $statusCounts,
            'current_status' => $status,
            'data'           => $formattedApplicants,
            'total'          => $paginated->total(),
            'current_page'   => $paginated->currentPage(),
            'last_page'      => $paginated->lastPage(),
            'limit'          => $paginated->perPage(),
        ], 200);
    }


public function showApplication(Request $request, $id): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        $application = IndustryJobApplication::with([
            'job.industry',
            'applicant:id,first_name,last_name,username,email,profile_image'
        ])->find($id);

        if (! $application) {
            return response()->json(['success' => false, 'message' => 'Job application not found.'], 404);
        }

        $job = $application->job;
        $industry = $job?->industry;

        // Permission check
        $isAdmin = method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'));
        $isApplicant = (int) $application->applicant_id === (int) $user->id;
        $isJobCreator = $job && $job->created_by && ((int) $job->created_by === (int) $user->id);
        $isIndustryOwner = $industry && ((int) $industry->created_by === (int) $user->id);

        if (! $isAdmin && ! $isApplicant && ! $isJobCreator && ! $isIndustryOwner) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to view this application.',
            ], 403);
        }

        // ----------------------------------------------------
        // Dynamic Filter Navigation Logic
        // ----------------------------------------------------
        // Request theke status nibe (default 'all' dhora holo)
        $filterStatus = strtolower(trim((string) $request->query('status', 'all')));

        $baseSiblingQuery = function () use ($application, $filterStatus) {
            $query = IndustryJobApplication::where('job_id', $application->job_id);

            // Jodi 'all' chara nirdishto kono status ashe (jemon pending, reviewing)
            if ($filterStatus !== 'all' && $filterStatus !== '') {
                $query->where('status', $filterStatus);
            }

            return $query;
        };

        // 1. Previous ID ber kora[cite: 2]
        $prevId = $baseSiblingQuery()
            ->where('id', '>', $application->id)
            ->orderBy('id', 'asc')
            ->value('id');

        // 2. Next ID ber kora[cite: 2]
        $nextId = $baseSiblingQuery()
            ->where('id', '<', $application->id)
            ->orderBy('id', 'desc')
            ->value('id');

        // 3. Position count & total count[cite: 2]
        $totalCount  = $baseSiblingQuery()->count();
        $newerCount  = $baseSiblingQuery()->where('id', '>', $application->id)->count();
        $currentPos  = $newerCount + 1;

        $positionText = "Applicants {$currentPos} of {$totalCount}"; // UI: Applicants 340 of 3[cite: 2]

        // ----------------------------------------------------
        // Response Data Formatting
        // ----------------------------------------------------
        $applicantAvatar = null;
        if ($application->applicant) {
            $rawImg = $application->applicant->profile_image_url ?? $application->applicant->profile_image;
            if ($rawImg) {
                $applicantAvatar = str_starts_with($rawImg, 'http') ? $rawImg : asset('storage/' . ltrim($rawImg, '/'));
            }
        }

        $currentStatus = $application->status instanceof \BackedEnum
            ? $application->status->value
            : $application->status;

        $data = [
            'id'               => $application->id,
            'application_id'   => $application->application_id,
            'application_type' => $application->application_type,
            'full_name'        => $application->full_name ?: trim(($application->applicant->first_name ?? '') . ' ' . ($application->applicant->last_name ?? '')),
            'email'            => $application->email ?: $application->applicant?->email,
            'phone_number'     => $application->phone_number,
            'experiences'      => $application->experiences,
            'current_position' => $application->current_position,
            'expected_salary'  => $application->expected_salary,
            'location'         => $application->location,
            'linkedin_url'     => $application->linkedin_url,
            'portfolio_url'    => $application->portfolio_url,
            'cover_letter'     => $application->cover_letter,
            'about_yourself'   => $application->about_yourself,
            'skills'           => $application->skills ?? [],
            'resume_url'       => $application->resume_url,
            'status'           => $currentStatus,
            'applied_at'       => $application->created_at?->format('M d, Y'),
            'updated_at'       => $application->updated_at?->toDateTimeString(),

            // Dynamic Navigation details[cite: 2]
            'navigation' => [
                'active_tab'     => $filterStatus,
                'prev_id'        => $prevId,
                'next_id'        => $nextId,
                'position_label' => $positionText,
                'has_previous'   => ! is_null($prevId),
                'has_next'       => ! is_null($nextId),
            ],

            'applicant' => $application->applicant ? [
                'id'            => $application->applicant->id,
                'name'          => trim(($application->applicant->first_name ?? '') . ' ' . ($application->applicant->last_name ?? '')),
                'username'      => $application->applicant->username,
                'email'         => $application->applicant->email,
                'profile_image' => $applicantAvatar,
            ] : null,

            'job' => $job ? [
                'id'        => $job->id,
                'job_id'    => $job->job_id,
                'job_title' => $job->job_title,
                'position'  => $job->position,
                'work_mode' => $job->work_mode,
                'industry'  => $industry ? [
                    'id'   => $industry->id,
                    'name' => $industry->name ?? null,
                    'slug' => $industry->slug ?? null,
                ] : null,
            ] : null,
        ];

        return response()->json([
            'success' => true,
            'message' => 'Job application retrieved successfully.',
            'data'    => $data,
        ], 200);
    }


    /**
     * Nirdisto application-er status update kora.
     * Access: System Admin, Original Job Creator, ebong Industry-r bortoman Owner.
     */
    public function updateApplicationStatus(Request $request, $applicationId): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 401);
        }

        // Application er shathe job ebong industry nested relation load kora
        $application = IndustryJobApplication::with(['job.industry'])->find($applicationId);

        if (! $application) {
            return response()->json(['success' => false, 'message' => 'Application not found.'], 404);
        }

        $job = $application->job;
        $industry = $job?->industry;

        // Permission check
        $isAdmin = method_exists($user, 'hasRole') && ($user->hasRole('admin') || $user->hasRole('super-admin'));
        $isJobCreator = $job && $job->created_by && ((int) $job->created_by === (int) $user->id);
        $isIndustryOwner = $industry && ((int) $industry->created_by === (int) $user->id);

        if (! $isAdmin && ! $isJobCreator && ! $isIndustryOwner) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to update this application status.',
            ], 403);
        }

        $validated = $request->validate([
            'status' => ['required', Rule::enum(JobApplicationStatus::class)],
        ]);

        $application->update([
            'status' => $validated['status'],
        ]);

        return response()->json([
            'success' => true,
            'message' => "Application status updated to {$validated['status']} successfully.",
            'data'    => [
                'id'             => $application->id,
                'application_id' => $application->application_id,
                'status'         => $application->status instanceof \BackedEnum ? $application->status->value : $application->status,
                'updated_at'     => $application->updated_at->toDateTimeString(),
            ],
        ]);
    }
}
