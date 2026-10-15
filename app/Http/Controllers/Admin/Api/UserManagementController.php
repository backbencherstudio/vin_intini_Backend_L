<?php

namespace App\Http\Controllers\Admin\Api;

use App\Enums\PlanType;
use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Connection;
use App\Models\DeletedAccountLog;
use App\Models\Education;
use App\Models\Experience;
use App\Models\LoginActivity;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\Skill;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    /**
     * Unified list endpoint supporting tier filtering via ?type=basic|premium|pro_industry|all.
     */
    public function index(Request $request): JsonResponse
    {
        $type = $request->input('type', 'all');
        $type = str_replace('-', '_', strtolower((string) $type));

        if ($type === 'industry' || $type === 'pro_industry') {
            $type = 'pro_industry';
        } elseif (! in_array($type, ['basic', 'premium', 'all'], true)) {
            $type = 'all';
        }

        return $this->getUsersByTier($request, $type);
    }

    /**
     * Display detailed user data for admin inspection (view-only).
     */
    public function show(int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::withTrashed()
            ->with([
                'roles',
                'profile.state',
                'profile.currentPosition',
                'profile.currentInstitute',
                'educations.institution',
                'experiences.company',
                'subscriptions.plan',
                'industry',
            ])
            ->findOrFail($id);

        $tier = $user->tier;

        $formatLogoUrl = function (?string $logo): ?string {
            if (! $logo) {
                return null;
            }
            if (preg_match('/^https?:\/\//i', $logo)) {
                return $logo;
            }

            return asset('storage/'.ltrim($logo, '/'));
        };

        $formatDuration = function ($startDate, $endDate, bool $isCurrent): ?string {
            if (! $startDate) {
                return null;
            }
            $start = Carbon::parse($startDate);
            $end = ($isCurrent || ! $endDate) ? now() : Carbon::parse($endDate);
            $diff = $start->diff($end);
            $parts = [];
            if ($diff->y > 0) {
                $parts[] = $diff->y.' '.($diff->y === 1 ? 'year' : 'years');
            }
            if ($diff->m > 0) {
                $parts[] = $diff->m.' '.($diff->m === 1 ? 'month' : 'months');
            }

            return implode(' ', $parts) ?: '1 month';
        };

        $skills = [];
        if (! empty($user->profile?->skills_id) && is_array($user->profile->skills_id)) {
            $skills = Skill::query()
                ->whereIn('id', $user->profile->skills_id)
                ->orderBy('name')
                ->get(['id', 'name'])
                ->toArray();
        }

        $state = $user->profile?->state?->name;
        $country = $user->profile?->country;
        $location = trim(implode(', ', array_filter([$state, $country]))) ?: null;

        $activeSub = $user->activeSubscription() ?? $user->subscriptions->sortByDesc('id')->first();
        $subData = $activeSub ? [
            'is_subscribed' => $activeSub->isActive(),
            'id' => $activeSub->id,
            'plan_id' => $activeSub->plan_id,
            'plan_name' => $activeSub->plan?->name,
            'plan_type' => $activeSub->plan?->plan_type instanceof PlanType ? $activeSub->plan->plan_type->value : $activeSub->plan?->plan_type,
            'billing_rate' => $activeSub->plan?->billing_rate,
            'billing_cycle' => $activeSub->plan?->billing_cycle,
            'status' => $activeSub->status,
            'current_period_start' => $activeSub->current_period_start?->toIso8601String(),
            'current_period_end' => $activeSub->current_period_end?->toIso8601String(),
            'cancel_at_period_end' => (bool) $activeSub->cancel_at_period_end,
            'features' => $activeSub->plan?->features ?? [],
        ] : [
            'is_subscribed' => false,
        ];

        $industryData = $user->industry ? [
            'id' => $user->industry->id,
            'name' => $user->industry->name,
            'slug' => $user->industry->slug,
            'industry' => $user->industry->industry,
            'website' => $user->industry->website,
            'address' => $user->industry->address,
            'company_size' => $user->industry->company_size,
            'tagline' => $user->industry->tagline,
            'description' => $user->industry->description,
            'logo' => $user->industry->logo,
            'cover_image' => $user->industry->cover_image,
            'views_count' => $user->industry->views_count,
            'created_at' => $user->industry->created_at?->toIso8601String(),
        ] : null;

        $connectionsCount = Connection::forUser($user->id)->accepted()->count();
        $followersCount = $user->followers()->count();
        $followingCount = $user->following()->count();
        $postsCount = $user->posts()->count();
        $commentsCount = $user->comments()->count();

        // Top-right banner badges (current company & graduated institution)
        $currentCompany = $user->profile?->currentPosition
            ?? $user->experiences->where('is_current', true)->first()?->company
            ?? $user->experiences->first()?->company;

        $graduatedInstitute = $user->profile?->currentInstitute
            ?? $user->educations->first()?->institution;

        $currentCompanyData = $currentCompany ? [
            'id' => $currentCompany->id,
            'name' => $currentCompany->name,
            'company_name' => $currentCompany->name,
            'logo' => $formatLogoUrl($currentCompany->logo),
            'logo_url' => $formatLogoUrl($currentCompany->logo),
            'website' => $currentCompany->website,
        ] : null;

        $graduatedInstituteData = $graduatedInstitute ? [
            'id' => $graduatedInstitute->id,
            'name' => $graduatedInstitute->name,
            'institution_name' => $graduatedInstitute->name,
            'logo' => $formatLogoUrl($graduatedInstitute->logo),
            'logo_url' => $formatLogoUrl($graduatedInstitute->logo),
            'website' => $graduatedInstitute->website,
        ] : null;

        // Activity: Recent posts & comments
        $recentPosts = $user->posts()
            ->with('media')
            ->latest('id')
            ->take(10)
            ->get();

        $recentComments = $user->comments()
            ->with('post')
            ->latest('id')
            ->take(10)
            ->get();

        $authorName = trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->username ?? 'User');
        $authorAvatar = $user->profile_image_url;
        $authorTitle = $user->title;

        $postsData = $recentPosts->map(function (Post $post) use ($user, $authorName, $authorAvatar, $authorTitle) {
            return [
                'id' => $post->id,
                'description' => $post->description,
                'created_at' => $post->created_at?->diffForHumans(),
                'created_at_formatted' => $post->created_at?->format('d M Y, h:i A'),
                'created_at_iso' => $post->created_at?->toIso8601String(),
                'total_like' => (int) ($post->total_like ?? 0),
                'total_comment' => (int) ($post->total_comment ?? 0),
                'author' => [
                    'id' => $user->id,
                    'name' => $authorName,
                    'title' => $authorTitle,
                    'avatar' => $authorAvatar,
                ],
                'media' => $post->media->map(function (PostMedia $media) {
                    return [
                        'id' => $media->id,
                        'url' => $media->url,
                        'type' => $media->type,
                        'sort_order' => $media->order,
                    ];
                })->values()->toArray(),
            ];
        });

        $commentsData = $recentComments->map(function (Comment $comment) {
            return [
                'id' => $comment->id,
                'post_id' => $comment->post_id,
                'comment' => $comment->comment,
                'image_url' => $comment->image_url,
                'like_count' => (int) ($comment->like_count ?? 0),
                'reply_count' => (int) ($comment->reply_count ?? 0),
                'created_at' => $comment->created_at?->diffForHumans(),
                'created_at_formatted' => $comment->created_at?->format('d M Y, h:i A'),
                'created_at_iso' => $comment->created_at?->toIso8601String(),
                'post' => $comment->post ? [
                    'id' => $comment->post->id,
                    'description_snippet' => Str::limit($comment->post->description, 100),
                ] : null,
            ];
        });

        $experiences = $user->experiences->map(function (Experience $exp) use ($user, $formatLogoUrl, $formatDuration) {
            $startFormatted = $exp->start_date ? Carbon::parse($exp->start_date)->format('M Y') : null;
            $endFormatted = $exp->is_current ? 'Still working' : ($exp->end_date ? Carbon::parse($exp->end_date)->format('M Y') : null);
            $duration = $formatDuration($exp->start_date, $exp->end_date, (bool) $exp->is_current);

            $periodParts = array_filter([$startFormatted, $endFormatted, $duration]);
            $formattedPeriod = implode(' • ', $periodParts);

            $jobTypeWithDuration = trim(implode(' • ', array_filter([$exp->employment_type, $duration])));

            $locParts = array_filter([$exp->location, $user->profile?->country, $exp->location_type]);
            $formattedLocation = implode(' • ', $locParts);

            return [
                'id' => $exp->id,
                'company_id' => $exp->company_id,
                'company_name' => $exp->company?->name,
                'company_logo_url' => $formatLogoUrl($exp->company?->logo),
                'company' => $exp->company ? [
                    'id' => $exp->company->id,
                    'name' => $exp->company->name,
                    'logo' => $formatLogoUrl($exp->company->logo),
                    'website' => $exp->company->website,
                ] : null,
                'title' => $exp->title,
                'employment_type' => $exp->employment_type,
                'job_type_with_period' => $jobTypeWithDuration ?: null,
                'start_date' => $exp->start_date?->format('Y-m-d'),
                'start_date_formatted' => $startFormatted,
                'end_date' => $exp->end_date?->format('Y-m-d'),
                'end_date_formatted' => $endFormatted,
                'duration' => $duration,
                'formatted_period' => $formattedPeriod ?: null,
                'is_current' => (bool) $exp->is_current,
                'location' => $exp->location,
                'country' => $user->profile?->country,
                'location_type' => $exp->location_type,
                'formatted_location' => $formattedLocation ?: null,
                'description' => $exp->description,
                'skills' => $exp->skills_data,
            ];
        });

        $educations = $user->educations->map(function (Education $edu) use ($formatLogoUrl) {
            $startYear = $edu->start_year;
            $endYear = $edu->is_current ? 'Present' : $edu->end_year;
            $formattedPeriod = trim(implode(' • ', array_filter([$startYear, $endYear])));
            $degreeWithField = trim(implode(' • ', array_filter([$edu->degree, $edu->field_study])));

            return [
                'id' => $edu->id,
                'institution_id' => $edu->institution_id,
                'institution_name' => $edu->institution?->name,
                'institution_logo_url' => $formatLogoUrl($edu->institution?->logo),
                'institution' => $edu->institution ? [
                    'id' => $edu->institution->id,
                    'name' => $edu->institution->name,
                    'logo' => $formatLogoUrl($edu->institution->logo),
                    'website' => $edu->institution->website,
                ] : null,
                'degree' => $edu->degree,
                'field_study' => $edu->field_study,
                'degree_with_field' => $degreeWithField ?: null,
                'start_month' => $edu->start_month,
                'start_year' => $edu->start_year,
                'end_month' => $edu->end_month,
                'end_year' => $edu->end_year,
                'formatted_period' => $formattedPeriod ?: null,
                'grade' => $edu->grade,
                'description' => $edu->description,
                'activities' => $edu->activities,
                'is_current' => (bool) $edu->is_current,
                'skills' => $edu->skills_data,
            ];
        });

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->username ?? 'No Name Provided'),
                'username' => $user->username,
                'title' => $user->title,
                'about' => $user->profile?->about,
                'email' => $user->email,
                'mobile' => $user->mobile,
                'profile_image_url' => $user->profile_image_url,
                'cover_image_url' => $user->cover_image_url,
                'is_verified' => (bool) $user->is_verified,
                'is_suspended' => (bool) $user->is_suspended,
                'status' => $user->is_suspended ? 'suspended' : 'active',
                'suspended_at' => $user->suspended_at?->format('d M Y, h:i A'),
                'tier' => $tier,
                'location' => $location,
                'total_connections' => $connectionsCount,
                'current_company' => $currentCompanyData,
                'graduated_institution' => $graduatedInstituteData,
                'member_since' => $user->created_at?->format('d M Y'),
                'terms_accepted_at' => $user->terms_accepted_at?->format('d M Y, h:i A'),
                'created_at' => $user->created_at?->toIso8601String(),
                'stats' => [
                    'posts_count' => $postsCount,
                    'comments_count' => $commentsCount,
                    'connections_count' => $connectionsCount,
                    'followers_count' => $followersCount,
                    'following_count' => $followingCount,
                ],
                'activity' => [
                    'total_posts' => $postsCount,
                    'total_comments' => $commentsCount,
                    'posts' => $postsData,
                    'comments' => $commentsData,
                ],
                'profile' => $user->profile ? [
                    'about' => $user->profile->about,
                    'country' => $user->profile->country,
                    'address' => $user->profile->address,
                    'postal_code' => $user->profile->postal_code,
                    'state' => $user->profile->state ? [
                        'id' => $user->profile->state->id,
                        'name' => $user->profile->state->name,
                    ] : null,
                    'profession' => $user->profile->profession,
                    'interests' => $user->profile->interests,
                    'highest_degree' => $user->profile->highest_degree,
                    'study_category' => $user->profile->study_category,
                    'field_study' => $user->profile->field_study,
                    'institution' => $user->profile->institution,
                    'graduation_year' => $user->profile->graduation_year,
                    'skills' => $skills,
                    'current_position' => $currentCompanyData,
                    'current_institute' => $graduatedInstituteData,
                ] : null,
                'experiences' => $experiences,
                'educations' => $educations,
                'subscription' => $subData,
                'industry' => $industryData,
            ],
        ], 200);
    }

    /**
     * Toggle or update suspension status of a user.
     */
    public function toggleSuspend(Request $request, int $id): JsonResponse
    {
        /** @var User $user */
        $user = User::withTrashed()->findOrFail($id);

        if ($user->hasRole('admin') || $user->email === 'admin@gmail.com') {
            return response()->json([
                'success' => false,
                'message' => 'Admin accounts cannot be suspended.',
            ], 403);
        }

        if ($request->has('is_suspended')) {
            $user->is_suspended = filter_var($request->input('is_suspended'), FILTER_VALIDATE_BOOLEAN);
        } elseif ($request->filled('status')) {
            $user->is_suspended = $request->input('status') === 'suspended';
        } else {
            $user->is_suspended = ! $user->is_suspended;
        }

        $user->suspended_at = $user->is_suspended ? now() : null;
        $user->save();

        if ($user->is_suspended) {
            LoginActivity::where('user_id', $user->id)->update(['is_active' => false]);
        }

        return response()->json([
            'success' => true,
            'message' => $user->is_suspended
                ? 'User has been suspended successfully.'
                : 'User has been unsuspended successfully.',
            'data' => [
                'id' => $user->id,
                'is_suspended' => (bool) $user->is_suspended,
                'status' => $user->is_suspended ? 'suspended' : 'active',
                'suspended_at' => $user->suspended_at?->toIso8601String(),
            ],
        ], 200);
    }

    /**
     * Get deleted account logs (Leave Requests).
     */
    public function getDeletedAccountLogs(Request $request): JsonResponse
    {
        $search = $request->input('search');

        $deletedAccounts = DeletedAccountLog::query()
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('user_name', 'like', "%{$search}%")
                        ->orWhere('user_email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate($request->input('per_page', 20));

        $data = $deletedAccounts->getCollection()->map(function ($account, $index) use ($deletedAccounts) {
            return [
                'sl_no' => (($deletedAccounts->currentPage() - 1) * $deletedAccounts->perPage()) + $index + 1,
                'id' => $account->id,
                'user_id' => $account->user_id,
                'full_name' => $account->user_name,
                'email' => $account->user_email,
                'reason' => $account->reason,
                'request_date' => Carbon::parse($account->requested_at)->format('d M Y'),
                'permanently_delete' => Carbon::parse($account->permanent_delete_at)->format('d M Y'),
                'remaining_days' => $account->permanent_delete_at
                    ? max(0, Carbon::now()->startOfDay()->diffInDays(
                        Carbon::parse($account->permanent_delete_at)->startOfDay(),
                        false
                    )).' Days'
                    : null,
            ];
        });

        return response()->json([
            'success' => true,
            'total_request' => DeletedAccountLog::count(),
            'data' => $data,

            'pagination' => [
                'current_page' => $deletedAccounts->currentPage(),
                'per_page' => $deletedAccounts->perPage(),
                'total' => $deletedAccounts->total(),
                'last_page' => $deletedAccounts->lastPage(),
                'from' => $deletedAccounts->firstItem(),
                'to' => $deletedAccounts->lastItem(),
            ],
        ]);
    }

    /**
     * Internal helper to retrieve paginated users by tier.
     */
    protected function getUsersByTier(Request $request, string $tier): JsonResponse
    {
        $search = $request->input('search');
        $status = $request->input('status');
        $perPage = (int) $request->input('per_page', 15);

        $baseQuery = $this->buildBaseUserQuery();
        $this->applyTierScope($baseQuery, $tier);

        // Apply filters to query
        $this->applySearch($baseQuery, $search);
        $this->applyStatusFilter($baseQuery, $status);

        $paginated = $baseQuery
            ->with(['profile.state', 'subscriptions.plan', 'industry'])
            ->latest('id')
            ->paginate($perPage)
            ->withQueryString();

        $rows = $paginated->getCollection()->map(function (User $user, int $index) use ($paginated, $tier) {
            $slNo = (($paginated->currentPage() - 1) * $paginated->perPage()) + $index + 1;

            return $this->formatUserListRow($user, $slNo, $tier);
        });

        return response()->json([
            'success' => true,
            'tier' => $tier,
            'total_users' => $paginated->total(),
            'data' => $rows,
            'pagination' => [
                'current_page' => $paginated->currentPage(),
                'per_page' => $paginated->perPage(),
                'total' => $paginated->total(),
                'last_page' => $paginated->lastPage(),
                'from' => $paginated->firstItem(),
                'to' => $paginated->lastItem(),
            ],
        ], 200);
    }

    /**
     * Build base query excluding admins.
     */
    protected function buildBaseUserQuery(): Builder
    {
        return User::query()
            ->whereDoesntHave('roles', fn (Builder $q) => $q->where('name', 'admin'))
            ->where('email', '!=', 'admin@gmail.com');
    }

    /**
     * Apply tier scope to query.
     */
    protected function applyTierScope(Builder $query, string $tier): void
    {
        if ($tier === 'pro_industry') {
            $query->where(function (Builder $q) {
                $q->whereHas('industry')
                    ->orWhereHas('subscriptions', function (Builder $sub) {
                        $sub->whereIn('status', ['active', 'trialing'])
                            ->where(function (Builder $period) {
                                $period->whereNull('current_period_end')
                                    ->orWhere('current_period_end', '>', now());
                            })
                            ->whereHas('plan', fn (Builder $p) => $p->where('plan_type', PlanType::INDUSTRY->value));
                    });
            });
        } elseif ($tier === 'premium') {
            $query->whereDoesntHave('industry')
                ->whereDoesntHave('subscriptions', function (Builder $sub) {
                    $sub->whereIn('status', ['active', 'trialing'])
                        ->where(function (Builder $period) {
                            $period->whereNull('current_period_end')
                                ->orWhere('current_period_end', '>', now());
                        })
                        ->whereHas('plan', fn (Builder $p) => $p->where('plan_type', PlanType::INDUSTRY->value));
                })
                ->whereHas('subscriptions', function (Builder $sub) {
                    $sub->whereIn('status', ['active', 'trialing'])
                        ->where(function (Builder $period) {
                            $period->whereNull('current_period_end')
                                ->orWhere('current_period_end', '>', now());
                        })
                        ->whereHas('plan', fn (Builder $p) => $p->where('plan_type', PlanType::PREMIUM->value));
                });
        } elseif ($tier === 'basic') {
            $query->whereDoesntHave('industry')
                ->whereDoesntHave('subscriptions', function (Builder $sub) {
                    $sub->whereIn('status', ['active', 'trialing'])
                        ->where(function (Builder $period) {
                            $period->whereNull('current_period_end')
                                ->orWhere('current_period_end', '>', now());
                        });
                });
        }
    }

    /**
     * Apply search keyword to user query.
     */
    protected function applySearch(Builder $query, ?string $search): void
    {
        if (! $search) {
            return;
        }

        $query->where(function (Builder $q) use ($search) {
            $q->where('first_name', 'like', "%{$search}%")
                ->orWhere('last_name', 'like', "%{$search}%")
                ->orWhere('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
                ->orWhere('mobile', 'like', "%{$search}%")
                ->orWhereRaw("CONCAT(COALESCE(first_name, ''), ' ', COALESCE(last_name, '')) LIKE ?", ["%{$search}%"]);
        });
    }

    /**
     * Apply status filter.
     */
    protected function applyStatusFilter(Builder $query, ?string $status): void
    {
        if (! $status || $status === 'all') {
            return;
        }

        if ($status === 'suspended') {
            $query->where('is_suspended', true);
        } elseif ($status === 'active') {
            $query->where('is_suspended', false);
        }
    }

    /**
     * Format a user model for table listing.
     */
    protected function formatUserListRow(User $user, int $slNo, string $tier): array
    {
        $state = $user->profile?->state?->name;
        $country = $user->profile?->country;
        $location = trim(implode(', ', array_filter([$state, $country]))) ?: null;

        $userTier = $tier === 'all' ? $user->tier : $tier;

        $activeSub = $user->activeSubscription() ?? $user->subscriptions->sortByDesc('id')->first();
        $planData = $activeSub && $activeSub->plan ? [
            'id' => $activeSub->plan->id,
            'name' => $activeSub->plan->name,
            'plan_type' => $activeSub->plan->plan_type instanceof PlanType ? $activeSub->plan->plan_type->value : $activeSub->plan->plan_type,
            'billing_rate' => $activeSub->plan->billing_rate,
            'billing_cycle' => $activeSub->plan->billing_cycle,
            'status' => $activeSub->status,
            'current_period_end' => $activeSub->current_period_end?->toIso8601String(),
            'cancel_at_period_end' => (bool) $activeSub->cancel_at_period_end,
        ] : null;

        $companyData = $user->industry ? [
            'id' => $user->industry->id,
            'name' => $user->industry->name,
            'slug' => $user->industry->slug,
            'industry' => $user->industry->industry,
            'logo' => $user->industry->logo,
            'website' => $user->industry->website,
            'company_size' => $user->industry->company_size,
        ] : null;

        return [
            'sl_no' => $slNo,
            'id' => $user->id,
            'full_name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')) ?: ($user->username ?? 'No Name Provided'),
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'username' => $user->username,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'profile_image_url' => $user->profile_image_url,
            'title' => $user->title,
            'location' => $location,
            'profession' => $user->profile?->profession,
            'status' => $user->is_suspended ? 'suspended' : 'active',
            'is_suspended' => (bool) $user->is_suspended,
            'suspended_at' => $user->suspended_at?->format('d M Y, h:i A'),
            'is_verified' => (bool) $user->is_verified,
            'terms_accepted_at' => $user->terms_accepted_at?->format('d M Y, h:i A'),
            'joined_at' => $user->created_at?->format('d M Y'),
            'created_at' => $user->created_at?->toIso8601String(),
            'tier' => $userTier,
            'plan' => $planData,
            'company' => $companyData,
        ];
    }
}
