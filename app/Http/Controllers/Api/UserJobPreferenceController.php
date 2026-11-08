<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserJobPreference;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserJobPreferenceController extends Controller
{
    /**
     * Get the authenticated user's job preferences.
     */
    public function getPreference(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $preference = UserJobPreference::with(['state:id,name,code,slug', 'city:id,name,state_id'])
            ->where('user_id', $user->id)
            ->first();

        return response()->json([
            'success' => true,
            'data' => $preference,
            'has_preference' => $preference && $preference->hasAnyPreference(),
        ], 200);
    }

    /**
     * Save or update the authenticated user's job preferences.
     * Every field is nullable so the user can provide only the preferences they want.
     */
    public function savePreference(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        $validated = $request->validate([
            'job_title' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'string', 'max:255'],
            'network_type' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:150'],
            'sub_category' => ['nullable', 'string', 'max:150'],
            'employment_offering' => ['nullable', 'string', 'max:100'],
            'work_mode' => ['nullable', 'string', 'max:100'],
            'employment_type' => ['nullable', 'string', 'max:100'],
            'level' => ['nullable', 'string', 'max:100'],
            'experience' => ['nullable', 'string', 'max:100'],
            'salary_type' => ['nullable', 'string', 'max:100'],
            'salary_min' => ['nullable', 'numeric', 'min:0'],
            'salary_max' => ['nullable', 'numeric', 'min:0'],
            'state_id' => ['nullable', 'integer', 'exists:states,id'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
        ]);

        // Clean string fields
        foreach ($validated as $key => $value) {
            if (is_string($value)) {
                $trimmed = trim($value);
                $validated[$key] = $trimmed === '' ? null : $trimmed;
            }
        }

        $preference = UserJobPreference::updateOrCreate(
            ['user_id' => $user->id],
            $validated
        );

        $preference->load(['state:id,name,code,slug', 'city:id,name,state_id']);

        return response()->json([
            'success' => true,
            'message' => 'Job preferences saved successfully.',
            'data' => $preference,
            'has_preference' => $preference->hasAnyPreference(),
        ], 200);
    }

    /**
     * Clear / reset the authenticated user's job preferences.
     */
    public function clearPreference(Request $request): JsonResponse
    {
        $user = auth('api')->user();

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        UserJobPreference::where('user_id', $user->id)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Job preferences reset successfully.',
            'data' => null,
            'has_preference' => false,
        ], 200);
    }
}
