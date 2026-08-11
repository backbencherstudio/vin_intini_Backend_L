<?php

namespace App\Http\Controllers\Api;

use App\Events\MessageSent;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Subscription;
use App\Notifications\NewMessageNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MessageController extends Controller
{
    public function index(Request $request, Conversation $conversation): JsonResponse
    {
        $currentUser = $request->user();

        if ($conversation->user_id_1 !== $currentUser->id && $conversation->user_id_2 !== $currentUser->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $messages = $conversation->messages()
            ->with(['sender:id,first_name,last_name,title,profile_image', 'reactions'])
            ->orderBy('id')
            ->cursorPaginate(50);

        $otherUser = $conversation->getOtherUser($currentUser->id);

        $data = $messages->map(fn (Message $message) => [
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

    public function store(Request $request, Conversation $conversation): JsonResponse
    {
        $currentUser = $request->user();

        if ($conversation->user_id_1 !== $currentUser->id && $conversation->user_id_2 !== $currentUser->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'type' => 'required|in:text,voice,file',
            'message' => 'required_if:type,text|nullable|string',
            'file' => 'required_if:type,file|required_if:type,voice|nullable|file|max:102400',
            'duration' => 'nullable|integer|min:0',
        ]);

        $data = [
            'conversation_id' => $conversation->id,
            'sender_id' => $currentUser->id,
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
        }

        $message = Message::create($data);

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
                'created_at' => $message->created_at->toISOString(),
            ],
        ], 201);
    }

    public function react(Request $request, Message $message): JsonResponse
    {
        $currentUser = $request->user();

        if (! $this->isParticipant($currentUser->id, $message->conversation_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'reaction' => 'required|string|max:20',
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

        $message->load('reactions');

        return response()->json([
            'success' => true,
            'message' => $changed ? 'Reaction added.' : 'Reaction removed.',
            'my_reaction' => $changed,
            'reactions' => $this->reactionSummary($message),
        ]);
    }

    public function unreact(Request $request, Message $message): JsonResponse
    {
        $currentUser = $request->user();

        if (! $this->isParticipant($currentUser->id, $message->conversation_id)) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $message->reactions()->where('user_id', $currentUser->id)->delete();

        $message->load('reactions');

        return response()->json([
            'success' => true,
            'message' => 'Reaction removed.',
            'my_reaction' => null,
            'reactions' => $this->reactionSummary($message),
        ]);
    }

    private function isParticipant(int $userId, int $conversationId): bool
    {
        return Conversation::where('id', $conversationId)
            ->where(fn ($query) => $query->where('user_id_1', $userId)->orWhere('user_id_2', $userId))
            ->exists();
    }

    private function reactionSummary(Message $message): array
    {
        return $message->reactions
            ->groupBy('reaction')
            ->map(fn ($reactions, string $reaction) => [
                'reaction' => $reaction,
                'count' => $reactions->count(),
                'user_ids' => $reactions->pluck('user_id')->values(),
            ])
            ->values()
            ->all();
    }

    private function userHasActiveSubscription(int $userId): bool
    {
        return Subscription::where('user_id', $userId)
            ->whereIn('status', ['active', 'trialing', 'paused'])
            ->exists();
    }

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
