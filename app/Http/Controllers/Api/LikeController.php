<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentLike;
use Illuminate\Http\Request;
use App\Models\PostLike;
use App\Models\Post;
use Illuminate\Support\Facades\DB;
use App\Models\Connection;
use App\Models\GroupUser;
use App\Models\Reply;
use App\Models\ReplyLike;
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

        DB::beginTransaction();

        try {

            $post = Post::with('user')
                ->where('id', $postId)
                ->lockForUpdate()
                ->firstOrFail();

            $existingLike = $post->likes()
                ->where('user_id', $user->id)
                ->first();

            if ($existingLike) {

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

            PostLike::create([
                'post_id' => $post->id,
                'user_id' => $user->id,
            ]);

            $post->increment('total_like');

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

    public function likedList(Request $request, $postId)
    {
        $perPage = $request->get('per_page', 10);

        $likes = PostLike::with(['user:id,first_name,last_name,profile_image'])
            ->where('post_id', $postId)
            ->latest()
            ->paginate($perPage);

        $users = collect($likes->items())->map(function ($like) {
            return [
                'id' => $like->user->id,
                'name' => $like->user->first_name . ' ' . $like->user->last_name,
                'profile_image' => $like->user->profile_image_url,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Liked users list',
            'data' => $users,
            'pagination' => [
                'current_page' => $likes->currentPage(),
                'per_page'     => $likes->perPage(),
                'total'        => $likes->total(),
                'last_page'    => $likes->lastPage(),
            ]
        ]);
    }
    
    public function likeComment($commentId)
    {
        $userId = auth('api')->id();

        $comment = Comment::select('id', 'like_count')->findOrFail($commentId);

        try {

            $created = CommentLike::firstOrCreate([
                'comment_id' => $comment->id,
                'user_id'    => $userId,
            ]);

            if ($created->wasRecentlyCreated) {

                Comment::where('id', $comment->id)
                    ->update([
                        'like_count' => DB::raw('like_count + 1')
                    ]);

                return response()->json([
                    'success'    => true,
                    'message'    => 'Comment liked',
                    'liked'      => true,
                    'like_count' => $comment->like_count + 1,
                ]);
            }

            CommentLike::where([
                'comment_id' => $comment->id,
                'user_id'    => $userId,
            ])->delete();

            Comment::where('id', $comment->id)
                ->where('like_count', '>', 0)
                ->update([
                    'like_count' => DB::raw('like_count - 1')
                ]);

            return response()->json([
                'success'    => true,
                'message'    => 'Comment unliked',
                'liked'      => false,
                'like_count' => max(0, $comment->like_count - 1),
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function likeReply($replyId)
    {
        $userId = auth('api')->id();

        $reply = Reply::select('id', 'like_count')->findOrFail($replyId);

        try {

            $created = ReplyLike::firstOrCreate([
                'reply_id' => $reply->id,
                'user_id'  => $userId,
            ]);

            if ($created->wasRecentlyCreated) {

                Reply::where('id', $reply->id)
                    ->update([
                        'like_count' => DB::raw('like_count + 1')
                    ]);

                return response()->json([
                    'success'    => true,
                    'message'    => 'Reply liked',
                    'liked'      => true,
                    'like_count' => $reply->like_count + 1,
                ]);
            }

            ReplyLike::where([
                'reply_id' => $reply->id,
                'user_id'  => $userId,
            ])->delete();

            Reply::where('id', $reply->id)
                ->where('like_count', '>', 0)
                ->update([
                    'like_count' => DB::raw('like_count - 1')
                ]);

            return response()->json([
                'success'    => true,
                'message'    => 'Reply unliked',
                'liked'      => false,
                'like_count' => max(0, $reply->like_count - 1),
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Something went wrong',
                'error'   => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function commentLikedList(Request $request, $commentId)
    {
        $perPage = $request->get('per_page', 10);

        $likes = CommentLike::with([
                'user:id,first_name,last_name,profile_image'
            ])
            ->where('comment_id', $commentId)
            ->latest()
            ->paginate($perPage);

        $users = collect($likes->items())->map(function ($like) {
            return [
                'id' => $like->user->id,
                'name' => $like->user->first_name . ' ' . $like->user->last_name,
                'profile_image' => $like->user->profile_image_url,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Comment liked users list',
            'data' => $users,
            'pagination' => [
                'current_page' => $likes->currentPage(),
                'per_page'     => $likes->perPage(),
                'total'        => $likes->total(),
                'last_page'    => $likes->lastPage(),
            ]
        ]);
    }

    public function replyLikedList(Request $request, $replyId)
    {
        $perPage = $request->get('per_page', 10);

        $likes = ReplyLike::with([
                'user:id,first_name,last_name,profile_image'
            ])
            ->where('reply_id', $replyId)
            ->latest()
            ->paginate($perPage);

        $users = $likes->map(function ($like) {
            return [
                'id' => $like->user->id,
                'name' => $like->user->first_name . ' ' . $like->user->last_name,
                'profile_image' => $like->user->profile_image_url,
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Reply liked users list',
            'data' => $users,
            'pagination' => [
                'current_page' => $likes->currentPage(),
                'per_page'     => $likes->perPage(),
                'total'        => $likes->total(),
                'last_page'    => $likes->lastPage(),
            ]
        ]);
    }

}
