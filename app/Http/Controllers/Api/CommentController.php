<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Connection;
use App\Models\Post;
use App\Models\Reply;
use App\Models\User;
use App\Services\Social\CommentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommentController extends Controller
{
    public function comment(Request $request, $postId, CommentService $commentService)
    {
        $request->validate([
            'comment' => 'nullable|string|max:1000|required_without:image',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120|required_without:comment',
            'parent_id' => 'nullable|exists:comments,id',
        ]);

        $user = auth('api')->user();

        $post = Post::with(['user' => fn ($q) => $q->withTrashed(), 'industryLink'])->findOrFail($postId);

        if (! $post->user || $post->user->trashed()) {
            return response()->json([
                'success' => false,
                'message' => 'This post is no longer available.',
            ], 404);
        }

        if ($post->isCompanyPost() && ! $post->isVisibleTo($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to comment on this post',
            ], 403);
        }

        if ($post->who_can_comment === 'no_one') {
            return response()->json(['success' => false, 'message' => 'Comments are disabled for this post'], 403);
        }

        if ($post->who_can_comment === 'connections') {
            $isConnected = Connection::where('status', Connection::STATUS_ACCEPTED)
                ->where(function ($q) use ($user, $post): void {
                    $q->where(function ($q1) use ($user, $post): void {
                        $q1->where('sender_id', $user->id)
                            ->where('receiver_id', $post->user_id);
                    })->orWhere(function ($q2) use ($user, $post): void {
                        $q2->where('sender_id', $post->user_id)
                            ->where('receiver_id', $user->id);
                    });
                })->exists();

            if (! $isConnected && $user->id !== $post->user_id) {
                return response()->json(['success' => false, 'message' => 'Only connections can comment on this post'], 403);
            }
        }

        $post->loadMissing('industryLink.industry');
        $company = $post->industryLink?->industry;
        $isPostCompanyOwner = $company && (int) $company->created_by === $user->id;

        $actingIndustryId = null;
        if ($request->boolean('as_company')) {
            if (! $isPostCompanyOwner) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to comment on behalf of this company.',
                ], 403);
            }

            $actingIndustryId = $company->id;
        }

        $parentComment = null;
        if ($request->parent_id) {
            $parentComment = Comment::with(['user' => fn ($q) => $q->withTrashed()])
                ->where('id', $request->parent_id)
                ->where('post_id', $post->id)
                ->first();

            if (! $parentComment) {
                return response()->json(['success' => false, 'message' => 'Parent comment not found.'], 404);
            }

            if (! $parentComment->user || $parentComment->user->trashed()) {
                return response()->json(['success' => false, 'message' => 'Cannot reply to a deleted user\'s comment.'], 404);
            }
        }

        try {
            $result = $commentService->addComment(
                $user,
                $post,
                ['comment' => $request->comment ?? null],
                $request->file('image'),
                $parentComment,
                $actingIndustryId
            );

            if ($result['is_reply']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Reply added',
                    'data' => $result['comment'],
                    'total_reply' => $result['total_reply'],
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Comment added',
                'data' => $result['comment'],
                'total_comment' => $result['total_comment'],
            ]);
        } catch (\Throwable) {
            return response()->json(['success' => false, 'message' => 'Something went wrong'], 500);
        }
    }

    public function commentList(Request $request, $postId)
    {
        $perPage = $request->get('per_page', 10);
        $user = auth('api')->user();

        $post = Post::with(['user' => fn ($q) => $q->withTrashed(), 'industryLink'])->findOrFail($postId);

        if ($post->isCompanyPost() && ! $post->isVisibleTo($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to view comments on this post',
            ], 403);
        }

        $comments = Comment::with([
            'user:id,username,title,first_name,last_name,profile_image',
            'industry:id,name,slug,tagline,logo',
            'post',
        ])
            ->whereHas('user', fn ($q) => $q->whereNull('deleted_at'))
            ->withExists(['likes as liked_by_me' => fn ($q) => $q->where('user_id', $user->id)])
            ->where('post_id', $postId)
            ->latest()
            ->paginate($perPage);

        $data = collect($comments->items())->map(function ($comment) use ($user): ?array {
            if (! $comment->user) {
                return null;
            }

            $canDelete = ($comment->user_id === $user->id || $comment->post->user_id === $user->id);
            $isCompany = (bool) $comment->industry_id;
            $industry = $isCompany ? $comment->industry : null;

            return [
                'id' => $comment->id,
                'comment' => $comment->comment ?? null,
                'image_url' => $comment->image_url ?? null,
                'industry_id' => $comment->industry_id,
                'is_company_comment' => $isCompany,
                'user' => $isCompany && $industry ? [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'title' => $industry->tagline,
                    'profile_image' => $industry->logo,
                    'is_company' => true,
                ] : [
                    'id' => $comment->user->id,
                    'name' => trim($comment->user->first_name.' '.$comment->user->last_name),
                    'title' => $comment->user->title,
                    'profile_image' => $comment->user->profile_image_url,
                    'is_company' => false,
                ],
                'industry' => $industry ? [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'slug' => $industry->slug,
                    'tagline' => $industry->tagline,
                    'logo' => $industry->logo,
                ] : null,
                'like_count' => (int) $comment->like_count,
                'liked_by_me' => (bool) $comment->liked_by_me,
                'replies_count' => (int) $comment->reply_count,
                'comment_time' => $comment->created_at,
                'can_delete' => $canDelete,
            ];
        })->filter()->values();

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

        if (($error = $this->denyWhenParentPostHidden($user, Comment::findOrFail($commentId)->post_id)) instanceof JsonResponse) {
            return $error;
        }

        $replies = Reply::with([
            'user:id,username,title,first_name,last_name,profile_image',
            'industry:id,name,slug,tagline,logo',
            'comment.post',
        ])
            ->whereHas('user', fn ($q) => $q->whereNull('deleted_at'))
            ->withExists(['likes as liked_by_me' => fn ($q) => $q->where('user_id', $user->id)])
            ->where('comment_id', $commentId)
            ->latest()
            ->paginate($perPage);

        $data = collect($replies->items())->map(function ($reply) use ($user): ?array {
            if (! $reply->user) {
                return null;
            }

            $canDelete = ($reply->user_id === $user->id || $reply->comment->user_id === $user->id || $reply->comment->post->user_id === $user->id);
            $isCompany = (bool) $reply->industry_id;
            $industry = $isCompany ? $reply->industry : null;

            return [
                'id' => $reply->id,
                'reply' => $reply->reply ?? null,
                'image_url' => $reply->image_url ?? null,
                'industry_id' => $reply->industry_id,
                'is_company_reply' => $isCompany,
                'user' => $isCompany && $industry ? [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'title' => $industry->tagline,
                    'profile_image' => $industry->logo,
                    'is_company' => true,
                ] : [
                    'id' => $reply->user->id,
                    'name' => trim($reply->user->first_name.' '.$reply->user->last_name),
                    'title' => $reply->user->title,
                    'profile_image' => $reply->user->profile_image_url,
                    'is_company' => false,
                ],
                'industry' => $industry ? [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'slug' => $industry->slug,
                    'tagline' => $industry->tagline,
                    'logo' => $industry->logo,
                ] : null,
                'like_count' => (int) $reply->like_count,
                'liked_by_me' => (bool) $reply->liked_by_me,
                'reply_time' => $reply->created_at,
                'can_delete' => $canDelete,
            ];
        })->filter()->values();

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

        if ($comment->user_id !== $user->id && $comment->post->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to delete this comment'], 403);
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

            return response()->json(['success' => true, 'message' => 'Comment deleted successfully']);
        } catch (\Throwable) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Something went wrong'], 500);
        }
    }

    public function deleteReply($replyId)
    {
        $user = auth('api')->user();
        $reply = Reply::with(['comment.post'])->findOrFail($replyId);
        $comment = $reply->comment;

        if ($reply->user_id !== $user->id && $comment->user_id !== $user->id && $reply->comment->post->user_id !== $user->id) {
            return response()->json(['success' => false, 'message' => 'You are not authorized to delete this reply'], 403);
        }

        DB::beginTransaction();
        try {
            if ($reply->image) {
                Storage::disk('public')->delete($reply->image);
            }
            $reply->delete();

            if ($comment->reply_count > 0) {
                $comment->decrement('reply_count');
                $comment->refresh();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reply deleted successfully',
                'total_reply' => (int) $comment->reply_count,
            ]);
        } catch (\Throwable) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Something went wrong'], 500);
        }
    }

    public function myComments(Request $request)
    {
        $user = auth('api')->user();

        $comments = Comment::with([
            'post:id,user_id,description',
            'post.user' => fn ($q) => $q->withTrashed()->select('id', 'first_name', 'last_name'),
        ])
            ->where('user_id', $user->id)
            ->whereHas('post.user', fn ($q) => $q->whereNull('deleted_at'))
            ->latest()
            ->paginate($request->get('per_page', 10));

        $data = collect($comments->items())->map(function ($comment): ?array {
            if (! $comment->post || ! $comment->post->user) {
                return null;
            }

            return [
                'id' => $comment->id,
                'comment' => $comment->comment,
                'post' => [
                    'id' => $comment->post->id,
                    'description' => $comment->post->description,
                    'posted_by' => [
                        'id' => $comment->post->user->id,
                        'name' => trim($comment->post->user->first_name.' '.$comment->post->user->last_name),
                    ],
                ],
                'comment_time' => $comment->created_at,
            ];
        })->filter()->values();

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

    /**
     * Company comments and replies live in the shared tables, so the generic
     * comment and reply endpoints must honour company post visibility too.
     */
    private function denyWhenParentPostHidden(?User $user, ?int $postId): ?JsonResponse
    {
        if (! $postId) {
            return null;
        }

        $post = Post::with('industryLink')->find($postId);

        if ($post && $post->isCompanyPost() && ! $post->isVisibleTo($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to interact with this post',
            ], 403);
        }

        return null;
    }
}
