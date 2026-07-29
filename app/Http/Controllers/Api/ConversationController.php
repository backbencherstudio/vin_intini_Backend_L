<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        $conversations = Conversation::forUser($currentUser->id)
            ->with(['lastMessage', 'user1', 'user2'])
            ->orderByDesc('updated_at')
            ->get();

        $totalUnread = 0;

        $data = $conversations->map(function (Conversation $conversation) use ($currentUser, &$totalUnread) {
            $otherUser = $conversation->getOtherUser($currentUser->id);
            $unreadCount = $conversation->unreadCountFor($currentUser->id);
            $totalUnread += $unreadCount;

            return [
                'id' => $conversation->id,
                'user' => [
                    'id' => $otherUser->id,
                    'name' => trim(($otherUser->first_name ?? '').' '.($otherUser->last_name ?? '')),
                    'first_name' => $otherUser->first_name,
                    'last_name' => $otherUser->last_name,
                    'title' => $otherUser->title,
                    'profile_image_url' => $otherUser->profile_image_url,
                    'cover_image_url' => $otherUser->cover_image_url,
                ],
                'last_message' => $conversation->lastMessage ? [
                    'id' => $conversation->lastMessage->id,
                    'type' => $conversation->lastMessage->type,
                    'message' => $conversation->lastMessage->message,
                    'file_url' => $conversation->lastMessage->file_url,
                    'file_name' => $conversation->lastMessage->file_name,
                    'sender_id' => $conversation->lastMessage->sender_id,
                    'created_at' => $conversation->lastMessage->created_at->toISOString(),
                ] : null,
                'unread_count' => $unreadCount,
                'updated_at' => $conversation->updated_at->toISOString(),
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => $data,
            'total_unread' => $totalUnread,
        ]);
    }

    public function markAsRead(Request $request, Conversation $conversation): JsonResponse
    {
        $currentUser = $request->user();

        if ($conversation->user_id_1 !== $currentUser->id && $conversation->user_id_2 !== $currentUser->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $conversation->markAsReadFor($currentUser->id);

        return response()->json([
            'success' => true,
            'message' => 'Conversation marked as read.',
        ]);
    }

    public function showOrCreate(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            return response()->json(['success' => false, 'message' => 'Cannot start conversation with yourself.'], 422);
        }

        $connected = Connection::query()
            ->accepted()
            ->where(function ($q) use ($currentUser, $user) {
                $q->where('sender_id', $currentUser->id)->where('receiver_id', $user->id)
                    ->orWhere('sender_id', $user->id)->where('receiver_id', $currentUser->id);
            })
            ->exists();

        if (! $connected) {
            return response()->json(['success' => false, 'message' => 'You must be connected to start a conversation.'], 403);
        }

        $conversation = Conversation::betweenUsers($currentUser->id, $user->id);

        $conversation->load(['lastMessage', 'user1', 'user2']);

        $otherUser = $conversation->getOtherUser($currentUser->id);
        $unreadCount = $conversation->unreadCountFor($currentUser->id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $conversation->id,
                'user' => [
                    'id' => $otherUser->id,
                    'name' => trim(($otherUser->first_name ?? '').' '.($otherUser->last_name ?? '')),
                    'first_name' => $otherUser->first_name,
                    'last_name' => $otherUser->last_name,
                    'title' => $otherUser->title,
                    'profile_image_url' => $otherUser->profile_image_url,
                    'cover_image_url' => $otherUser->cover_image_url,
                ],
                'last_message' => $conversation->lastMessage ? [
                    'id' => $conversation->lastMessage->id,
                    'type' => $conversation->lastMessage->type,
                    'message' => $conversation->lastMessage->message,
                    'file_url' => $conversation->lastMessage->file_url,
                    'file_name' => $conversation->lastMessage->file_name,
                    'sender_id' => $conversation->lastMessage->sender_id,
                    'created_at' => $conversation->lastMessage->created_at->toISOString(),
                ] : null,
                'unread_count' => $unreadCount,
                'updated_at' => $conversation->updated_at->toISOString(),
            ],
        ]);
    }

    public function destroy(Request $request, Conversation $conversation): JsonResponse
    {
        $currentUser = $request->user();

        if ($conversation->user_id_1 !== $currentUser->id && $conversation->user_id_2 !== $currentUser->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $conversation->messages()->delete();
        $conversation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Conversation deleted successfully.',
        ]);
    }
}
