<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use App\Models\Settings;
use App\Http\Requests\Settings\UpdatePlatformSettingsRequest;
use App\Models\User;
use App\Services\ProfileImageService;
use App\Models\LoginActivity;

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
                        'profile_image' => $user->profile_image_url,
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

    public function updateSettings(Request $request, ProfileImageService $profileImageService): JsonResponse
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
                $profileImageService->deleteIfLocal($user->profile_image);

                $validated['profile_image'] = null;
            }

            if ($request->hasFile('profile_image')) {
                $validated['profile_image'] = $profileImageService->storeUploaded(
                    $request->file('profile_image'),
                    $user->profile_image,
                );
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
                    'profile_image' => $user->profile_image_url,
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



    public function getLoginActivities(Request $request): JsonResponse
    {
        $perPage = $request->integer('limit', $request->integer('per_page', 10));
        $perPage = max(1, min($perPage, 100));
        $sortOrder = strtolower($request->query('sort', 'desc')) === 'asc' ? 'asc' : 'desc';

        $currentTokenId = auth('api')->check()
            ? auth('api')->payload()->get('jti')
            : session()->getId();

        $paginator = LoginActivity::where('user_id', auth()->id())
            ->orderByRaw("token_id = ? DESC", [$currentTokenId]) // Current device absolutely first
            ->orderByDesc('is_active') // Then other active sessions
            ->orderBy('login_at', $sortOrder) // Then by date
            ->paginate($perPage);

        $items = collect($paginator->items())->map(function ($activity) use ($currentTokenId) {
            return [
                'id' => $activity->id,
                'device' => $activity->device,
                'browser' => $activity->browser,
                'ip_address' => $activity->ip_address,
                'location' => $activity->location,
                'status' => $activity->status,
                'is_active' => (bool) $activity->is_active,

                'is_current' => $activity->token_id === $currentTokenId,

                'login_at' => $activity->login_at ? \Carbon\Carbon::parse($activity->login_at)->toISOString() : null,
                'created_at' => $activity->created_at ? $activity->created_at->toISOString() : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Login activities retrieved successfully.',
            'status' => 'success',
            'data' => [
                'items' => $items,
            ],
            'total' => $paginator->total(),
            'limit' => $perPage,
            'current_page' => $paginator->currentPage(),
            'total_page' => $paginator->lastPage(),
            'last_page' => $paginator->lastPage(),
            'filters' => [
                'sort' => $sortOrder,
            ],
        ], 200);
    }

    public function revokeSession($id)
    {
        $session = LoginActivity::where('id', $id)
            ->where('user_id', auth()->id())
            ->where('is_active', true)
            ->first();

        if (!$session) {
            return response()->json(['message' => 'Session not found or already revoked.'], 404);
        }

        $session->update(['is_active' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Device has been signed out successfully.'
        ]);
    }
}
