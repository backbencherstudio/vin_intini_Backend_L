<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class FollowController extends Controller
{
    public function followers(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $search = trim((string) $request->query('search', ''));
        $followingIds = UserFollow::query()
            ->where('follower_id', $currentUser->id)
            ->pluck('following_id')
            ->all();

        $followers = UserFollow::query()
            ->where('following_id', $currentUser->id)
            ->with('follower:id,first_name,last_name,title,profile_image')
            ->latest('id')
            ->get();

        $totalFollowers = $followers->count();

        $mutualConnections = $this->buildMutualConnectionsMap(
            $currentUser->id,
            $followers->pluck('follower_id')->values()
        );

        $filteredFollowers = $followers;

        if ($search !== '') {
            $normalizedSearch = mb_strtolower($search);

            $filteredFollowers = $followers
                ->filter(function (UserFollow $follow) use ($normalizedSearch): bool {
                    $fullName = trim(($follow->follower?->first_name ?? '') . ' ' . ($follow->follower?->last_name ?? ''));

                    return str_contains(mb_strtolower($fullName), $normalizedSearch);
                })
                ->values();
        }

        if ($filteredFollowers->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => $search !== '' ? 'No followers found for this search.' : 'You have no followers yet.',
                'status' => 'success',
                'total_followers' => $totalFollowers,
                'data' => [],
                'stats' => [
                    'total_followers' => $totalFollowers,
                    'filtered_followers' => 0,
                ],
                'total' => 0,
                'limit' => 0,
                'current_page' => 1,
                'total_page' => 0,
                'last_page' => 0,
                'filters' => [
                    'search' => $search !== '' ? $search : null,
                ],
            ], 200);
        }

        $data = $filteredFollowers->map(function (UserFollow $follow) use ($followingIds, $mutualConnections) {
            $mutualConnectionData = $mutualConnections[$follow->follower_id] ?? [
                'count' => 0,
                'preview' => [],
            ];

            return [
                'id' => $follow->id,
                'user' => $this->formatUser($follow->follower),
                'is_following_back' => in_array($follow->follower_id, $followingIds, true),
                'mutual_connections_count' => $mutualConnectionData['count'],
                'mutual_connections' => $mutualConnectionData['preview'],
                'followed_at' => optional($follow->created_at)?->toDateTimeString(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Followers retrieved successfully.',
            'status' => 'success',
            'total_followers' => $totalFollowers,
            'data' => $data,
            'stats' => [
                'total_followers' => $totalFollowers,
                'filtered_followers' => $data->count(),
            ],
            'total' => $data->count(),
            'limit' => $data->count(),
            'current_page' => 1,
            'total_page' => 1,
            'last_page' => 1,
            'filters' => [
                'search' => $search !== '' ? $search : null,
            ],
        ], 200);
    }

    public function following(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $search = trim((string) $request->query('search', ''));
        $followerIds = UserFollow::query()
            ->where('following_id', $currentUser->id)
            ->pluck('follower_id')
            ->all();

        $following = UserFollow::query()
            ->where('follower_id', $currentUser->id)
            ->with('following:id,first_name,last_name,title,profile_image')
            ->latest('id')
            ->get();

        $totalFollowing = $following->count();

        $mutualConnections = $this->buildMutualConnectionsMap(
            $currentUser->id,
            $following->pluck('following_id')->values()
        );

        $filteredFollowing = $following;

        if ($search !== '') {
            $normalizedSearch = mb_strtolower($search);

            $filteredFollowing = $following
                ->filter(function (UserFollow $follow) use ($normalizedSearch): bool {
                    $fullName = trim(($follow->following?->first_name ?? '') . ' ' . ($follow->following?->last_name ?? ''));

                    return str_contains(mb_strtolower($fullName), $normalizedSearch);
                })
                ->values();
        }

        if ($filteredFollowing->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => $search !== '' ? 'No following users found for this search.' : 'You are not following anyone yet.',
                'status' => 'success',
                'total_following' => $totalFollowing,
                'data' => [],
                'stats' => [
                    'total_following' => $totalFollowing,
                    'filtered_following' => 0,
                ],
                'total' => 0,
                'limit' => 0,
                'current_page' => 1,
                'total_page' => 0,
                'last_page' => 0,
                'filters' => [
                    'search' => $search !== '' ? $search : null,
                ],
            ], 200);
        }

        $data = $filteredFollowing->map(function (UserFollow $follow) use ($followerIds, $mutualConnections) {
            $mutualConnectionData = $mutualConnections[$follow->following_id] ?? [
                'count' => 0,
                'preview' => [],
            ];

            return [
                'id' => $follow->id,
                'user' => $this->formatUser($follow->following),
                'is_followed_back' => in_array($follow->following_id, $followerIds, true),
                'mutual_connections_count' => $mutualConnectionData['count'],
                'mutual_connections' => $mutualConnectionData['preview'],
                'followed_at' => optional($follow->created_at)?->toDateTimeString(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Following users retrieved successfully.',
            'status' => 'success',
            'total_following' => $totalFollowing,
            'data' => $data,
            'stats' => [
                'total_following' => $totalFollowing,
                'filtered_following' => $data->count(),
            ],
            'total' => $data->count(),
            'limit' => $data->count(),
            'current_page' => 1,
            'total_page' => 1,
            'last_page' => 1,
            'filters' => [
                'search' => $search !== '' ? $search : null,
            ],
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

    private function buildMutualConnectionsMap(int $currentUserId, Collection $counterpartIds): array
    {
        if ($counterpartIds->isEmpty()) {
            return [];
        }

        $currentFollowingIds = UserFollow::query()
            ->where('follower_id', $currentUserId)
            ->pluck('following_id')
            ->unique()
            ->values();

        if ($currentFollowingIds->isEmpty()) {
            return [];
        }

        $mutualFollowRows = UserFollow::query()
            ->whereIn('follower_id', $counterpartIds->all())
            ->whereIn('following_id', $currentFollowingIds->all())
            ->get(['follower_id', 'following_id']);

        $mutualUserIds = $mutualFollowRows
            ->pluck('following_id')
            ->unique()
            ->values();

        if ($mutualUserIds->isEmpty()) {
            return [];
        }

        $mutualUsers = User::query()
            ->whereIn('id', $mutualUserIds->all())
            ->get(['id', 'first_name', 'last_name', 'title', 'profile_image'])
            ->keyBy('id');

        $mutualConnections = [];

        foreach ($counterpartIds as $counterpartId) {
            $counterpartMutualIds = $mutualFollowRows
                ->where('follower_id', $counterpartId)
                ->pluck('following_id')
                ->unique()
                ->values();

            $preview = $counterpartMutualIds
                ->map(function (int $userId) use ($mutualUsers) {
                    return $mutualUsers->get($userId);
                })
                ->filter()
                ->map(function (User $user) {
                    return $this->formatUser($user);
                })
                ->take(1)
                ->values();

            $mutualConnections[$counterpartId] = [
                'count' => $counterpartMutualIds->count(),
                'preview' => $preview,
            ];
        }

        return $mutualConnections;
    }
}
