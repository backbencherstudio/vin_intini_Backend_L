<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\Post;
use App\Models\UserFollow;
use Illuminate\Http\Request;

class NewsfeedController extends Controller
{
    public function newsFeed(Request $request)
    {
        $user = auth('api')->user();

        $groupIds = $user->groups()->pluck('groups.id')->toArray();
        $adminGroupIds = $user->groups()
            ->wherePivot('role', 'admin')
            ->pluck('groups.id')
            ->toArray();

        $relationships = Connection::where(function ($q) use ($user) {
            $q->where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id);
        })
            ->whereHas('sender', fn($q) => $q->whereNull('deleted_at'))
            ->whereHas('receiver', fn($q) => $q->whereNull('deleted_at'))
            ->get();

        $relationshipMap = [];
        foreach ($relationships as $connection) {
            $otherUserId = $connection->sender_id == $user->id
                ? $connection->receiver_id
                : $connection->sender_id;
            $relationshipMap[$otherUserId] = $connection->status;
        }

        $connectionIds = collect($relationshipMap)
            ->filter(fn ($status) => $status === Connection::STATUS_ACCEPTED)
            ->keys();

        $followingIds = UserFollow::where('follower_id', $user->id)
            ->whereHas('following', fn($q) => $q->whereNull('deleted_at'))
            ->pluck('following_id');

        $unfollowedConnectionIds = $connectionIds->diff($followingIds);

        $posts = Post::query()
            ->whereHas('user', fn($q) => $q->whereNull('deleted_at'))
            ->with([
                'user:id,username,first_name,last_name,profile_image,title',
                'user.profile:user_id,privacy_profile_activity',
                'media',
                'groups:id,name,logo',
                'likes' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                },
            ])
            ->where(function ($query) use ($user, $groupIds, $connectionIds, $followingIds, $unfollowedConnectionIds) {

                $query->where('user_id', $user->id)

                    ->orWhere(function ($q) use ($connectionIds, $unfollowedConnectionIds) {
                        $q->where('visibility', 'public')
                            ->whereNotIn('user_id', $unfollowedConnectionIds)
                            ->whereHas('user.profile', function ($qProfile) use ($connectionIds) {
                                $qProfile->where(function ($qp) use ($connectionIds) {
                                    $qp->where('privacy_profile_activity', 'everyone')
                                        ->orWhere(function ($qp2) use ($connectionIds) {
                                            $qp2->where('privacy_profile_activity', 'only_connected')
                                                ->whereIn('user_profiles.user_id', $connectionIds);
                                        });
                                });
                            });
                    })

                    ->orWhere(function ($q) use ($connectionIds, $followingIds) {
                        $q->where('visibility', 'connections')
                            ->whereIn('user_id', $connectionIds)
                            ->whereIn('user_id', $followingIds)
                            ->whereHas('user.profile', function ($qProfile) {
                                $qProfile->where('privacy_profile_activity', '!=', 'nobody');
                            });
                    })

                    ->orWhere(function ($q) use ($groupIds, $user) {
                        $q->where('visibility', 'groups')
                            ->whereHas('groups', function ($q2) use ($groupIds, $user) {
                                $q2->whereIn('groups.id', $groupIds)
                                    ->whereHas('users', function ($q3) use ($user) {
                                        $q3->where('group_users.user_id', $user->id)
                                            ->where('group_users.status', '!=', 'banned');
                                    });
                            });
                    });
            })
            ->orderByDesc('id')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Feed fetched successfully',
            'data' => collect($posts->items())->map(function ($post) use ($user, $relationshipMap, $adminGroupIds) {
                if (!$post->user) return null;
                $canEdit = ($post->user_id === $user->id);
                $canDelete = ($post->user_id === $user->id);

                if (! $canDelete) {
                    foreach ($post->groups as $group) {
                        if (in_array($group->id, $adminGroupIds)) {
                            $canDelete = true;
                            break;
                        }
                    }
                }

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
                    'relationship_status' => $relationshipStatus,
                    'media' => $post->media,
                    'group' => $post->groups->first(),
                    'created_at' => $post->created_at,
                    'can_edit' => $canEdit,
                    'can_delete' => $canDelete,
                ];
            })->filter()->values(),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }

    // santos's code===================================
    // public function newsFeed(Request $request)
    // {
    //     $user = auth('api')->user();

    //     $groupIds = $user->groups()->pluck('groups.id');

    //     $relationships = Connection::where(function ($q) use ($user) {
    //         $q->where('sender_id', $user->id)
    //             ->orWhere('receiver_id', $user->id);
    //     })->get();

    //     $relationshipMap = [];

    //     foreach ($relationships as $connection) {

    //         $otherUserId = $connection->sender_id == $user->id
    //             ? $connection->receiver_id
    //             : $connection->sender_id;

    //         $relationshipMap[$otherUserId] = $connection->status;
    //     }

    //     $connectionIds = collect($relationshipMap)
    //         ->filter(fn ($status) => $status === Connection::STATUS_ACCEPTED)
    //         ->keys();

    //     $followingIds = UserFollow::where('follower_id', $user->id)
    //         ->pluck('following_id');

    //     $allowedConnectionIds = $connectionIds->intersect($followingIds);

    //     $posts = Post::query()
    //         ->with([
    //             'user:id,first_name,last_name,profile_image,title',
    //             'media',
    //             'groups:id,name,logo',
    //             'likes' => function ($q) use ($user) {
    //                 $q->where('user_id', $user->id);
    //             },
    //         ])
    //         ->where(function ($query) use (
    //             $user,
    //             $groupIds,
    //             $allowedConnectionIds
    //         ) {

    //             $query->where('user_id', $user->id)

    //                 ->orWhere(function ($query) use (
    //                     $groupIds,
    //                     $allowedConnectionIds,
    //                     $user
    //                 ) {

    //                     $query->where('visibility', 'public')

    //                         ->orWhere(function ($q) use ($allowedConnectionIds) {
    //                             $q->where('visibility', 'connections')
    //                                 ->whereIn('user_id', $allowedConnectionIds);
    //                         })

    //                         ->orWhere(function ($q) use ($groupIds, $user) {
    //                             $q->where('visibility', 'groups')
    //                                 ->whereHas('groups', function ($q2) use ($groupIds, $user) {
    //                                     $q2->whereIn('groups.id', $groupIds)
    //                                         ->whereHas('users', function ($q3) use ($user) {
    //                                             $q3->where('group_users.user_id', $user->id)
    //                                                 ->where('group_users.status', '!=', 'banned');
    //                                         });
    //                                 });
    //                         });
    //                 });
    //         })
    //         ->orderByDesc('id')
    //         ->paginate($request->get('per_page', 10));

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Feed fetched successfully',

    //         'data' => collect($posts->items())->map(function ($post) use (
    //             $user,
    //             $relationshipMap
    //         ) {

    //             $canEdit = ($post->user_id === $user->id);
    //             $canDelete = ($post->user_id === $user->id);

    //             foreach ($post->groups as $group) {
    //                 $isGroupAdmin = $group->users()
    //                     ->wherePivot('role', 'admin')
    //                     ->wherePivot('user_id', $user->id)
    //                     ->exists();

    //                 if ($isGroupAdmin && $post->user_id !== $user->id) {
    //                     $canDelete = true;
    //                     break;
    //                 }
    //             }

    //             if ($post->user_id === $user->id) {
    //                 $relationshipStatus = 'connected';
    //             } else {
    //                 $status = $relationshipMap[$post->user_id] ?? null;

    //                 $relationshipStatus = match ($status) {
    //                     Connection::STATUS_ACCEPTED => 'connected',
    //                     Connection::STATUS_PENDING => 'pending',
    //                     default => 'not_connected',
    //                 };
    //             }

    //             return [
    //                 'id' => $post->id,
    //                 'user' => $post->user,
    //                 'description' => $post->description,
    //                 'visibility' => $post->visibility,
    //                 'who_can_comment' => $post->who_can_comment,
    //                 'total_like' => $post->total_like ?? 0,
    //                 'total_comment' => $post->total_comment ?? 0,
    //                 'liked_by_me' => $post->likes->isNotEmpty(),
    //                 'relationship_status' => $relationshipStatus,
    //                 'media' => $post->media,
    //                 'group' => $post->groups->first(),
    //                 'created_at' => $post->created_at,
    //                 'can_edit' => $canEdit,
    //                 'can_delete' => $canDelete,
    //             ];
    //         }),

    //         'pagination' => [
    //             'current_page' => $posts->currentPage(),
    //             'per_page' => $posts->perPage(),
    //             'total' => $posts->total(),
    //             'last_page' => $posts->lastPage(),
    //         ],
    //     ]);
    // }
    // santos's code===================================

    public function singlePost($id)
    {
        $user = auth('api')->user();

        $groupIds = $user->groups()->pluck('groups.id');

        $relationships = Connection::where(function ($q) use ($user) {
            $q->where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id);
        })
            ->whereHas('sender', fn($q) => $q->whereNull('deleted_at'))
            ->whereHas('receiver', fn($q) => $q->whereNull('deleted_at'))
            ->get();

        $relationshipMap = [];

        foreach ($relationships as $connection) {

            $otherUserId = $connection->sender_id == $user->id
                ? $connection->receiver_id
                : $connection->sender_id;

            $relationshipMap[$otherUserId] = $connection->status;
        }

        $connectionIds = collect($relationshipMap)
            ->filter(fn ($status) => $status === Connection::STATUS_ACCEPTED)
            ->keys();

        $followingIds = UserFollow::where('follower_id', $user->id)
            ->whereHas('following', fn($q) => $q->whereNull('deleted_at'))
            ->pluck('following_id');

        $allowedConnectionIds = $connectionIds->intersect($followingIds);

        $post = Post::with([
            'user:id,username,first_name,last_name,profile_image,title',
            'media',
            'groups:id,name,logo',
            'likes' => function ($q) use ($user) {
                $q->where('user_id', $user->id);
            },
        ])
            ->where('id', $id)
            ->whereHas('user', fn($q) => $q->whereNull('deleted_at'))
            ->where(function ($query) use (
                $user,
                $groupIds,
                $allowedConnectionIds
            ) {

                $query->where('user_id', $user->id)

                    ->orWhere(function ($query) use (
                        $groupIds,
                        $allowedConnectionIds,
                        $user
                    ) {
                        $query->where('visibility', 'public')

                            ->orWhere(function ($q) use ($allowedConnectionIds) {
                                $q->where('visibility', 'connections')
                                    ->whereIn('user_id', $allowedConnectionIds);
                            })

                            ->orWhere(function ($q) use ($groupIds, $user) {
                                $q->where('visibility', 'groups')
                                    ->whereHas('groups', function ($q2) use ($groupIds, $user) {
                                        $q2->whereIn('groups.id', $groupIds)
                                            ->whereHas('users', function ($q3) use ($user) {
                                                $q3->where('group_users.user_id', $user->id)
                                                    ->where('group_users.status', '!=', 'banned');
                                            });
                                    });
                            });
                    });
            })
            ->first();

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found or access denied',
            ], 404);
        }

        $canEdit = $post->user_id === $user->id;
        $canDelete = $post->user_id === $user->id;

        foreach ($post->groups as $group) {

            $isGroupAdmin = $group->users()
                ->wherePivot('role', 'admin')
                ->wherePivot('user_id', $user->id)
                ->exists();

            if ($isGroupAdmin && $post->user_id !== $user->id) {
                $canDelete = true;
                break;
            }
        }

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

        return response()->json([
            'success' => true,
            'message' => 'Post fetched successfully',
            'data' => [
                'id' => $post->id,
                'user' => $post->user,
                'description' => $post->description,
                'visibility' => $post->visibility,
                'who_can_comment' => $post->who_can_comment,
                'total_like' => $post->total_like ?? 0,
                'total_comment' => $post->total_comment ?? 0,
                'liked_by_me' => $post->likes->isNotEmpty(),
                'relationship_status' => $relationshipStatus,
                'media' => $post->media,
                'group' => $post->groups->first(),
                'created_at' => $post->created_at,
                'can_edit' => $canEdit,
                'can_delete' => $canDelete,
            ],
        ]);
    }
}
