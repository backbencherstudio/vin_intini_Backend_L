<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\IndustryConversation;
use App\Models\IndustryMessage;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class IndustryConversationController extends Controller
{
    /**
     * List conversations for the industry dashboard or for a user chatting with industries.
     */
    public function index(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $status = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));
        $mode = $request->query('mode', 'industry'); // 'industry' or 'user'

        $isIndustryMode = $mode !== 'user' && $currentUser->industry()->exists();

        if ($isIndustryMode) {
            $industry = $currentUser->industry;

            $conversations = IndustryConversation::forIndustry($industry->id)
                ->notDeletedForIndustry()
                ->when(
                    $status === 'archived',
                    fn ($q) => $q->archivedOnlyForIndustry(),
                    fn ($q) => $q->notArchivedForIndustry()
                )
                ->with(['lastMessage', 'user' => fn ($q) => $q->withTrashed()])
                ->orderByDesc('updated_at')
                ->get();

            $premiumUserIds = Subscription::query()
                ->whereIn('status', ['active', 'trialing'])
                ->whereIn('user_id', $conversations->pluck('user_id')->unique())
                ->pluck('user_id')
                ->all();

            $data = $conversations
                ->map(function (IndustryConversation $conv) use ($premiumUserIds) {
                    $otherUser = $conv->user;
                    if (! $otherUser) {
                        return null;
                    }

                    $lastMsg = $conv->lastMessage;
                    $clearedId = $conv->industry_cleared_message_id;
                    $visibleLast = ($lastMsg && (! $clearedId || $lastMsg->id > $clearedId)) ? $lastMsg : null;

                    $userName = trim(($otherUser->first_name ?? '').' '.($otherUser->last_name ?? ''));

                    return [
                        'id' => $conv->id,
                        'industry_id' => $conv->industry_id,
                        'user' => [
                            'id' => $otherUser->id,
                            'name' => $userName,
                            'first_name' => $otherUser->first_name,
                            'last_name' => $otherUser->last_name,
                            'title' => $otherUser->title,
                            'profile_image_url' => $otherUser->profile_image_url,
                            'has_premium' => in_array($otherUser->id, $premiumUserIds),
                        ],
                        'last_message' => $visibleLast ? [
                            'id' => $visibleLast->id,
                            'type' => $visibleLast->display_type,
                            'message' => $visibleLast->message,
                            'file_url' => $visibleLast->file_url,
                            'file_name' => $visibleLast->file_name,
                            'sender_type' => $visibleLast->sender_type,
                            'sender_id' => $visibleLast->sender_id,
                            'created_at' => $visibleLast->created_at->toISOString(),
                            'created_at_diff' => $visibleLast->created_at->diffForHumans(),
                        ] : null,
                        'is_archived' => $conv->isArchivedForIndustry(),
                        'unread_count' => $conv->unreadCountForIndustry(),
                        '_search_name' => $userName,
                        'updated_at' => $conv->updated_at->toISOString(),
                    ];
                })
                ->filter()
                ->when($status === 'unread', fn ($items) => $items->filter(fn ($item) => $item['unread_count'] > 0))
                ->when($search !== '', fn ($items) => $items->filter(function ($item) use ($search) {
                    return str_contains(mb_strtolower($item['_search_name']), mb_strtolower($search));
                }))
                ->values()
                ->map(fn ($item) => collect($item)->except('_search_name')->all());

            return response()->json([
                'success' => true,
                'mode' => 'industry',
                'industry' => [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'logo' => $industry->logo,
                ],
                'data' => $data,
            ]);
        }

        // User mode: listing conversations this user has with companies/industries
        $conversations = IndustryConversation::forUser($currentUser->id)
            ->notDeletedForUser()
            ->when(
                $status === 'archived',
                fn ($q) => $q->archivedOnlyForUser(),
                fn ($q) => $q->notArchivedForUser()
            )
            ->with(['lastMessage', 'industry'])
            ->orderByDesc('updated_at')
            ->get();

        $data = $conversations
            ->map(function (IndustryConversation $conv) {
                $ind = $conv->industry;
                if (! $ind) {
                    return null;
                }

                $lastMsg = $conv->lastMessage;
                $clearedId = $conv->user_cleared_message_id;
                $visibleLast = ($lastMsg && (! $clearedId || $lastMsg->id > $clearedId)) ? $lastMsg : null;

                return [
                    'id' => $conv->id,
                    'industry' => [
                        'id' => $ind->id,
                        'name' => $ind->name,
                        'tagline' => $ind->tagline,
                        'industry' => $ind->industry,
                        'logo' => $ind->logo,
                        'cover_image' => $ind->cover_image,
                    ],
                    'last_message' => $visibleLast ? [
                        'id' => $visibleLast->id,
                        'type' => $visibleLast->display_type,
                        'message' => $visibleLast->message,
                        'file_url' => $visibleLast->file_url,
                        'file_name' => $visibleLast->file_name,
                        'sender_type' => $visibleLast->sender_type,
                        'sender_id' => $visibleLast->sender_id,
                        'created_at' => $visibleLast->created_at->toISOString(),
                        'created_at_diff' => $visibleLast->created_at->diffForHumans(),
                    ] : null,
                    'is_archived' => $conv->isArchivedForUser(),
                    'unread_count' => $conv->unreadCountForUser(),
                    '_search_name' => $ind->name,
                    'updated_at' => $conv->updated_at->toISOString(),
                ];
            })
            ->filter()
            ->when($status === 'unread', fn ($items) => $items->filter(fn ($item) => $item['unread_count'] > 0))
            ->when($search !== '', fn ($items) => $items->filter(function ($item) use ($search) {
                return str_contains(mb_strtolower($item['_search_name']), mb_strtolower($search));
            }))
            ->values()
            ->map(fn ($item) => collect($item)->except('_search_name')->all());

        return response()->json([
            'success' => true,
            'mode' => 'user',
            'data' => $data,
        ]);
    }

    /**
     * Start or fetch conversation with an industry (initiated by a user from the industry profile).
     */
    public function showOrCreateForUser(Request $request, Industry $industry): JsonResponse
    {
        $currentUser = $request->user();

        if ((int) $industry->created_by === (int) $currentUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot message your own industry. Manage it via your Pro Industry Dashboard.',
            ], 422);
        }

        $conversation = IndustryConversation::where('industry_id', $industry->id)
            ->where('user_id', $currentUser->id)
            ->first();

        if (! $conversation) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot initiate a conversation with a company. The company must message you first.',
            ], 403);
        }

        $conversation->restoreForUser();
        $conversation->load(['lastMessage', 'industry']);

        $lastMsg = $conversation->lastMessage;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $conversation->id,
                'industry' => [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'tagline' => $industry->tagline,
                    'industry' => $industry->industry,
                    'address' => $industry->address,
                    'logo' => $industry->logo,
                    'cover_image' => $industry->cover_image,
                ],
                'last_message' => $lastMsg ? [
                    'id' => $lastMsg->id,
                    'type' => $lastMsg->display_type,
                    'message' => $lastMsg->message,
                    'sender_type' => $lastMsg->sender_type,
                    'sender_id' => $lastMsg->sender_id,
                    'created_at' => $lastMsg->created_at->toISOString(),
                ] : null,
                'unread_count' => $conversation->unreadCountForUser(),
                'is_archived' => $conversation->isArchivedForUser(),
                'updated_at' => $conversation->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Start or fetch conversation with a user (initiated by a pro-industry user from industry dashboard or applicant).
     */
    public function showOrCreateForIndustry(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();
        $industry = $currentUser->industry;

        if (! $industry) {
            return response()->json([
                'success' => false,
                'message' => 'You must have an industry profile to send messages as an industry.',
            ], 403);
        }

        if ($currentUser->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot start conversation with yourself.',
            ], 422);
        }

        $conversation = IndustryConversation::between($industry->id, $user->id);
        $conversation->restoreForIndustry();
        $conversation->load(['lastMessage', 'user' => fn ($q) => $q->withTrashed()]);

        $otherUser = $conversation->user;
        $lastMsg = $conversation->lastMessage;

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $conversation->id,
                'industry_id' => $industry->id,
                'user' => [
                    'id' => $otherUser->id,
                    'name' => trim(($otherUser->first_name ?? '').' '.($otherUser->last_name ?? '')),
                    'first_name' => $otherUser->first_name,
                    'last_name' => $otherUser->last_name,
                    'title' => $otherUser->title,
                    'profile_image_url' => $otherUser->profile_image_url,
                    'cover_image_url' => $otherUser->cover_image_url,
                    'has_premium' => $otherUser->subscriptions()->whereIn('status', ['active', 'trialing'])->exists(),
                ],
                'last_message' => $lastMsg ? [
                    'id' => $lastMsg->id,
                    'type' => $lastMsg->display_type,
                    'message' => $lastMsg->message,
                    'sender_type' => $lastMsg->sender_type,
                    'sender_id' => $lastMsg->sender_id,
                    'created_at' => $lastMsg->created_at->toISOString(),
                ] : null,
                'unread_count' => $conversation->unreadCountForIndustry(),
                'is_archived' => $conversation->isArchivedForIndustry(),
                'updated_at' => $conversation->updated_at->toISOString(),
            ],
        ]);
    }

    /**
     * Search users for a pro-industry account to initiate messaging with anyone.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('query', $request->query('search', '')));
        $currentUser = $request->user();

        if (mb_strlen($search) < 2) {
            return response()->json(['success' => true, 'data' => []]);
        }

        $users = User::query()
            ->where('id', '!=', $currentUser->id)
            ->whereNull('deleted_at')
            ->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$search}%"])
                    ->orWhere('title', 'like', "%{$search}%");
            })
            ->limit(15)
            ->get(['id', 'first_name', 'last_name', 'title', 'profile_image']);

        $premiumUserIds = Subscription::query()
            ->whereIn('status', ['active', 'trialing'])
            ->whereIn('user_id', $users->pluck('id'))
            ->pluck('user_id')
            ->all();

        $data = $users->map(fn (User $u) => [
            'id' => $u->id,
            'name' => trim(($u->first_name ?? '').' '.($u->last_name ?? '')),
            'first_name' => $u->first_name,
            'last_name' => $u->last_name,
            'title' => $u->title,
            'profile_image_url' => $u->profile_image_url,
            'has_premium' => in_array($u->id, $premiumUserIds),
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    /**
     * Unread count summary for industry dashboard.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $industry = $currentUser->industry;

        if (! $industry) {
            return response()->json([
                'success' => true,
                'data' => ['unread_conversation_count' => 0, 'total_unread_messages' => 0],
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => IndustryConversation::unreadSummaryForIndustry($industry->id),
        ]);
    }

    public function markAsRead(Request $request, IndustryConversation $conversation): JsonResponse
    {
        $currentUser = $request->user();

        if ($this->isIndustryOwner($currentUser, $conversation)) {
            $conversation->markAsReadForIndustry();
        } elseif ((int) $conversation->user_id === (int) $currentUser->id) {
            $conversation->markAsReadForUser();
        } else {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json(['success' => true, 'message' => 'Marked as read.']);
    }

    public function markAsUnread(Request $request, IndustryConversation $conversation): JsonResponse
    {
        $currentUser = $request->user();

        if ($this->isIndustryOwner($currentUser, $conversation)) {
            $conversation->markAsUnreadForIndustry();
        } elseif ((int) $conversation->user_id === (int) $currentUser->id) {
            $conversation->markAsUnreadForUser();
        } else {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json(['success' => true, 'message' => 'Marked as unread.']);
    }

    public function archive(Request $request, IndustryConversation $conversation): JsonResponse
    {
        $currentUser = $request->user();

        if ($this->isIndustryOwner($currentUser, $conversation)) {
            $conversation->archiveForIndustry();
        } elseif ((int) $conversation->user_id === (int) $currentUser->id) {
            $conversation->archiveForUser();
        } else {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json(['success' => true, 'message' => 'Archived.']);
    }

    public function unarchive(Request $request, IndustryConversation $conversation): JsonResponse
    {
        $currentUser = $request->user();

        if ($this->isIndustryOwner($currentUser, $conversation)) {
            $conversation->unarchiveForIndustry();
        } elseif ((int) $conversation->user_id === (int) $currentUser->id) {
            $conversation->unarchiveForUser();
        } else {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json(['success' => true, 'message' => 'Unarchived.']);
    }

    public function destroy(Request $request, IndustryConversation $conversation): JsonResponse
    {
        $currentUser = $request->user();

        if ($this->isIndustryOwner($currentUser, $conversation)) {
            $conversation->deleteForIndustry();
        } elseif ((int) $conversation->user_id === (int) $currentUser->id) {
            $conversation->deleteForUser();
        } else {
            return response()->json(['success' => false, 'message' => 'Unauthorized.'], 403);
        }

        return response()->json(['success' => true, 'message' => 'Deleted.']);
    }

    protected function isIndustryOwner(User $user, IndustryConversation $conversation): bool
    {
        return (int) $conversation->industry?->created_by === (int) $user->id;
    }
}

