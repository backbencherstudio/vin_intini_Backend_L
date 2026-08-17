<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageReactionChanged;
use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Subscription;
use App\Notifications\NewMessageNotification;
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

        if ($conversation->user_id_1 !== $currentUser->id && $conversation->user_id_2 !== $currentUser->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $conversation->markAsReadFor($currentUser->id);

        $messages = $conversation->messages()
            ->with(['sender:id,first_name,last_name,title,profile_image', 'replyTo.sender:id,first_name,last_name', 'reactions.user:id,first_name,last_name'])
            ->orderBy('id', 'desc')
            ->cursorPaginate(50);

        $otherUser = $conversation->getOtherUser($currentUser->id);

        $data = $messages->reverse()->values()->map(fn (Message $message) => [
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

        return response()->json([
            'success' => true,
            'other_user' => [
                'id' => $otherUser->id,
                'name' => trim(($otherUser->first_name ?? '').' '.($otherUser->last_name ?? '')),
                'first_name' => $otherUser->first_name,
                'last_name' => $otherUser->last_name,
                'title' => $otherUser->title,
                'profile_image_url' => $otherUser->profile_image_url,
                'cover_image_url' => $otherUser->cover_image_url,
                'has_premium' => $this->userHasActiveSubscription($otherUser->id),
            ],
            'data' => $data,
            'next_cursor' => $messages->nextCursor()?->encode(),
            'has_more' => $messages->hasMorePages(),
        ]);
    }

    /**
     * Create and send a new message in a conversation.
     */
    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $currentUser = $request->user();

        if ($conversation->user_id_1 !== $currentUser->id && $conversation->user_id_2 !== $currentUser->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:text,voice,file,image,video',
            'message' => 'required_if:type,text|nullable|string',
            'file' => 'required_if:type,file|required_if:type,voice|required_if:type,image|required_if:type,video|nullable|file|max:102400',
            'duration' => 'nullable|integer|min:0',
            'reply_to_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, Closure $fail) use ($conversation): void {
                    if ($value && ! Message::where('id', $value)->where('conversation_id', $conversation->id)->exists()) {
                        $fail('The replied message does not exist in this conversation.');
                    }
                },
            ],
        ]);

        $data = [
            'conversation_id' => $conversation->id,
            'sender_id' => $currentUser->id,
            'reply_to_id' => $validated['reply_to_id'] ?? null,
            'type' => $validated['type'],
            'message' => $validated['message'] ?? null,
            'duration' => $validated['duration'] ?? null,
        ];

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $path = $file->store('conversations/'.$conversation->id, 'public');
            $data['file_path'] = $path;
            $data['file_name'] = $file->getClientOriginalName();
            $data['file_size'] = $file->getSize();

            if (($data['duration'] ?? null) === null && $this->isMediaFile($file)) {
                $data['duration'] = $this->extractMediaDuration($file);
            }
        }

        $message = Message::create($data)->load('replyTo.sender:id,first_name,last_name');

        $conversation->update(['last_message_id' => $message->id]);

        event(new MessageSent($message));

        $otherUser = $conversation->getOtherUser($currentUser->id);
        $otherUser->notify(new NewMessageNotification($message));

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

        if (! $this->isParticipant($currentUser->id, $message->conversation_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
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

        if (! $this->isParticipant($currentUser->id, $message->conversation_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
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
        if (! $message) {
            return null;
        }

        return [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'sender_name' => trim(($message->sender->first_name ?? '').' '.($message->sender->last_name ?? '')),
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
     * Extract the duration of a media file with ffprobe.
     */
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
            ->map(fn ($reactions, string $reaction) => [
                'reaction' => $reaction,
                'count' => $reactions->count(),
                'users' => $reactions
                    ->map(fn ($item) => [
                        'id' => $item->user_id,
                        'name' => trim(($item->user->first_name ?? '').' '.($item->user->last_name ?? '')),
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
            ->whereIn('status', ['active', 'trialing', 'paused'])
            ->exists();
    }

    /**
     * Delete a message if the user is its sender.
     */
    public function destroy(Request $request, Message $message): JsonResponse
    {
        $currentUser = $request->user();

        if ($message->sender_id !== $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'You can only delete your own messages.',
            ], 403);
        }

        $message->delete();

        return response()->json([
            'success' => true,
            'message' => 'Message deleted successfully.',
        ]);
    }
}
