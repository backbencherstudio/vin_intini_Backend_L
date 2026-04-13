<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use App\Services\ProfileImageService;
use Illuminate\Http\Request;

class UserProfileController extends Controller
{
    public function show(Request $request)
    {
        $user = $request->user()->load('profile');

        $skills = Skill::query()
            ->select(['id', 'name'])
            ->whereIn('id', $user->profile?->skills_id ?? [])
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'title' => $user->title,
                'country' => $user->profile?->country,
                'skills' => $skills,
            ],
        ], 200);
    }

    public function setupProfile(Request $request, ProfileImageService $profileImageService)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'profile_image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'title' => 'required|string|max:120',
            'country' => 'required',
            'profession' => 'required|string',
            'interests' => 'required|string',
            'study_category' => 'required',
            'study_subcategory' => 'required',
            'postal_code' => 'nullable|string',
            'highest_degree' => 'nullable|string',
            'institution' => 'nullable|string',
            'graduation_year' => 'nullable|string',
            'about' => 'nullable|string|max:250',
            'skills' => 'nullable|array',
            'skills.*' => 'string',
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
                'study_subcategory' => $request->study_subcategory,
                'institution' => $request->institution,
                'graduation_year' => $request->graduation_year,
                'interests' => $interestsArray,
                'skills_id' => $skillIds,
                'about' => $request->about,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Profile completed successfully!',
            'data' => $user->load('profile'),
        ], 200);
    }

    public function update(Request $request, ProfileImageService $profileImageService)
    {
        $validated = $request->validate([
            'first_name' => 'sometimes|required|string',
            'last_name' => 'sometimes|required|string',
            'title' => 'sometimes|required|string|max:120',
            'country' => 'sometimes|required|string',
            'skills' => 'nullable|array',
            'skills.*' => 'string',
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

        if ($profileData !== []) {
            $profile->update($profileData);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Profile updated successfully!',
            'data' => $user->fresh()->load('profile'),
        ], 200);
    }
}
