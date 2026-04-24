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

    public function timeline(Request $request)
    {
        $user = auth('api')->user();

        $perPage = $request->get('per_page', 10);

        // 🔥 get connection IDs
        $connectionIds = Connection::where(function ($q) use ($user) {
                $q->where('sender_id', $user->id)
                ->orWhere('receiver_id', $user->id);
            })
            ->where('status', Connection::STATUS_ACCEPTED)
            ->get()
            ->map(function ($conn) use ($user) {
                return $conn->sender_id == $user->id
                    ? $conn->receiver_id
                    : $conn->sender_id;
            })
            ->unique()
            ->toArray();

        // 🔥 get user groups
        $groupIds = $user->groups()->pluck('groups.id')->toArray();

        $posts = Post::with([
                'user:id,first_name,last_name,profile_image',
                'media',
                'groups:id,name'
            ])

            // 🔥 Visibility logic
            ->where(function ($q) use ($user, $connectionIds, $groupIds) {

                // own posts
                $q->where('user_id', $user->id)

                // connections posts
                ->orWhere(function ($q) use ($connectionIds) {
                    $q->where('visibility', 'connections')
                    ->whereIn('user_id', $connectionIds);
                })

                // public posts
                ->orWhere('visibility', 'public')

                // group posts (IMPORTANT)
                ->orWhere(function ($q) use ($groupIds) {
                    $q->where('visibility', 'groups')
                    ->whereHas('groups', function ($q) use ($groupIds) {
                        $q->whereIn('groups.id', $groupIds)
                            ->where('post_groups.remove_status', 0);
                    });
                });

            })

            // 🔥 latest first
            ->latest()

            // 🔥 pagination
            ->paginate($perPage);

        // 🔥 attach counts (LIKE FACEBOOK)
        $posts->getCollection()->transform(function ($post) {

            $post->likes_count = $post->likes()->count();

            $post->comments_count = Comment::where('post_id', $post->id)->count();

            $post->is_liked = $post->likes()
                ->where('user_id', auth('api')->id())
                ->exists();

            return $post;
        });

        return response()->json([
            'success' => true,
            'message' => 'Timeline fetched successfully',
            'data' => $posts
        ]);
    }


}
