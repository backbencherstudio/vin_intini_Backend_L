<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Connection;
use App\Models\Comment;
use Illuminate\Support\Facades\DB;

class TimelineController extends Controller
{

    public function timeline(Request $request, $userId)
    {
        $authUser = auth('api')->user();

        $isOwnProfile = $authUser->id == $userId;

        $isConnected = false;

        if (!$isOwnProfile) {
            $isConnected = Connection::where('status', Connection::STATUS_ACCEPTED)
                ->where(function ($q) use ($authUser, $userId) {
                    $q->where(function ($q1) use ($authUser, $userId) {
                        $q1->where('sender_id', $authUser->id)
                            ->where('receiver_id', $userId);
                    })->orWhere(function ($q2) use ($authUser, $userId) {
                        $q2->where('sender_id', $userId)
                            ->where('receiver_id', $authUser->id);
                    });
                })
                ->exists();
        }

        $postsQuery = Post::query()
            ->with([
                'user:id,first_name,last_name,profile_image,title',
                'media'
            ])
            ->where('user_id', $userId)
            ->where(function ($query) use ($isOwnProfile, $isConnected) {

                if ($isOwnProfile) {
                    $query->whereIn('visibility', ['public', 'connections']);
                }

                else {

                    if ($isConnected) {
                        $query->whereIn('visibility', ['public', 'connections']);
                    }

                    else {
                        $query->where('visibility', 'public');
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

                    'liked_by_me' => $post->likes()
                        ->where('user_id', $authUser->id)
                        ->exists(),

                    'media' => $post->media,
                    // 'groups' => $post->groups,
                    'created_at' => $post->created_at,
                ];
            }),

            'meta' => [
                'is_own_profile' => $isOwnProfile,
                'is_connected' => $isOwnProfile ? true : $isConnected,
            ],

            'pagination' => [
                'current_page' => $posts->currentPage(),
                'per_page'     => $posts->perPage(),
                'total'        => $posts->total(),
                'last_page'    => $posts->lastPage(),
            ]
        ]);
    }


}
