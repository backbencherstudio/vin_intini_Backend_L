<?php

namespace App\Http\Controllers\Api;

use App\Enums\PlanType;
use App\Events\ConversationUpdated;
use App\Events\MessageDeleted;
use App\Events\MessageReactionChanged;
use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Subscription;
use App\Models\User;
use App\Services\Messaging\MessageDeliveryService;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;

class MessageController extends Controller
{
    /**
     * List paginated messages of a conversation.
     */
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $currentUser = $request->user();

        if (($error = $this->authorizeConversation($conversation, $currentUser->id)) instanceof JsonResponse) {
            return $error;
        }

        $validated = $request->validate([
            'after_id' => 'sometimes|integer|min:0',
        ]);
        $afterId = $validated['after_id'] ?? null;

        $conversation->markAsReadFor($currentUser->id);

        $messages = $conversation->visibleMessagesFor($currentUser->id)
            ->with([
                'sender' => fn ($q) => $q->select(['id', 'first_name', 'last_name', 'title', 'profile_image'])->withTrashed(),
                'replyTo.sender' => fn ($q) => $q->select(['id', 'first_name', 'last_name'])->withTrashed(),
                'reactions.user' => fn ($q) => $q->select(['id', 'first_name', 'last_name'])->withTrashed(),
            ])
            ->when(
                $afterId !== null,
                fn ($query) => $query->where('messages.id', '>', $afterId)->orderBy('messages.id'),
                fn ($query) => $query->orderByDesc('messages.id')
            )
            ->cursorPaginate(50);

        $conversation->load(['user1' => fn ($q) => $q->withTrashed(), 'user2' => fn ($q) => $q->withTrashed()]);
        $otherUser = $conversation->getOtherUser($currentUser->id);

        $isConnected = false;

        if ($otherUser instanceof User && ! $otherUser->trashed()) {
            $isConnected = Connection::query()
                ->accepted()
                ->where(function ($query) use ($currentUser, $otherUser): void {
                    $query->where('sender_id', $currentUser->id)->where('receiver_id', $otherUser->id)
                        ->orWhere('sender_id', $otherUser->id)->where('receiver_id', $currentUser->id);
                })
                ->exists();
        }

        $messageItems = collect($messages->items());

        $canDirectMessage = $this->isProIndustryUser($currentUser) || ($otherUser instanceof User && $this->isProIndustryUser($otherUser));
        $effectiveConnected = $isConnected || $canDirectMessage;

        if ($afterId === null) {
            $messageItems = $messageItems->reverse();
        }

        $data = $messageItems->values()->map(fn (Message $message): array => [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'is_mine' => $message->sender_id === $currentUser->id,
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
        ])->values();

        $unreadSummary = Conversation::unreadSummaryFor($currentUser->id);

        event(new ConversationUpdated(
            $conversation,
            $currentUser,
            $conversation->unreadCountFor($currentUser->id),
            null,
            $unreadSummary['unread_conversation_count'],
            $unreadSummary['total_unread_messages'],
        ));

        return response()->json([
            'success' => true,
            'other_user' => $otherUser instanceof User ? [
                'id' => $otherUser->id,
                'name' => trim(($otherUser->first_name ?? '').' '.($otherUser->last_name ?? '')),
                'first_name' => $otherUser->first_name,
                'last_name' => $otherUser->last_name,
                'title' => $otherUser->title,
                'profile_image_url' => $otherUser->profile_image_url,
                'cover_image_url' => $otherUser->cover_image_url,
                'has_premium' => $this->userHasActiveSubscription($otherUser->id),
                'is_connected' => $effectiveConnected,
            ] : null,
            'data' => $data,
            'next_cursor' => $messages->nextCursor()?->encode(),
            'has_more' => $messages->hasMorePages(),
        ]);
    }

    /**
     * Create and send a new message in a conversation.
     */
    public function store(Request $request, Conversation $conversation, MessageDeliveryService $deliveryService): JsonResponse
    {
        $currentUser = $request->user();

        if (($error = $this->authorizeConversation($conversation, $currentUser->id)) instanceof JsonResponse) {
            return $error;
        }

        $conversation->load(['user1' => fn ($q) => $q->withTrashed(), 'user2' => fn ($q) => $q->withTrashed()]);
        $otherUser = $conversation->getOtherUser($currentUser->id);

        if (! $otherUser instanceof User || $otherUser->trashed()) {
            return response()->json(['success' => false, 'message' => 'Cannot send messages to a deleted user.'], 403);
        }

        $isConnected = Connection::query()
            ->accepted()
            ->where(function ($query) use ($currentUser, $otherUser): void {
                $query->where('sender_id', $currentUser->id)->where('receiver_id', $otherUser->id)
                    ->orWhere('sender_id', $otherUser->id)->where('receiver_id', $currentUser->id);
            })
            ->exists();

        $canDirectMessage = $this->isProIndustryUser($currentUser) || $this->isProIndustryUser($otherUser);

        if (! $isConnected && ! $canDirectMessage) {
            return response()->json(['success' => false, 'message' => 'You must be connected to message this user.'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:text,voice,file,image,video',
            'message' => 'required_if:type,text|nullable|string',
            'file' => 'required_if:type,file|required_if:type,voice|required_if:type,image|required_if:type,video|nullable|file|max:102400',
            'duration' => 'nullable|integer|min:0',
            'reply_to_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail) use ($conversation, $currentUser): void {
                    if ($value && ! $conversation->visibleMessagesFor($currentUser->id)->where('id', $value)->exists()) {
                        $fail('The replied message does not exist in this conversation.');
                    }
                },
            ],
        ]);

        $extractedDuration = null;
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            if (($validated['duration'] ?? null) === null && $this->isMediaFile($file)) {
                $extractedDuration = $this->extractMediaDuration($file);
            }
        }

        $message = $deliveryService->sendMessage(
            $conversation,
            $currentUser,
            $otherUser,
            $validated,
            $request->file('file'),
            $extractedDuration
        );

        return response()->json([
            'success' => true,
            'message' => 'Message sent successfully.',
            'data' => [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender_id' => $message->sender_id,
                'is_mine' => $message->sender_id === $currentUser->id,
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
     * Add, change or remove the user's reaction on a message.
     */
    public function react(Request $request, Message $message): JsonResponse
    {
        $currentUser = $request->user();

        if (($error = $this->authorizeMessage($message, $currentUser->id)) instanceof JsonResponse) {
            return $error;
        }

        $validated = $request->validate([
            'reaction' => [
                'required',
                'string',
                function (string $attribute, mixed $value, Closure $fail): void {
                    if (! $this->isSingleEmoji($value)) {
                        $fail('The reaction must be a single emoji.');
                    }
                },
            ],
        ]);

        $reaction = $message->reactions()->where('user_id', $currentUser->id)->first();

        if ($reaction && $reaction->reaction === $validated['reaction']) {
            $reaction->delete();
            $changed = null;
        } elseif ($reaction) {
            $reaction->update(['reaction' => $validated['reaction']]);
            $changed = $validated['reaction'];
        } else {
            $message->reactions()->create([
                'user_id' => $currentUser->id,
                'reaction' => $validated['reaction'],
            ]);
            $changed = $validated['reaction'];
        }

        $message->load('reactions.user:id,first_name,last_name');

        $reactions = $this->reactionSummary($message);

        event(new MessageReactionChanged($message, $changed, $reactions, $currentUser->id));

        return response()->json([
            'success' => true,
            'message' => $changed ? 'Reaction added.' : 'Reaction removed.',
            'my_reaction' => $changed,
            'reactions' => $reactions,
        ]);
    }

    /**
     * Remove the user's reaction from a message.
     */
    public function unreact(Request $request, Message $message): JsonResponse
    {
        $currentUser = $request->user();

        if (($error = $this->authorizeMessage($message, $currentUser->id)) instanceof JsonResponse) {
            return $error;
        }

        $message->reactions()->where('user_id', $currentUser->id)->delete();

        $message->load('reactions.user:id,first_name,last_name');

        $reactions = $this->reactionSummary($message);

        event(new MessageReactionChanged($message, null, $reactions, $currentUser->id));

        return response()->json([
            'success' => true,
            'message' => 'Reaction removed.',
            'my_reaction' => null,
            'reactions' => $reactions,
        ]);
    }

    /**
     * Build a compact preview of the replied message.
     */
    private function replyPreview(?Message $message): ?array
    {
        if (! $message instanceof Message) {
            return null;
        }

        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender_name' => trim(($message->sender?->first_name ?? '').' '.($message->sender?->last_name ?? '')),
            'type' => $message->type,
            'message' => $message->message,
            'file_url' => $message->file_url,
            'file_name' => $message->file_name,
            'file_category' => $message->file_category,
            'created_at' => $message->created_at?->toISOString(),
        ];
    }

    /**
     * Check if the uploaded file is audio or video.
     */
    private function isMediaFile(UploadedFile $file): bool
    {
        $mime = $file->getMimeType();

        return str_contains($mime, 'audio') || str_contains($mime, 'video');
    }

    /**
     * Extract the duration of a media file with ffprobe, with pure-PHP fallback for WAV files.
     */
    private function extractMediaDuration(UploadedFile $file): ?int
    {
        $path = $file->getRealPath();
        if ($path && file_exists($path)) {
            $fp = @fopen($path, 'rb');
            if ($fp) {
                $header = fread($fp, 12);
                if (strlen($header) === 12 && str_starts_with($header, 'RIFF') && substr($header, 8, 4) === 'WAVE') {
                    $byteRate = null;
                    $dataSize = null;
                    while (! feof($fp)) {
                        $chunkHeader = fread($fp, 8);
                        if (strlen($chunkHeader) < 8) {
                            break;
                        }
                        $chunkId = substr($chunkHeader, 0, 4);
                        $chunkSize = unpack('V', substr($chunkHeader, 4, 4))[1] ?? 0;
                        if ($chunkId === 'fmt ') {
                            $fmtData = fread($fp, $chunkSize);
                            if (strlen($fmtData) >= 12) {
                                $byteRate = unpack('V', substr($fmtData, 8, 4))[1] ?? null;
                            }
                        } elseif ($chunkId === 'data') {
                            $dataSize = $chunkSize;
                            break;
                        } else {
                            fseek($fp, $chunkSize, SEEK_CUR);
                        }
                    }
                    fclose($fp);
                    if ($byteRate && $dataSize) {
                        return (int) round($dataSize / $byteRate);
                    }
                } else {
                    fclose($fp);
                }
            }
        }

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

    /**
     * Check if a user is a participant of a conversation.
     */
    private function isParticipant(int $userId, int $conversationId): bool
    {
        return Conversation::where('id', $conversationId)
            ->where(fn ($query) => $query->where('user_id_1', $userId)->orWhere('user_id_2', $userId))
            ->exists();
    }

    /**
     * Check if a string is exactly one emoji.
     */
    private function isSingleEmoji(string $value): bool
    {
        return preg_match('/^(?:\p{Extended_Pictographic}(?:\p{Emoji_Modifier}|\x{FE0F}|\x{200D}\p{Extended_Pictographic}\p{Emoji_Modifier}?)*|[\x{1F1E6}-\x{1F1FF}]{2})$/u', $value) === 1;
    }

    /**
     * Build a per-emoji reaction summary with count and users.
     */
    private function reactionSummary(Message $message): array
    {
        return $message->reactions
            ->groupBy('reaction')
            ->map(fn ($reactions, string $reaction): array => [
                'reaction' => $reaction,
                'count' => $reactions->count(),
                'users' => $reactions
                    ->map(fn ($item): array => [
                        'id' => $item->user_id,
                        'name' => trim(($item->user?->first_name ?? '').' '.($item->user?->last_name ?? '')),
                    ])
                    ->values(),
            ])
            ->values()
            ->all();
    }

    /**
     * Check if a user has an active subscription.
     */
    private function userHasActiveSubscription(int $userId): bool
    {
        return Subscription::where('user_id', $userId)
            ->whereIn('status', ['active', 'trialing'])
            ->exists();
    }

    /**
     * Delete a message if the user is its sender.
     */
    public function destroy(Request $request, Message $message): JsonResponse
    {
        $currentUser = $request->user();

        if (($error = $this->authorizeMessage($message, $currentUser->id)) instanceof JsonResponse) {
            return $error;
        }

        if ($message->sender_id !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own messages.',
            ], 403);
        }

        $message->delete();

        event(new MessageDeleted($message->id, $message->conversation_id));

        $conversation = $message->conversation;

        if ($conversation->last_message_id === $message->id) {
            $conversation->update(['last_message_id' => $conversation->messages()->max('id')]);
        }

        $otherUser = $conversation->getOtherUser($currentUser->id);

        if ($otherUser && ! $conversation->isArchivedFor($otherUser->id)) {
            $unreadSummary = Conversation::unreadSummaryFor($otherUser->id);

            event(new ConversationUpdated(
                $conversation,
                $otherUser,
                $conversation->unreadCountFor($otherUser->id),
                null,
                $unreadSummary['unread_conversation_count'],
                $unreadSummary['total_unread_messages'],
            ));
        }

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully.',
        ]);
    }

    /**
     * Authorize that the user participates in the message's conversation
     * and has not deleted the conversation from their side.
     */
    private function authorizeMessage(Message $message, int $userId): ?JsonResponse
    {
        if (! $this->isParticipant($userId, $message->conversation_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($message->conversation->isDeletedFor($userId)) {
            return response()->json(['success' => false, 'message' => 'Conversation not found.'], 404);
        }

        return null;
    }

    /**
     * Authorize that the user participates in the conversation
     * and has not deleted it from their side.
     */
    private function authorizeConversation(Conversation $conversation, int $userId): ?JsonResponse
    {
        if ($conversation->user_id_1 !== $userId && $conversation->user_id_2 !== $userId) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($conversation->isDeletedFor($userId)) {
            return response()->json(['success' => false, 'message' => 'Conversation not found.'], 404);
        }

        return null;
    }

    protected function isProIndustryUser($user): bool
    {
        if (! $user) {
            return false;
        }

        return $user->tier === 'pro_industry'
            || $user->industry()->exists()
            || $user->hasActiveSubscription(PlanType::INDUSTRY);
    }
}
