<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use App\Models\Connection;
use App\Models\Comment;
use App\Notifications\CommentRepliedNotification;
use App\Notifications\PostCommentedNotification;

class CommentController extends Controller
{
    public function comment(Request $request, $postId)
    {
        $request->validate([
            'comment' => 'required|string|max:1000',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $user = auth('api')->user();

        $post = Post::with('user')->findOrFail($postId);

        if ($post->who_can_comment === 'no_one') {
            return response()->json([
                'success' => false,
                'message' => 'Comments are disabled for this post'
            ], 403);
        }

        if ($post->who_can_comment === 'connections') {

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

            if (!$isConnected && $user->id !== $post->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only connections can comment on this post'
                ], 403);
            }
        }

        $parentComment = null;

        if ($request->parent_id) {

            $parentComment = Comment::where('id', $request->parent_id)
                ->where('post_id', $post->id)
                ->firstOrFail();

            if ($parentComment->parent_id !== null) {
                return response()->json([
                    'success' => false,
                    'message' => 'Replies are only allowed one level deep'
                ], 400);
            }
        }

        DB::beginTransaction();

        try {

            $comment = Comment::create([
                'post_id'   => $post->id,
                'user_id'   => $user->id,
                'comment'   => $request->comment,
                'parent_id' => $request->parent_id ?? null,
            ]);

            $post->increment('total_comment');

            if (!$request->parent_id) {

                if ($post->user_id !== $user->id) {
                    $post->user->notify(new PostCommentedNotification($user, $post, $comment));
                }
            }

            if ($request->parent_id && $parentComment) {

                if ($parentComment->user_id !== $user->id) {
                    $parentComment->user->notify(
                        new CommentRepliedNotification($user, $post, $comment)
                    );
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $request->parent_id ? 'Reply added' : 'Comment added',
                'data' => $comment->load('user')
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
