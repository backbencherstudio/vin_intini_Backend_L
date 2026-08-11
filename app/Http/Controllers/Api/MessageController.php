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
            ->with('sender:id,first_name,last_name,title,profile_image')
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
            'duration' => $message->duration,
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
                'duration' => $message->duration,
                'created_at' => $message->created_at->toISOString(),
            ],
        ], 201);
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
