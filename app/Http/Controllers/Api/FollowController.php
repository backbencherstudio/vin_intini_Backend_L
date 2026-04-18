<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FollowController extends Controller
{
    public function followers(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $followingIds = UserFollow::query()
            ->where('follower_id', $currentUser->id)
            ->pluck('following_id')
            ->all();

        $followers = UserFollow::query()
            ->where('following_id', $currentUser->id)
            ->with('follower:id,first_name,last_name,title,profile_image')
            ->latest('id')
            ->get();

        if($followers->isEmpty()) {
            return response()->json([
                'status' => 'success',
                'message' => 'You have no followers yet.',
                'data' => [],
            ], 200);
        }

        return response()->json([
            'status' => 'success',
            'data' => $followers->map(function (UserFollow $follow) use ($followingIds) {
                return [
                    'id' => $follow->id,
                    'user' => $this->formatUser($follow->follower),
                    'is_following_back' => in_array($follow->follower_id, $followingIds, true),
                    'followed_at' => optional($follow->created_at)?->toDateTimeString(),
                ];
            })->values(),
        ], 200);
    }

    public function following(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $followerIds = UserFollow::query()
            ->where('following_id', $currentUser->id)
            ->pluck('follower_id')
            ->all();

        $following = UserFollow::query()
            ->where('follower_id', $currentUser->id)
            ->with('following:id,first_name,last_name,title,profile_image')
            ->latest('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $following->map(function (UserFollow $follow) use ($followerIds) {
                return [
                    'id' => $follow->id,
                    'user' => $this->formatUser($follow->following),
                    'is_followed_back' => in_array($follow->following_id, $followerIds, true),
                    'followed_at' => optional($follow->created_at)?->toDateTimeString(),
                ];
            })->values(),
        ], 200);
    }

    public function unfollow(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        UserFollow::query()
            ->where('follower_id', $currentUser->id)
            ->where('following_id', $user->id)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Unfollowed successfully.',
            'data' => [
                'user' => $this->formatUser($user),
                'is_following' => false,
            ],
        ], 200);
    }

    public function follow(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            throw ValidationException::withMessages([
                'user_id' => ['You cannot follow yourself.'],
            ]);
        }

        $follow = UserFollow::query()->firstOrCreate([
            'follower_id' => $currentUser->id,
            'following_id' => $user->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $follow->wasRecentlyCreated
                ? 'Followed successfully.'
                : 'You are already following this user.',
            'data' => [
                'user' => $this->formatUser($user),
                'is_following' => true,
            ],
        ], $follow->wasRecentlyCreated ? 201 : 200);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'title' => $user->title,
            'profile_image' => $user->profile_image,
            'profile_image_url' => $user->profile_image_url,
            'cover_image' => $user->cover_image,
            'cover_image_url' => $user->cover_image_url,
        ];
    }
}
