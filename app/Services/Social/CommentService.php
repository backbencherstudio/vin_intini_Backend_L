<?php

namespace App\Services\Social;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Reply;
use App\Models\User;
use App\Notifications\CommentRepliedNotification;
use App\Notifications\PostCommentedNotification;
use App\Services\Media\OptimizedImageUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CommentService
{
    public function __construct(
        protected OptimizedImageUploadService $imageUploadService
    ) {}

    /**
     * Add a comment or reply to a post.
     *
     * @param  array<string, mixed>  $data
     * @return array{
     *     comment: Comment|Reply,
     *     total_comment: int,
     *     is_reply: bool
     * }
     */
    public function addComment(
        User $user,
        Post $post,
        array $data,
        ?UploadedFile $imageFile = null,
        ?Comment $parentComment = null,
        ?int $actingIndustryId = null
    ): array {
        $imagePath = null;
        if ($imageFile instanceof UploadedFile) {
            $imagePath = $this->imageUploadService->store($imageFile, 'comments');
        }

        try {
            if (! $parentComment instanceof Comment) {
                $comment = DB::transaction(function () use ($post, $user, $actingIndustryId, $data, $imagePath) {
                    $newComment = Comment::create([
                        'post_id' => $post->id,
                        'user_id' => $user->id,
                        'industry_id' => $actingIndustryId,
                        'comment' => $data['comment'] ?? null,
                        'image' => $imagePath,
                    ]);

                    $post->increment('total_comment');
                    $post->refresh();

                    return $newComment;
                });

                if ($post->user_id !== $user->id && $post->user && ! $post->user->trashed()) {
                    $post->user->notify(new PostCommentedNotification($user, $post, $comment));
                }

                return [
                    'comment' => $comment,
                    'total_comment' => (int) $post->total_comment,
                    'is_reply' => false,
                ];
            }

            $replyToReplyId = ! empty($data['reply_to_reply_id']) ? (int) $data['reply_to_reply_id'] : null;
            $replyToUserId = ! empty($data['reply_to_user_id']) ? (int) $data['reply_to_user_id'] : null;
            if (! $replyToUserId && $replyToReplyId) {
                $targetReply = Reply::find($replyToReplyId);
                if ($targetReply) {
                    $replyToUserId = (int) $targetReply->user_id;
                }
            }

            // Fallback for notification: If no explicit reply_to_user_id was passed, but the replier is the parent comment owner ($parentComment->user_id === $user->id)
            // notify the other person who participated in this reply thread
            if (! $replyToUserId && (int) $parentComment->user_id === (int) $user->id) {
                $lastOtherReply = Reply::where('comment_id', $parentComment->id)
                    ->where('user_id', '!=', $user->id)
                    ->latest()
                    ->first();
                if ($lastOtherReply) {
                    $replyToUserId = (int) $lastOtherReply->user_id;
                }
            }

            $reply = DB::transaction(function () use ($post, $parentComment, $user, $actingIndustryId, $data, $imagePath, $replyToUserId, $replyToReplyId) {
                $newReply = Reply::create([
                    'post_id' => $post->id,
                    'comment_id' => $parentComment->id,
                    'user_id' => $user->id,
                    'reply_to_user_id' => $replyToUserId,
                    'reply_to_reply_id' => $replyToReplyId,
                    'industry_id' => $actingIndustryId,
                    'reply' => $data['comment'] ?? null,
                    'image' => $imagePath,
                ]);

                $parentComment->increment('reply_count');
                $parentComment->refresh();

                return $newReply;
            });

            $notifiedUserIds = [];

            // 1. Specific reply author being replied to
            if ($replyToUserId && (int) $replyToUserId !== (int) $user->id) {
                $targetUser = User::find($replyToUserId);
                if ($targetUser && ! $targetUser->trashed()) {
                    $targetUser->notify(new CommentRepliedNotification($user, $post, $reply));
                    $notifiedUserIds[] = (int) $targetUser->id;
                }
            }

            // 2. Parent comment owner (if not already notified and not the replier)
            if ($parentComment->user_id && (int) $parentComment->user_id !== (int) $user->id && ! in_array((int) $parentComment->user_id, $notifiedUserIds, true)) {
                if ($parentComment->user && ! $parentComment->user->trashed()) {
                    $parentComment->user->notify(new CommentRepliedNotification($user, $post, $reply));
                    $notifiedUserIds[] = (int) $parentComment->user_id;
                }
            }

            // 3. Post owner (if post owner is different from replier, and not yet notified)
            if ($post->user_id && (int) $post->user_id !== (int) $user->id && ! in_array((int) $post->user_id, $notifiedUserIds, true)) {
                if ($post->user && ! $post->user->trashed()) {
                    $post->user->notify(new CommentRepliedNotification($user, $post, $reply));
                    $notifiedUserIds[] = (int) $post->user_id;
                }
            }

            return [
                'comment' => $reply,
                'total_comment' => (int) $post->total_comment,
                'total_reply' => (int) $parentComment->reply_count,
                'is_reply' => true,
            ];
        } catch (\Throwable $e) {
            if ($imagePath && Storage::disk('public')->exists($imagePath)) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $e;
        }
    }
}
