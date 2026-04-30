<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Connection;
use App\Models\Post;
use App\Models\UserFollow;


class NewsfeedController extends Controller
{

    public function newsFeed(Request $request)
    {
        $user = auth('api')->user();

        $groupIds = $user->groups()->pluck('groups.id');

        $connectionIds = Connection::where('status', Connection::STATUS_ACCEPTED)
            ->where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)
                    ->orWhere('receiver_id', $user->id);
            })
            ->selectRaw("
                CASE
                    WHEN sender_id = ? THEN receiver_id
                    ELSE sender_id
                END as user_id
            ", [$user->id])
            ->pluck('user_id');

        $followingIds = UserFollow::where('follower_id', $user->id)
            ->pluck('following_id');

        $allowedConnectionIds = $connectionIds->intersect($followingIds);
        $posts = Post::query()
            ->with([
                'user:id,first_name,last_name,profile_image,title',
                'media',
                'groups:id,name,logo',
                'likes' => function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                }
            ])
            ->where(function ($query) use ($user, $groupIds, $allowedConnectionIds, $followingIds) {
                $query->where('user_id', $user->id)

                    ->orWhere(function ($query) use ($groupIds, $allowedConnectionIds, $followingIds, $user) {

                        $query->where(function ($q) use ($followingIds) {
                            $q->where('visibility', 'public');
                        })
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
            ->orderByDesc('id')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Feed fetched successfully',
            'data' => collect($posts->items())->map(function ($post) use ($connectionIds, $user) {

                $canEdit = ($post->user_id === $user->id);
                $canDelete = ($post->user_id === $user->id);

                foreach ($post->groups as $group) {
                    $isGroupAdmin = $group->users()->wherePivot('role', 'admin')->wherePivot('user_id', $user->id)->exists();
                    if ($isGroupAdmin) {
                        $canEdit = true;
                        $canDelete = true;
                        break;
                    }
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

                    'is_connected' =>
                        $post->user_id === $user->id
                            ? true
                            : $connectionIds->contains($post->user_id),

                    'media' => $post->media,
                    'groups' => $post->groups,
                    'created_at' => $post->created_at,
                    'can_edit' => $canEdit,
                    'can_delete' => $canDelete,
                ];
            }),

            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page'     => $posts->perPage(),
                'total'        => $posts->total(),
                'last_page'    => $posts->lastPage(),
            ]
        ]);
    }

}
