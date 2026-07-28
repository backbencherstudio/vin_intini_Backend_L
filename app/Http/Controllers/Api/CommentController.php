<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Connection;
use App\Models\Post;
use App\Models\Reply;
use App\Notifications\CommentRepliedNotification;
use App\Notifications\PostCommentedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommentController extends Controller
{
    public function comment(Request $request, $postId)
    {
        $request->validate([
            'comment' => 'nullable|string|max:1000|required_without:image',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120|required_without:comment',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $user = auth('api')->user();
        $post = Post::with('user')->findOrFail($postId);

        if ($post->who_can_comment === 'no_one') {
            return response()->json([
                'success' => false,
                'message' => 'Comments are disabled for this post',
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

            if (! $isConnected && $user->id !== $post->user_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Only connections can comment on this post',
                ], 403);
            }
        }

        DB::beginTransaction();

        try {

            $imagePath = null;

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('comments', 'public');
            }

            if (! $request->parent_id) {

                $comment = Comment::create([
                    'post_id' => $post->id,
                    'user_id' => $user->id,
                    'comment' => $request->comment ?? null,
                    'image' => $imagePath,
                ]);

                $post->increment('total_comment');
                $post->refresh();

                // notification
                if ($post->user_id !== $user->id) {
                    $post->user->notify(
                        new PostCommentedNotification($user, $post, $comment)
                    );
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Comment added',
                    'data' => $comment,
                    'total_comment' => $post->total_comment,
                ]);
            }

            $parentComment = Comment::where('id', $request->parent_id)
                ->where('post_id', $post->id)
                ->firstOrFail();

            $reply = Reply::create([
                'post_id' => $post->id,
                'comment_id' => $parentComment->id,
                'user_id' => $user->id,
                'reply' => $request->comment ?? null,
                'image' => $imagePath,
            ]);

            $parentComment->increment('reply_count');
            $parentComment->refresh();

            if ($parentComment->user_id !== $user->id) {
                $parentComment->user->notify(
                    new CommentRepliedNotification($user, $post, $reply)
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reply added',
                'data' => $reply,
                'total_reply' => $parentComment->reply_count,
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function commentList(Request $request, $postId)
    {
        $perPage = $request->get('per_page', 10);

        $user = auth('api')->user();

        $comments = Comment::with('user:id,title,first_name,last_name,profile_image')
            ->withExists([
                'likes as liked_by_me' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
            ])
            ->where('post_id', $postId)
            ->latest()
            ->paginate($perPage);

        $data = collect($comments->items())->map(function ($comment) use ($user) {

            $canDelete = (
                $comment->user_id === $user->id ||
                $comment->post->user_id === $user->id ||
                ($comment->parent_id && $comment->parent->user_id === $user->id)
            );

            return [
                'id' => $comment->id,
                'comment' => $comment->comment ?? null,
                'image_url' => $comment->image_url ?? null,

                'user' => [
                    'id' => $comment->user->id,
                    'name' => $comment->user->first_name.' '.$comment->user->last_name,
                    'title' => $comment->user->title,
                    'profile_image' => $comment->user->profile_image_url,
                ],

                'like_count' => $comment->like_count,
                'liked_by_me' => (bool) $comment->liked_by_me,

                'replies_count' => $comment->reply_count,
                'comment_time' => $comment->created_at,
                'can_delete' => $canDelete,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Comment list',
            'data' => $data,
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
                'last_page' => $comments->lastPage(),
            ],
        ]);
    }

    public function replyList(Request $request, $commentId)
    {
        $perPage = $request->get('per_page', 10);
        $user = auth('api')->user();

        $replies = Reply::with('user:id,title,first_name,last_name,profile_image')
            ->withExists([
                'likes as liked_by_me' => function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                },
            ])
            ->where('comment_id', $commentId)
            ->latest()
            ->paginate($perPage);

        $data = collect($replies->items())->map(function ($reply) use ($user) {

            $canDelete = (
                $reply->user_id === $user->id ||
                $reply->comment->user_id === $user->id ||
                $reply->comment->post->user_id === $user->id
            );

            return [
                'id' => $reply->id,
                'reply' => $reply->reply ?? null,
                'image_url' => $reply->image_url ?? null,
                'user' => [
                    'id' => $reply->user->id,
                    'name' => $reply->user->first_name.' '.$reply->user->last_name,
                    'title' => $reply->user->title,
                    'profile_image' => $reply->user->profile_image_url,
                ],

                'like_count' => $reply->like_count,
                'liked_by_me' => (bool) $reply->liked_by_me,

                'reply_time' => $reply->created_at,
                'can_delete' => $canDelete,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Reply list',
            'data' => $data,
            'pagination' => [
                'current_page' => $replies->currentPage(),
                'per_page' => $replies->perPage(),
                'total' => $replies->total(),
                'last_page' => $replies->lastPage(),
            ],
        ]);
    }

    public function deleteComment($commentId)
    {
        $user = auth('api')->user();

        $comment = Comment::with(['post', 'replies'])->findOrFail($commentId);

        if (
            $comment->user_id !== $user->id &&
            $comment->post->user_id !== $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this comment',
            ], 403);
        }

        DB::beginTransaction();

        try {

            foreach ($comment->replies as $reply) {

                if ($reply->image) {
                    Storage::disk('public')->delete($reply->image);
                }

                $reply->delete();
            }

            if ($comment->image) {
                Storage::disk('public')->delete($comment->image);
            }

            $totalDecrease = 1 + $comment->replies()->count();

            $comment->post->decrement('total_comment', $totalDecrease);

            $comment->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comment deleted successfully',
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function deleteReply($replyId)
    {
        $user = auth('api')->user();

        $reply = Reply::with(['comment.post'])->findOrFail($replyId);

        $comment = $reply->comment;
        $post = $comment->post;

        if (
            $reply->user_id !== $user->id &&
            $comment->user_id !== $user->id &&
            $post->user_id !== $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You are not authorized to delete this reply',
            ], 403);
        }

        DB::beginTransaction();

        try {

            if ($reply->image) {
                Storage::disk('public')->delete($reply->image);
            }

            $reply->delete();

            if ($comment->reply_count > 0) {
                $comment->decrement('reply_count');
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reply deleted successfully',
                'total_reply' => $comment->reply_count,
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error' => app()->environment('local') ? $e->getMessage() : null,
            ], 500);
        }
    }

    public function myComments(Request $request)
    {
        $user = auth('api')->user();

        $comments = Comment::with([
            'post:id,user_id,description',
            'post.user:id,first_name,last_name',
        ])
            ->where('user_id', $user->id)
            ->latest()
            ->paginate($request->get('per_page', 10));

        $data = collect($comments->items())->map(function ($comment) {
            return [
                'id' => $comment->id,
                'comment' => $comment->comment,

                'post' => [
                    'id' => $comment->post->id,
                    'description' => $comment->post->description,

                    'posted_by' => [
                        'id' => $comment->post->user->id ?? null,
                        'name' => $comment->post->user->first_name.' '.$comment->post->user->last_name ?? null,
                    ],
                ],

                'comment_time' => $comment->created_at,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'My comment list retrieved successfully',
            'data' => $data,
            'pagination' => [
                'current_page' => $comments->currentPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
                'last_page' => $comments->lastPage(),
            ],
        ]);
    }
}
