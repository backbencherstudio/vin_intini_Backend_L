<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Connection;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ConversationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $status = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));

        $conversations = Conversation::forUser($currentUser->id)
            ->when(
                $status === 'archived',
                fn ($q) => $q->archivedOnlyFor($currentUser->id),
                fn ($q) => $q->notArchivedFor($currentUser->id)
            )
            ->with(['lastMessage', 'user1', 'user2'])
            ->orderByDesc('updated_at')
            ->get();

        $unreadCounts = $this->loadUnreadCounts($conversations, $currentUser->id);

        $data = $conversations
            ->map(function (Conversation $conversation) use ($currentUser, $unreadCounts) {
                $otherUser = $conversation->getOtherUser($currentUser->id);
                $unreadCount = $unreadCounts[$conversation->id] ?? 0;

                return [
                    'id' => $conversation->id,
                    'user' => [
                        'id' => $otherUser->id,
                        'name' => trim(($otherUser->first_name ?? '').' '.($otherUser->last_name ?? '')),
                        'profile_image_url' => $otherUser->profile_image_url,
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
                    'is_archived' => $conversation->isArchivedFor($currentUser->id),
                    'unread_count' => $unreadCount,
                    '_search_name' => $otherUser->first_name.' '.$otherUser->last_name,
                    'updated_at' => $conversation->updated_at->toISOString(),
                ];
            })
            ->when($status === 'unread', fn ($items) => $items->filter(fn ($item) => $item['unread_count'] > 0))
            ->when($search !== '', fn ($items) => $items->filter(function ($item) use ($search) {
                return str_contains(mb_strtolower($item['_search_name']), mb_strtolower($search));
            }))
            ->values()
            ->map(fn ($item) => collect($item)->except('_search_name')->all());

        return response()->json([
            'success' => true,
            'data' => $data,
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

    public function markAsUnread(Request $request, Conversation $conversation): JsonResponse
    {
        $currentUser = $request->user();

        if ($conversation->user_id_1 !== $currentUser->id && $conversation->user_id_2 !== $currentUser->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        $conversation->markAsUnreadFor($currentUser->id);

        return response()->json([
            'success' => true,
            'message' => 'Conversation marked as unread.',
        ]);
    }

    public function archive(Request $request, Conversation $conversation): JsonResponse
    {
        $currentUser = $request->user();

        if ($conversation->user_id_1 !== $currentUser->id && $conversation->user_id_2 !== $currentUser->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if ($conversation->isArchivedFor($currentUser->id)) {
            return response()->json(['success' => false, 'message' => 'Conversation is already archived.'], 409);
        }

        $conversation->archiveFor($currentUser->id);

        return response()->json([
            'success' => true,
            'message' => 'Conversation archived.',
            'is_archived' => true,
        ]);
    }

    public function unarchive(Request $request, Conversation $conversation): JsonResponse
    {
        $currentUser = $request->user();

        if ($conversation->user_id_1 !== $currentUser->id && $conversation->user_id_2 !== $currentUser->id) {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        if (! $conversation->isArchivedFor($currentUser->id)) {
            return response()->json(['success' => false, 'message' => 'Conversation is not archived.'], 409);
        }

        $conversation->unarchiveFor($currentUser->id);

        return response()->json([
            'success' => true,
            'message' => 'Conversation unarchived.',
            'is_archived' => false,
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
                'is_archived' => $conversation->isArchivedFor($currentUser->id),
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

    private function loadUnreadCounts($conversations, int $userId): array
    {
        $conversationIds = $conversations->pluck('id');

        if ($conversationIds->isEmpty()) {
            return [];
        }

        $results = DB::table('messages')
            ->join('conversations', 'messages.conversation_id', '=', 'conversations.id')
            ->whereIn('messages.conversation_id', $conversationIds)
            ->where('messages.sender_id', '!=', $userId)
            ->where(function ($q) use ($userId) {
                $q->where(function ($q) use ($userId) {
                    $q->where('conversations.user_id_1', $userId)
                        ->where(function ($q) {
                            $q->whereColumn('messages.created_at', '>', 'conversations.user_1_last_read_at')
                                ->orWhereNull('conversations.user_1_last_read_at');
                        });
                })->orWhere(function ($q) use ($userId) {
                    $q->where('conversations.user_id_2', $userId)
                        ->where(function ($q) {
                            $q->whereColumn('messages.created_at', '>', 'conversations.user_2_last_read_at')
                                ->orWhereNull('conversations.user_2_last_read_at');
                        });
                });
            })
            ->groupBy('messages.conversation_id')
            ->selectRaw('messages.conversation_id, COUNT(*) as count')
            ->get();

        return $results->pluck('count', 'conversation_id')->toArray();
    }
}
