<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Connection;
use App\Models\Post;


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

        $postsQuery = Post::query()
            ->with([
                'user:id,first_name,last_name,profile_image,title',
                'media',
                'groups:id,name'
            ])
            ->where(function ($query) use ($groupIds, $connectionIds) {

                $query->where('visibility', 'public')

                ->orWhere(function ($q) use ($connectionIds) {
                    $q->where('visibility', 'connections')
                    ->whereIn('user_id', $connectionIds);
                })

                ->orWhere(function ($q) use ($groupIds) {
                    $q->where('visibility', 'groups')
                    ->whereHas('groups', function ($q2) use ($groupIds) {
                        $q2->whereIn('groups.id', $groupIds);
                    });
                });
            });

        $posts = $postsQuery
            ->orderByDesc('id')
            ->paginate($request->get('per_page', 10));

        return response()->json([
            'success' => true,
            'message' => 'Feed fetched successfully',
            'data' => collect($posts->items())->map(function ($post) {
                return [
                    'id' => $post->id,
                    'user' => $post->user,
                    'description' => $post->description,
                    'visibility' => $post->visibility,
                    'who_can_comment' => $post->who_can_comment,

                    'total_like' => $post->total_like ?? 0,
                    'total_comment' => $post->total_comment ?? 0,

                    'media' => $post->media,
                    'groups' => $post->groups,
                    'created_at' => $post->created_at,
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
