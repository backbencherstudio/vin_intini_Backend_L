<?php

namespace App\Services\Social;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Reply;
use App\Models\User;
use App\Notifications\CommentRepliedNotification;
use App\Notifications\PostCommentedNotification;
use App\Services\OptimizedImageUploadService;
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

            $reply = DB::transaction(function () use ($post, $parentComment, $user, $actingIndustryId, $data, $imagePath) {
                $newReply = Reply::create([
                    'post_id' => $post->id,
                    'comment_id' => $parentComment->id,
                    'user_id' => $user->id,
                    'industry_id' => $actingIndustryId,
                    'reply' => $data['comment'] ?? null,
                    'image' => $imagePath,
                ]);

                $parentComment->increment('reply_count');
                $parentComment->refresh();

                return $newReply;
            });

            if ($parentComment->user_id !== $user->id && $parentComment->user && ! $parentComment->user->trashed()) {
                $parentComment->user->notify(new CommentRepliedNotification($user, $post, $reply));
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
