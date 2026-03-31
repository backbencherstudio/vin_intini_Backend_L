<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use App\Models\Settings;
use App\Http\Requests\Settings\UpdatePlatformSettingsRequest;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SettingsController extends Controller
{
    public function getSettings(Request $request): JsonResponse
    {
        try {
            $user = $request->user(); // Authenticated user

            return response()->json([
                'success' => true,
                'data' => [
                    'user_settings' => [
                        'first_name'    => $user->first_name,
                        'last_name'     => $user->last_name,
                        'email'         => $user->email,
                        'profile_image' => $user->profile_image
                            ? asset('storage/' . $user->profile_image)
                            : null, // null safe
                        'new_order_e_notification' => $user->new_order_e_notification ?? false,
                    ],
                ]
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch settings',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    public function updateSettings(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'first_name' => ['string', 'max:255'],
                'last_name' => ['string', 'max:255'],
                'email' => ['email', 'max:255', 'unique:users,email,' . $user->id],
                'mobile' => ['unique:users,mobile,' . $user->id],
                'profile_image' => ['sometimes', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
                'remove_image' => ['sometimes', 'boolean'],
            ]);

            if ($request->boolean('remove_image')) {
                if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                    Storage::disk('public')->delete($user->profile_image);
                }

                $validated['profile_image'] = null;
            }

            if ($request->hasFile('profile_image')) {

                if ($user->profile_image && Storage::disk('public')->exists($user->profile_image)) {
                    Storage::disk('public')->delete($user->profile_image);
                }

                $filename = Str::uuid() . '.' . $request->file('profile_image')->extension();

                $path = $request->file('profile_image')->storeAs(
                    'uploads/admin',
                    $filename,
                    'public'
                );

                $validated['profile_image'] = $path;
            }

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Settings updated successfully',
                'data' => [
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'email' => $user->email,
                    'mobile' => $user->mobile,
                    'profile_image' => $user->profile_image
                        ? asset('storage/' . $user->profile_image)
                        : null
                ]
            ], 200);
        } catch (Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Failed to update settings',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function updateNotificationSettings(Request $request): JsonResponse
    {
        try {
            $user = $request->user();

            $validated = $request->validate([
                'new_order_e_notification' => ['sometimes', 'boolean'],
            ]);

            if (empty($validated)) {
                return response()->json([
                    'success' => false,
                    'message' => 'At least one notification setting must be provided.'
                ], 422);
            }

            $user->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Notification settings updated successfully.',
            ], 200);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update notification settings.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
