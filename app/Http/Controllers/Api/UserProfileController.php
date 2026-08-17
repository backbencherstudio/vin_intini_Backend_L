<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Institution;
use App\Models\LoginActivity;
use App\Models\Skill;
use App\Models\User;
use App\Services\ProfileImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load([
            'profile.currentPosition',
            'profile.currentInstitute',
            'educations.institution',
            'experiences.company',
        ]);

        $isOwnProfile = true;

        $totalConnections = Connection::query()
            ->accepted()
            ->forUser($user->id)
            ->count();

        $skills = Skill::query()
            ->select(['id', 'name'])
            ->whereIn('id', $user->profile?->skills_id ?? [])
            ->orderBy('name')
            ->get();

        // profile objects
        $currentPositionCompany = $user->profile?->currentPosition; // Company
        $currentInstitute = $user->profile?->currentInstitute;

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'is_own_profile' => $isOwnProfile,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'title' => $user->title,
                'about' => $user->profile?->about,
                'country' => $user->profile?->country,
                'total_connections' => $totalConnections,

                'current_position_id' => $user->profile?->current_position_id,
                'current_institute_id' => $user->profile?->current_institute_id,

                'current_position' => $currentPositionCompany ? [
                    'id' => $currentPositionCompany->id,
                    'name' => $currentPositionCompany->name,
                ] : null,

                'current_institute' => $currentInstitute ? [
                    'id' => $currentInstitute->id,
                    'name' => $currentInstitute->name,
                ] : null,

                'profile_image_url' => $user->profile_image_url,
                'cover_image_url' => $user->cover_image_url,

                'skills' => $skills,

                'experiences' => $user->experiences->map(function ($experience) {
                    return [
                        'id' => $experience->id,
                        'company_id' => $experience->company_id,
                        'company' => [
                            'id' => $experience->company?->id,
                            'name' => $experience->company?->name,
                        ],
                        'title' => $experience->title,
                        'start_date' => $experience->start_date,
                        'end_date' => $experience->end_date,
                        'is_current' => $experience->is_current,
                        'status' => $experience->formatted_end_date_attribute,
                        'description' => $experience->description,
                        'skills' => $experience->skills_data,
                    ];
                })->values(),

                'educations' => $user->educations->map(function ($education) {
                    return [
                        'id' => $education->id,
                        'institution_id' => $education->institution_id,
                        'institution' => [
                            'id' => $education->institution?->id,
                            'name' => $education->institution?->name,
                        ],
                        'degree' => $education->degree,
                        'field_study' => $education->field_study,
                        'start_month' => $education->start_month,
                        'start_year' => $education->start_year,
                        'end_month' => $education->end_month,
                        'end_year' => $education->end_year,
                        'grade' => $education->grade,
                        'description' => $education->description,
                        'activities' => $education->activities,
                        'is_current' => $education->is_current,
                        'status' => $education->status,
                        'skills' => $education->skills_data,
                    ];
                })->values(),
            ],
        ], 200);
    }

    public function showUserProfile(Request $request, $id)
    {
        $currentUser = $request->user();

        $user = User::with([
            'profile.currentPosition',
            'profile.currentInstitute',
            'educations.institution',
            'experiences.company',
        ])->find($id);

        if (! $user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User profile not found',
            ], 404);
        }

        $isOwnProfile = $currentUser->id === $user->id;

        $state = 'not_connected';
        $actionLabel = 'Connect';
        $pendingRequestId = null;

        if ($isOwnProfile) {
            $state = 'self';
            $actionLabel = 'Edit Profile';
        } else {
            $connection = Connection::query()
                ->where(function ($query) use ($currentUser, $user) {
                    $query->where('sender_id', $currentUser->id)
                        ->where('receiver_id', $user->id);
                })
                ->orWhere(function ($query) use ($currentUser, $user) {
                    $query->where('sender_id', $user->id)
                        ->where('receiver_id', $currentUser->id);
                })
                ->first();

            if ($connection) {
                if ($connection->status === Connection::STATUS_ACCEPTED) {
                    $state = 'accepted';
                    $actionLabel = 'Connected';
                } elseif ($connection->status === Connection::STATUS_PENDING) {
                    if ($connection->sender_id === $currentUser->id) {
                        $state = 'pending_sent';
                        $actionLabel = 'Pending';
                        $pendingRequestId = $connection->id;
                    } else {
                        $state = 'pending_received';
                        $actionLabel = 'Accept';
                        $pendingRequestId = $connection->id;
                    }
                }
            }

            $isFollowing = $currentUser->following()->where('following_id', $user->id)->exists();
        }

        $totalConnections = Connection::query()
            ->accepted()
            ->forUser($user->id)
            ->count();

        $skills = Skill::query()
            ->select(['id', 'name'])
            ->whereIn('id', $user->profile?->skills_id ?? [])
            ->orderBy('name')
            ->get();

        $currentPosition = $user->profile?->currentPosition;
        $currentInstitute = $user->profile?->currentInstitute;

        return response()->json([
            'status' => 'success',
            'data' => [
                'is_own_profile' => $isOwnProfile,
                'connection_status' => [
                    'state' => $state,
                    'action_label' => $actionLabel,
                    'pending_request_id' => $pendingRequestId,
                    'is_connected' => $state === 'accepted',
                    'is_following' => $isFollowing,
                ],

                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'title' => $user->title,
                'about' => $user->profile?->about,
                'profile_image_url' => $user->profile_image_url,
                'cover_image_url' => $user->cover_image_url,
                'country' => $user->profile?->country,
                'total_connections' => $totalConnections,
                'current_position' => $currentPosition ? [
                    'id' => $currentPosition->id,
                    'company_name' => $currentPosition->name,
                ] : null,
                'current_institute' => $currentInstitute ? [
                    'id' => $currentInstitute->id,
                    'name' => $currentInstitute->name,
                ] : null,
                'skills' => $skills,
                'experiences' => $user->experiences->map(function ($experience) {
                    return [
                        'id' => $experience->id,
                        'company_id' => $experience->company_id,
                        'company' => [
                            'id' => $experience->company?->id,
                            'name' => $experience->company?->name,
                        ],
                        'title' => $experience->title,
                        'start_date' => $experience->start_date,
                        'end_date' => $experience->end_date,
                        'is_current' => $experience->is_current,
                        'status' => $experience->formatted_end_date_attribute,
                        'description' => $experience->description,
                        'skills' => $experience->skills_data,
                    ];
                })->values(),
                'educations' => $user->educations->map(function ($education) {
                    return [
                        'id' => $education->id,
                        'institution_id' => $education->institution_id,
                        'institution' => [
                            'id' => $education->institution?->id,
                            'name' => $education->institution?->name,
                        ],
                        'degree' => $education->degree,
                        'field_study' => $education->field_study,
                        'start_month' => $education->start_month,
                        'start_year' => $education->start_year,
                        'end_month' => $education->end_month,
                        'end_year' => $education->end_year,
                        'grade' => $education->grade,
                        'description' => $education->description,
                        'activities' => $education->activities,
                        'is_current' => $education->is_current,
                        'status' => $education->status,
                        'skills' => $education->skills_data,
                    ];
                })->values(),
            ],
        ], 200);
    }

    public function setupProfile(Request $request, ProfileImageService $profileImageService)
    {
        if ($request->has('group_ids') && is_string($request->group_ids)) {
            $request->merge([
                'group_ids' => explode(',', $request->group_ids),
            ]);
        }

        $request->merge([
            'notify_jobs' => filter_var($request->notify_jobs, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'notify_publications' => filter_var($request->notify_publications, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'notify_residency' => filter_var($request->notify_residency, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'notify_offers' => filter_var($request->notify_offers, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'is_current'   => filter_var($request->is_current, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
        ]);

        $validated = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'title' => 'required|string|max:120',
            'country' => 'required',
            'profession' => 'required|string',
            'interests' => 'required|string',
            'study_category' => 'nullable|string',
            'field_study' => 'required|string',
            'postal_code' => 'nullable|string',
            'highest_degree' => 'nullable|string',
            'institution' => 'nullable|string',
            'graduation_year' => 'nullable|string',
            'about' => 'nullable|string|max:250',
            'skills' => 'nullable|array',
            'skills.*' => 'string',
            'current_position_id' => 'nullable|integer|exists:experiences,id',
            'current_institute_id' => 'nullable|integer|exists:institutions,id',

            // Education
            'start_month' => 'required_with:institution|nullable|string|in:January,February,March,April,May,June,July,August,September,October,November,December',
            'start_year'  => 'required_with:institution|nullable|integer|min:1900|max:' . (date('Y') + 10),
            'is_current'  => 'required_with:institution|nullable|boolean',
            'end_month'   => 'required_if:is_current,false,0|nullable|string|in:January,February,March,April,May,June,July,August,September,October,November,December',
            'end_year'    => 'required_if:is_current,false,0|nullable|integer|min:1900|max:' . (date('Y') + 10),

            'notify_jobs'         => 'nullable|boolean',
            'notify_publications' => 'nullable|boolean',
            'notify_residency' => 'nullable|boolean',
            'notify_offers' => 'nullable|boolean',
            'group_ids' => 'nullable|array',
            'group_ids.*' => 'exists:groups,id',
        ]);

        $user = $request->user();

        $professionArray = array_map('trim', explode(',', $request->profession));
        $interestsArray = array_map('trim', explode(',', $request->interests));
        $skillIds = [];

        if ($request->has('skills')) {
            foreach ($request->skills as $skillName) {
                $skill = Skill::firstOrCreate(['name' => trim($skillName)]);
                $skillIds[] = $skill->id;
            }
        }

        $currentPositionId = $this->resolveCurrentPositionId($request, $validated['current_position_id'] ?? null);
        $currentInstituteId = $this->resolveCurrentInstituteId($request, $validated['current_institute_id'] ?? null);

        $imagePath = $user->profile_image;
        if ($request->hasFile('profile_image')) {
            $imagePath = $profileImageService->storeUploaded(
                $request->file('profile_image'),
                $user->profile_image,
            );
        }

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'title' => $request->title,
            'profile_image' => $imagePath,
        ]);

        $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'country' => $request->country,
                'postal_code' => $request->postal_code,
                'profession' => $professionArray,
                'highest_degree' => $request->highest_degree,
                'study_category' => $request->study_category,
                'field_study' => $request->field_study,
                'institution' => $request->institution,
                'graduation_year' => $request->graduation_year,
                'interests' => $interestsArray,
                'skills_id' => $skillIds,
                'current_position_id' => $currentPositionId,
                'current_institute_id' => $currentInstituteId,
                'about' => $request->about,

                'notify_jobs' => $request->boolean('notify_jobs'),
                'notify_publications' => $request->boolean('notify_publications'),
                'notify_residency' => $request->boolean('notify_residency'),
                'notify_offers' => $request->boolean('notify_offers'),
            ]
        );

        if ($request->has('group_ids')) {
            $user->groups()->syncWithoutDetaching($request->group_ids);
        }

        $institutionName = trim((string) ($validated['institution'] ?? ''));
        $degree = trim((string) ($validated['highest_degree'] ?? ''));


        // $endYear = trim((string) ($validated['graduation_year'] ?? ''));

        if ($institutionName !== '' && $degree !== '') {
            $institution = Institution::query()->firstOrCreate(['name' => $institutionName]);

            $isCurrent = $request->boolean('is_current');

            Education::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'institution_id' => $institution->id,
                    'degree' => $degree,
                ],
                [
                    'field_study' => $validated['field_study'] ?? null,
                    'start_month' => $validated['start_month'],
                    'start_year'  => $validated['start_year'],
                    'end_month'   => $isCurrent ? null : $validated['end_month'],
                    'end_year'    => $isCurrent ? null : $validated['end_year'],
                    'is_current'  => $isCurrent,
                    'skills_id'   => $skillIds,
                ]
            );
        }


        return response()->json([
            'status' => 'success',
            'message' => 'Profile completed successfully!',
            'data' => $user->load('profile', 'groups:id,name,logo'),
        ], 200);
    }

    public function update(Request $request, ProfileImageService $profileImageService)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string',
            'last_name' => 'sometimes|required|string',
            'title' => 'sometimes|required|string|max:120',
            'country' => 'sometimes|required|string',
            'skills' => 'nullable|array|max:5',
            'skills.*' => 'string',
            // 'current_position_id' => 'nullable|integer|exists:experiences,id',
            // 'current_position_id' => 'nullable|integer|exists:companies,id',
            'current_position_id' => [
                'nullable',
                'integer',
                Rule::exists('experiences', 'company_id')->where(function ($query) use ($request) {
                    $query->where('user_id', $request->user()->id);
                }),
            ],
            'current_institute_id' => 'nullable|integer|exists:institutions,id',
            'about' => 'sometimes|required|string',
        ]);

        $user = $request->user();

        $userData = [];

        if (array_key_exists('first_name', $validated)) {
            $userData['first_name'] = $validated['first_name'];
        }

        if (array_key_exists('last_name', $validated)) {
            $userData['last_name'] = $validated['last_name'];
        }

        if (array_key_exists('title', $validated)) {
            $userData['title'] = $validated['title'];
        }

        if ($userData !== []) {
            $user->update($userData);
        }

        $profile = $user->profile()->firstOrCreate(['user_id' => $user->id]);
        $profileData = [];

        if (array_key_exists('country', $validated)) {
            $profileData['country'] = $validated['country'];
        }

        if (array_key_exists('skills', $validated)) {
            $skillIds = [];

            foreach ($validated['skills'] as $skillName) {
                $skill = Skill::firstOrCreate(['name' => trim($skillName)]);
                $skillIds[] = $skill->id;
            }

            $profileData['skills_id'] = $skillIds;
        }

        if (array_key_exists('current_position_id', $validated)) {
            $profileData['current_position_id'] = $validated['current_position_id'];
        }

        // if (array_key_exists('current_position_id', $validated)) {
        //     $profileData['current_position_id'] = $this->resolveCurrentPositionId(
        //         $request,
        //         $validated['current_position_id'],
        //     );
        // }

        if (array_key_exists('current_institute_id', $validated)) {
            $profileData['current_institute_id'] = $this->resolveCurrentInstituteId(
                $request,
                $validated['current_institute_id'],
            );
        }

        if (array_key_exists('about', $validated)) {
            $profileData['about'] = $validated['about'];
        }

        if ($profileData !== []) {
            $profile->update($profileData);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully!',
            'data' => $user->fresh()->load('profile'),
        ], 200);
    }

    public function updateImages(Request $request, ProfileImageService $profileImageService)
    {
        $request->validate([
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'cover_image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:5120',
        ]);

        if (! $request->hasFile('profile_image') && ! $request->hasFile('cover_image')) {
            return response()->json([
                'status' => 'error',
                'message' => 'At least one image file is required.',
                'errors' => [
                    'images' => ['Please provide profile_image or cover_image.'],
                ],
            ], 422);
        }

        $user = $request->user();
        $updateData = [];

        if ($request->hasFile('profile_image')) {
            $updateData['profile_image'] = $profileImageService->storeUploaded(
                $request->file('profile_image'),
                $user->profile_image,
                'profile_photos',
            );
        }

        if ($request->hasFile('cover_image')) {
            $updateData['cover_image'] = $profileImageService->storeUploaded(
                $request->file('cover_image'),
                $user->cover_image,
                'cover_photos',
            );
        }

        if ($updateData !== []) {
            $user->update($updateData);
        }

        $user = $user->fresh();

        return response()->json([
            'status' => 'success',
            'message' => 'Profile images updated successfully!',
            'data' => [
                'profile_image' => $user->profile_image,
                'profile_image_url' => $user->profile_image_url,
                'cover_image' => $user->cover_image,
                'cover_image_url' => $user->cover_image_url,
            ],
        ], 200);
    }

    private function resolveCurrentPositionId(Request $request, mixed $currentPositionId): ?int
    {
        if (! $currentPositionId) {
            return null;
        }

        $experienceExists = Experience::query()
            ->whereKey($currentPositionId)
            ->where('user_id', $request->user()->id)
            ->exists();

        if (! $experienceExists) {
            throw ValidationException::withMessages([
                'current_position_id' => ['The selected current position is invalid.'],
            ]);
        }

        return (int) $currentPositionId;
    }

    private function resolveCurrentInstituteId(Request $request, mixed $currentInstituteId): ?int
    {
        if (! $currentInstituteId) {
            return null;
        }

        $instituteExistsForUser = Education::query()
            ->where('user_id', $request->user()->id)
            ->where('institution_id', $currentInstituteId)
            ->exists();

        if (! $instituteExistsForUser) {
            throw ValidationException::withMessages([
                'current_institute_id' => ['The selected current institute is invalid.'],
            ]);
        }

        return (int) $currentInstituteId;
    }

    public function changePassword(Request $request)
    {
        // 1. Validation
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => [
                'required',
                'confirmed',
                Password::min(8)->mixedCase()->numbers(), // Minimum 8 chars, mixed case, and numbers
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        // 2. Check if current password matches
        if (! Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'The current password you entered is incorrect.',
            ], 400);
        }

        // 3. Update the password
        $user->update([
            'password' => Hash::make($request->new_password),
        ]);

        // 4. Current token invalidation and logout other sessions
        $currentTokenId = auth('api')->payload()->get('jti');

        LoginActivity::where('user_id', $user->id)
            ->where('token_id', '!=', $currentTokenId)
            ->where('is_active', true)
            ->update(['is_active' => false]);
        // ========================================================

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully.',
        ], 200);
    }
}
