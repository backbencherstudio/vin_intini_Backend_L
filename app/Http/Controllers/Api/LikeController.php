<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PostLike;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use App\Models\Connection;
use App\Models\GroupUser;
use App\Notifications\PostLikedNotification;


class LikeController extends Controller
{
    public function toggleLike(Request $request, $postId)
    {
        $user = auth('api')->user();

        $post = Post::with(['user', 'groups'])->findOrFail($postId);



        if ($post->visibility === 'connections') {

            $isConnected = Connection::where('status', Connection::STATUS_ACCEPTED)
                ->where(function ($q) use ($user, $post) {
                    $q->where(function ($q1) use ($user, $post) {
                        $q1->where('sender_id', $user->id)
                        ->where('receiver_id', $post->user_id);
                    })->orWhere(function ($q2) use ($user, $post) {
                        $q2->where('sender_id', $post->user_id)
                        ->where('receiver_id', $user->id);
                    });
                })
                ->exists();

            if (!$isConnected) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not connected to like this post'
                ], 403);
            }
        }

        $group = null;

        if ($post->visibility === 'groups') {

            $group = $post->groups->first();

            if (!$group) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid group post'
                ], 400);
            }

            if ($group->type === 'private') {
                $isMember = GroupUser::where('user_id', $user->id)
                    ->where('group_id', $group->id)
                    ->exists();

                if (!$isMember) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not a member of this group'
                    ], 403);
                }
            }
        }

        // ---------------------------------
        // 🔁 2. ATOMIC LIKE TOGGLE
        // ---------------------------------

        DB::beginTransaction();

        try {

            // Lock row (prevents race condition)
            $post = Post::with('user')
                ->where('id', $postId)
                ->lockForUpdate()
                ->firstOrFail();

            $existingLike = $post->likes()
                ->where('user_id', $user->id)
                ->first();

            if ($existingLike) {

                // UNLIKE
                $existingLike->delete();
                $post->decrement('total_like');

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Post unliked',
                    'liked' => false,
                    'liked_by_me' => false,
                    'total_like' => $post->total_like
                ]);
            }

            // LIKE
            PostLike::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
            ]);

            $post->increment('total_like');

            // ---------------------------------
            // 🔔 3. NOTIFICATION (NO SELF)
            // ---------------------------------

            if ($post->user_id !== $user->id) {
                $post->user->notify(new PostLikedNotification($user, $post));
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Post liked',
                'liked' => true,
                'liked_by_me' => true,
                'total_like' => $post->total_like
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

}
