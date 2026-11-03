<?php

namespace App\Http\Controllers\Api;

use App\Events\IndustryConversationUpdated;
use App\Events\IndustryMessageDeleted;
use App\Events\IndustryMessageReactionChanged;
use App\Events\IndustryMessageSent;
use App\Http\Controllers\Controller;
use App\Models\IndustryConversation;
use App\Models\IndustryMessage;
use App\Models\IndustryMessageReaction;
use App\Models\Subscription;
use App\Services\OptimizedImageUploadService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class IndustryMessageController extends Controller
{
    /**
     * List paginated messages of an industry conversation.
     */
    public function index(Request $request, IndustryConversation $conversation): JsonResponse
    {
        $currentUser = $request->user();

        if ($error = $this->authorizeConversation($conversation, $currentUser->id)) {
            return $error;
        }

        $validated = $request->validate([
            'after_id' => 'sometimes|integer|min:0',
        ]);
        $afterId = $validated['after_id'] ?? null;

        $isIndustryOwner = $this->isIndustryOwner($currentUser, $conversation);

        if ($isIndustryOwner) {
            $conversation->markAsReadForIndustry();
            $messagesQuery = $conversation->visibleMessagesForIndustry();
        } else {
            $conversation->markAsReadForUser();
            $messagesQuery = $conversation->visibleMessagesForUser();
        }

        $messages = $messagesQuery
            ->with([
                'sender' => fn ($q) => $q->select(['id', 'first_name', 'last_name', 'title', 'profile_image'])->withTrashed(),
                'replyTo.sender' => fn ($q) => $q->select(['id', 'first_name', 'last_name'])->withTrashed(),
                'reactions.user' => fn ($q) => $q->select(['id', 'first_name', 'last_name'])->withTrashed(),
            ])
            ->when(
                $afterId !== null,
                fn ($query) => $query->where('industry_messages.id', '>', $afterId)->orderBy('industry_messages.id'),
                fn ($query) => $query->orderByDesc('industry_messages.id')
            )
            ->cursorPaginate(50);

        $conversation->load(['industry', 'user' => fn ($q) => $q->withTrashed()]);
        $otherUser = $conversation->user;
        $industry = $conversation->industry;

        $messageItems = collect($messages->items());

        if ($afterId === null) {
            $messageItems = $messageItems->reverse();
        }

        $data = $messageItems->values()->map(function (IndustryMessage $message) use ($currentUser, $isIndustryOwner) {
            $isMine = $isIndustryOwner
                ? ($message->sender_type === 'industry')
                : ($message->sender_type === 'user');

            return [
                'id' => $message->id,
                'conversation_id' => $message->industry_conversation_id,
                'sender_type' => $message->sender_type,
                'sender_id' => $message->sender_id,
                'is_mine' => $isMine,
                'type' => $message->type,
                'message' => $message->message,
                'file_url' => $message->file_url,
                'file_name' => $message->file_name,
                'file_size' => $message->file_size,
                'file_extension' => $message->file_extension,
                'file_category' => $message->file_category,
                'duration' => $message->duration,
                'reply_to' => $this->replyPreview($message->replyTo),
                'reactions' => $this->reactionSummary($message),
                'my_reaction' => $message->reactions->firstWhere('user_id', $currentUser->id)?->reaction,
                'created_at' => $message->created_at->toISOString(),
            ];
        })->values();

        $participantInfo = $isIndustryOwner ? [
            'user' => [
                'id' => $otherUser->id,
                'name' => trim(($otherUser->first_name ?? '').' '.($otherUser->last_name ?? '')),
                'first_name' => $otherUser->first_name,
                'last_name' => $otherUser->last_name,
                'title' => $otherUser->title,
                'profile_image_url' => $otherUser->profile_image_url,
                'cover_image_url' => $otherUser->cover_image_url,
                'has_premium' => $this->userHasActiveSubscription($otherUser->id),
            ],
            'mode' => 'industry',
        ] : [
            'industry' => [
                'id' => $industry->id,
                'name' => $industry->name,
                'tagline' => $industry->tagline,
                'industry' => $industry->industry,
                'address' => $industry->address,
                'logo' => $industry->logo,
                'cover_image' => $industry->cover_image,
            ],
            'mode' => 'user',
        ];

        return response()->json([
            'success' => true,
            ...$participantInfo,
            'data' => $data,
            'next_cursor' => $messages->nextCursor()?->encode(),
            'has_more' => $messages->hasMorePages(),
        ]);
    }

    /**
     * Send a new message in an industry conversation.
     */
    public function store(Request $request, IndustryConversation $conversation, OptimizedImageUploadService $imageUploadService): JsonResponse
    {
        $currentUser = $request->user();

        if ($error = $this->authorizeConversation($conversation, $currentUser->id)) {
            return $error;
        }

        $isIndustryOwner = $this->isIndustryOwner($currentUser, $conversation);
        $senderType = $isIndustryOwner ? 'industry' : 'user';

        $validated = $request->validate([
            'type' => 'required|in:text,voice,file,image,video',
            'message' => 'required_if:type,text|nullable|string',
            'file' => 'required_if:type,file|required_if:type,voice|required_if:type,image|required_if:type,video|nullable|file|max:102400',
            'duration' => 'nullable|integer|min:0',
            'reply_to_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail) use ($conversation, $isIndustryOwner): void {
                    $query = $isIndustryOwner
                        ? $conversation->visibleMessagesForIndustry()
                        : $conversation->visibleMessagesForUser();
                    if ($value && ! $query->where('id', $value)->exists()) {
                        $fail('The replied message does not exist in this conversation.');
                    }
                },
            ],
        ]);

        $data = [
            'industry_conversation_id' => $conversation->id,
            'sender_type' => $senderType,
            'sender_id' => $currentUser->id,
            'reply_to_id' => $validated['reply_to_id'] ?? null,
            'type' => $validated['type'],
            'message' => $validated['message'] ?? null,
            'duration' => $validated['duration'] ?? null,
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $imageUploadService->store($file, 'industry-conversations/'.$conversation->id);
            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();

            if (($data['duration'] ?? null) === null && $this->isMediaFile($file)) {
                $data['duration'] = $this->extractMediaDuration($file);
            }
        }

        $message = IndustryMessage::create($data)->load('replyTo.sender:id,first_name,last_name');

        $conversation->restoreForIndustry();
        $conversation->restoreForUser();
        $conversation->update(['last_message_id' => $message->id]);

        event(new IndustryMessageSent($message));

        // Notify the recipient
        $conversation->load(['industry', 'user']);
        $receiverId = $isIndustryOwner ? $conversation->user_id : (int) $conversation->industry->created_by;
        $unreadCount = $isIndustryOwner ? $conversation->unreadCountForUser() : $conversation->unreadCountForIndustry();

        $unreadSummary = $isIndustryOwner
            ? ['unread_conversation_count' => 0, 'total_unread_messages' => 0]
            : IndustryConversation::unreadSummaryForIndustry($conversation->industry_id);

        event(new IndustryConversationUpdated(
            $conversation,
            $receiverId,
            $unreadCount,
            $message,
            $unreadSummary['unread_conversation_count'],
            $unreadSummary['total_unread_messages'],
        ));

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => [
                'id' => $message->id,
                'conversation_id' => $message->industry_conversation_id,
                'sender_type' => $message->sender_type,
                'sender_id' => $message->sender_id,
                'is_mine' => true,
                'type' => $message->type,
                'message' => $message->message,
                'file_url' => $message->file_url,
                'file_name' => $message->file_name,
                'file_size' => $message->file_size,
                'file_extension' => $message->file_extension,
                'file_category' => $message->file_category,
                'duration' => $message->duration,
                'reply_to' => $this->replyPreview($message->replyTo),
                'created_at' => $message->created_at->toISOString(),
            ],
        ], 201);
    }

    /**
     * Delete an industry message.
     */
    public function destroy(Request $request, IndustryMessage $message): JsonResponse
    {
        $currentUser = $request->user();
        $conversation = $message->conversation;

        if ($error = $this->authorizeConversation($conversation, $currentUser->id)) {
            return $error;
        }

        $isIndustryOwner = $this->isIndustryOwner($currentUser, $conversation);

        // Can delete if sent by this user or if industry owner deleting an industry message
        $canDelete = ($message->sender_id === $currentUser->id)
            || ($isIndustryOwner && $message->sender_type === 'industry');

        if (! $canDelete) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own messages.',
            ], 403);
        }

        $message->delete();

        event(new IndustryMessageDeleted($message->industry_conversation_id, $message->id));

        if ($conversation->last_message_id === $message->id) {
            $conversation->update(['last_message_id' => $conversation->messages()->max('id')]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully.',
        ]);
    }

    /**
     * React to an industry message.
     */
    public function react(Request $request, IndustryMessage $message): JsonResponse
    {
        $currentUser = $request->user();
        $conversation = $message->conversation;

        if ($error = $this->authorizeConversation($conversation, $currentUser->id)) {
            return $error;
        }

        $validated = $request->validate([
            'reaction' => 'required|string',
        ]);

        $reaction = IndustryMessageReaction::where('industry_message_id', $message->id)
            ->where('user_id', $currentUser->id)
            ->first();

        if ($reaction && $reaction->reaction === $validated['reaction']) {
            $reaction->delete();
        } else {
            IndustryMessageReaction::updateOrCreate(
                ['industry_message_id' => $message->id, 'user_id' => $currentUser->id],
                ['reaction' => $validated['reaction']]
            );
        }

        $message->load('reactions.user');
        $reactions = $this->reactionSummary($message);

        event(new IndustryMessageReactionChanged($message->industry_conversation_id, $message->id, $reactions));

        return response()->json([
            'success' => true,
            'message' => 'Reaction updated.',
            'reactions' => $reactions,
        ]);
    }

    /**
     * Unreact to an industry message.
     */
    public function unreact(Request $request, IndustryMessage $message): JsonResponse
    {
        $currentUser = $request->user();
        $conversation = $message->conversation;

        if ($error = $this->authorizeConversation($conversation, $currentUser->id)) {
            return $error;
        }

        IndustryMessageReaction::where('industry_message_id', $message->id)
            ->where('user_id', $currentUser->id)
            ->delete();

        $message->load('reactions.user');
        $reactions = $this->reactionSummary($message);

        event(new IndustryMessageReactionChanged($message->industry_conversation_id, $message->id, $reactions));

        return response()->json([
            'success' => true,
            'message' => 'Reaction removed.',
            'reactions' => $reactions,
        ]);
    }

    private function authorizeConversation(IndustryConversation $conversation, int $userId): ?JsonResponse
    {
        $conversation->loadMissing('industry');
        $isIndustryOwner = (int) $conversation->industry?->created_by === $userId;
        $isUser = (int) $conversation->user_id === $userId;

        if (! $isIndustryOwner && ! $isUser) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($isIndustryOwner && $conversation->isDeletedForIndustry()) {
            return response()->json(['success' => false, 'message' => 'Conversation not found.'], 404);
        }

        if ($isUser && $conversation->isDeletedForUser()) {
            return response()->json(['success' => false, 'message' => 'Conversation not found.'], 404);
        }

        return null;
    }

    private function isIndustryOwner($user, IndustryConversation $conversation): bool
    {
        return (int) $conversation->industry?->created_by === (int) $user->id;
    }

    private function replyPreview(?IndustryMessage $replyTo): ?array
    {
        if (! $replyTo) {
            return null;
        }

        return [
            'id' => $replyTo->id,
            'sender_type' => $replyTo->sender_type,
            'sender_id' => $replyTo->sender_id,
            'type' => $replyTo->type,
            'message' => $replyTo->message,
            'file_url' => $replyTo->file_url,
            'file_name' => $replyTo->file_name,
            'file_category' => $replyTo->file_category,
        ];
    }

    private function reactionSummary(IndustryMessage $message): array
    {
        return $message->reactions
            ->groupBy('reaction')
            ->map(fn ($reactions, string $reaction) => [
                'reaction' => $reaction,
                'count' => $reactions->count(),
                'users' => $reactions
                    ->map(fn ($item) => [
                        'id' => $item->user_id,
                        'name' => trim(($item->user?->first_name ?? '').' '.($item->user?->last_name ?? '')),
                    ])
                    ->values(),
            ])
            ->values()
            ->all();
    }

    private function userHasActiveSubscription(int $userId): bool
    {
        return Subscription::where('user_id', $userId)
            ->whereIn('status', ['active', 'trialing'])
            ->exists();
    }

    private function isMediaFile(UploadedFile $file): bool
    {
        $mime = $file->getMimeType() ?? '';

        return str_starts_with($mime, 'audio/') || str_starts_with($mime, 'video/');
    }

    private function extractMediaDuration(UploadedFile $file): ?int
    {
        $command = sprintf(
            'ffprobe -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 %s 2>&1',
            escapeshellarg($file->getRealPath())
        );

        exec($command, $output, $status);

        if ($status !== 0 || ! isset($output[0]) || ! is_numeric(trim($output[0]))) {
            return null;
        }

        return (int) round((float) trim($output[0]));
    }
}

