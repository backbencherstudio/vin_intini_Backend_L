<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProfileImageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserProfileController extends Controller
{
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
        ]);

        $user = Auth::user();

        $professionArray = array_map('trim', explode(',', $request->profession));
        $interestsArray = array_map('trim', explode(',', $request->interests));

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
                'about' => $request->about,
            ]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Profile completed successfully!',
            'data' => $user->load('profile')
        ], 200);
    }
}
