<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\Group;
use App\Models\GroupUser;
use App\Models\Post;
use Illuminate\Http\Request;

class TimelineController extends Controller
{
    public function timeline(Request $request, $userId)
    {
        $authUser = auth('api')->user();
        $isOwnProfile = $authUser->id == $userId;

        $targetUser = \App\Models\User::with('profile:user_id,privacy_profile_activity')
            ->where('username', $userId)
            ->orWhere('id', $userId)
            ->first();

        if (!$targetUser) {
            return response()->json(['success' => false, 'message' => 'User not found'], 404);
        }

        $actualId = $targetUser->id;
        $isOwnProfile = $authUser->id == $actualId;

        $privacy = $targetUser->profile->privacy_profile_activity ?? 'everyone';
        $relationshipStatus = 'not_connected';

        if ($isOwnProfile) {
            $relationshipStatus = 'connected';
        } else {
            $connection = Connection::where(function ($q) use ($authUser, $actualId ) {
                $q->where(function ($q1) use ($authUser, $actualId) {
                    $q1->where('sender_id', $authUser->id)->where('receiver_id', $actualId );
                })->orWhere(function ($q2) use ($authUser, $actualId) {
                    $q2->where('sender_id', $actualId)->where('receiver_id', $authUser->id);
                });
            })->first();

            if ($connection) {
                $relationshipStatus = match ($connection->status) {
                    Connection::STATUS_ACCEPTED => 'connected',
                    Connection::STATUS_PENDING => 'pending',
                    default => 'not_connected',
                };
            }
        }

        $isConnected = $relationshipStatus === 'connected';

        $postsQuery = Post::query()
            ->with([
                'user:id,username,first_name,last_name,profile_image,title',
                'media',
                'likes' => function ($q) use ($authUser) {
                    $q->where('user_id', $authUser->id);
                },
            ])
            ->where('user_id', $actualId);

        $postsQuery->where(function ($query) use ($isOwnProfile, $isConnected, $privacy) {
            if ($isOwnProfile) {
                $query->whereIn('visibility', ['public', 'connections', 'groups']);
            } else {
                if ($privacy === 'nobody') {
                    $query->whereRaw('1 = 0');
                } elseif ($privacy === 'only_connected') {
                    if ($isConnected) {
                        $query->whereIn('visibility', ['public', 'connections']);
                    } else {
                        $query->whereRaw('1 = 0');
                    }
                } else {
                    if ($isConnected) {
                        $query->whereIn('visibility', ['public', 'connections']);
                    } else {
                        $query->where('visibility', 'public');
                    }
                }
            }
        });

        $posts = $postsQuery
            ->orderByDesc('id')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Timeline fetched successfully',

            'data' => collect($posts->items())->map(function ($post) use ($authUser) {
                return [
                    'id' => $post->id,
                    'user' => $post->user,
                    'description' => $post->description,
                    'visibility' => $post->visibility,
                    'who_can_comment' => $post->who_can_comment,
                    'total_like' => $post->total_like ?? 0,
                    'total_comment' => $post->total_comment ?? 0,
                    'liked_by_me' => $post->likes->isNotEmpty(),
                    'media' => $post->media,
                    'created_at' => $post->created_at,
                ];
            }),

            'meta' => [
                'is_own_profile' => $isOwnProfile,
                'relationship_status' => $relationshipStatus,
            ],

            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }

    //santos's code===================================
    // public function timeline(Request $request, $userId)
    // {
    //     $authUser = auth('api')->user();

    //     $isOwnProfile = $authUser->id == $userId;

    //     $relationshipStatus = 'not_connected';

    //     if ($isOwnProfile) {
    //         $relationshipStatus = 'connected';
    //     } else {

    //         $connection = Connection::where(function ($q) use ($authUser, $userId) {
    //             $q->where(function ($q1) use ($authUser, $userId) {
    //                 $q1->where('sender_id', $authUser->id)
    //                     ->where('receiver_id', $userId);
    //             })->orWhere(function ($q2) use ($authUser, $userId) {
    //                 $q2->where('sender_id', $userId)
    //                     ->where('receiver_id', $authUser->id);
    //             });
    //         })->first();

    //         if ($connection) {
    //             $relationshipStatus = match ($connection->status) {
    //                 Connection::STATUS_ACCEPTED => 'connected',
    //                 Connection::STATUS_PENDING => 'pending',
    //                 default => 'not_connected',
    //             };
    //         }
    //     }

    //     $isConnected = $relationshipStatus === 'connected';

    //     $postsQuery = Post::query()
    //         ->with([
    //             'user:id,first_name,last_name,profile_image,title',
    //             'media',
    //         ])
    //         ->where('user_id', $userId)
    //         ->where(function ($query) use ($isOwnProfile, $isConnected) {

    //             if ($isOwnProfile) {
    //                 $query->whereIn('visibility', ['public', 'connections']);
    //             } else {

    //                 if ($isConnected) {
    //                     $query->whereIn('visibility', ['public', 'connections']);
    //                 } else {
    //                     $query->where('visibility', 'public');
    //                 }
    //             }
    //         });

    //     $posts = $postsQuery
    //         ->orderByDesc('id')
    //         ->paginate($request->get('per_page', 10));

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Timeline fetched successfully',

    //         'data' => collect($posts->items())->map(function ($post) use ($authUser) {
    //             return [
    //                 'id' => $post->id,
    //                 'user' => $post->user,
    //                 'description' => $post->description,
    //                 'visibility' => $post->visibility,
    //                 'who_can_comment' => $post->who_can_comment,

    //                 'total_like' => $post->total_like ?? 0,
    //                 'total_comment' => $post->total_comment ?? 0,

    //                 'liked_by_me' => $post->likes()
    //                     ->where('user_id', $authUser->id)
    //                     ->exists(),

    //                 'media' => $post->media,
    //                 'created_at' => $post->created_at,
    //             ];
    //         }),

    //         'meta' => [
    //             'is_own_profile' => $isOwnProfile,

    //             'relationship_status' => $relationshipStatus,
    //         ],

    //         'pagination' => [
    //             'current_page' => $posts->currentPage(),
    //             'per_page' => $posts->perPage(),
    //             'total' => $posts->total(),
    //             'last_page' => $posts->lastPage(),
    //         ],
    //     ]);
    // }
    //santos's code===================================

    public function groupPosts(Request $request, $groupId)
    {
        $user = auth('api')->user();

        $group = Group::findOrFail($groupId);

        $membership = GroupUser::where('group_id', $groupId)
            ->where('user_id', $user->id)
            ->first();

        $isCreator = $group->creator_id === $user->id;

        $isMember = $isCreator || ($membership && $membership->status !== 'banned');

        if ($group->type === 'private' && ! $isMember) {
            return response()->json([
                'success' => false,
                'message' => 'This group is private',
            ], 403);
        }

        if (! $isCreator && $membership && $membership->status === 'banned') {
            return response()->json([
                'success' => false,
                'message' => 'You are banned from this group',
            ], 403);
        }

        $relationships = Connection::where(function ($q) use ($user) {
            $q->where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id);
        })->get();

        $relationshipMap = [];

        foreach ($relationships as $connection) {

            $otherUserId = $connection->sender_id === $user->id
                ? $connection->receiver_id
                : $connection->sender_id;

            $relationshipMap[$otherUserId] = $connection->status;
        }

        $posts = Post::query()
            ->with([
                'user:id,username,first_name,last_name,profile_image,title',
                'media',
                'groups:id,name',
                'likes' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                },
            ])
            ->where('visibility', 'groups')
            ->whereHas('groups', function ($q) use ($groupId) {
                $q->where('groups.id', $groupId);
            })
            ->latest()
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Group posts fetched successfully',

            'data' => collect($posts->items())->map(function ($post) use (
                $user,
                $relationshipMap
            ) {

                if ($post->user_id === $user->id) {
                    $relationshipStatus = 'connected';
                } else {

                    $status = $relationshipMap[$post->user_id] ?? null;

                    $relationshipStatus = match ($status) {
                        Connection::STATUS_ACCEPTED => 'connected',
                        Connection::STATUS_PENDING => 'pending',
                        default => 'not_connected',
                    };
                }

                return [
                    'id' => $post->id,
                    'user' => $post->user,
                    'description' => $post->description,
                    'visibility' => $post->visibility,
                    'who_can_comment' => $post->who_can_comment,

                    'total_like' => $post->total_like ?? 0,
                    'total_comment' => $post->total_comment ?? 0,

                    'liked_by_me' => $post->likes->isNotEmpty(),

                    'media' => $post->media,
                    'groups' => $post->groups,
                    'created_at' => $post->created_at,

                    'can_edit' => $post->user_id === $user->id,
                    'can_delete' => $post->user_id === $user->id,

                    'relationship_status' => $relationshipStatus,
                ];
            }),

            'meta' => [
                'group_id' => $group->id,
                'group_type' => $group->type,
                'is_creator' => $isCreator,
                'is_member' => $isMember,
            ],

            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }
}
