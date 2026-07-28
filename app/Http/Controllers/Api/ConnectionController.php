<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\ConnectionRequestMail;
use App\Models\Connection;
use App\Models\Conversation;
use App\Models\GroupInvitation;
use App\Models\User;
use App\Models\UserFollow;
use App\Notifications\ConnectionRequestAcceptedNotification;
use App\Notifications\ConnectionRequestReceivedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class ConnectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $search = trim((string) $request->query('search', ''));
        $sort = strtolower(trim((string) $request->query('sort', 'recent')));
        $perPage = $request->integer('limit', $request->integer('per_page', 10));
        $perPage = max(1, min($perPage, 100));
        // $perPage = max(1, min((int) $request->integer('per_page', 10), 50));
        $page = max(1, (int) $request->integer('page', 1));

        $connections = Connection::query()
            ->accepted()
            ->forUser($currentUser->id)
            ->with([
                'sender:id,first_name,last_name,title,profile_image',
                'receiver:id,first_name,last_name,title,profile_image',
            ])
            ->orderByDesc('responded_at')
            ->orderByDesc('id')
            ->get();

        if ($connections->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No accepted connection found.',
                'status' => 'success',
                'data' => [],
                'stats' => [
                    'total_connections' => 0,
                    'filtered_connections' => 0,
                ],
                'total' => 0,
                'limit' => $perPage,
                'current_page' => $page,
                'total_page' => 0,
                'last_page' => 0,
                'filters' => [
                    'search' => $search !== '' ? $search : null,
                    'sort' => $sort,
                ],
            ], 200);
        }

        $counterpartIds = $connections
            ->map(function (Connection $connectionRequest) use ($currentUser) {
                return $connectionRequest->sender_id === $currentUser->id
                    ? $connectionRequest->receiver_id
                    : $connectionRequest->sender_id;
            })
            ->unique()
            ->values();

        $followingIds = UserFollow::query()
            ->where('follower_id', $currentUser->id)
            ->whereIn('following_id', $counterpartIds)
            ->pluck('following_id')
            ->toArray();

        $items = $connections->map(function (Connection $connectionRequest) use ($currentUser) {
            $counterpart = $connectionRequest->sender_id === $currentUser->id
                ? $connectionRequest->receiver
                : $connectionRequest->sender;

            return [
                'payload' => $this->formatConnectionRequest($connectionRequest, $currentUser->id, []),
                'search_name' => trim(($counterpart->first_name ?? '').' '.($counterpart->last_name ?? '')),
                'connected_at' => $connectionRequest->responded_at?->timestamp ?? $connectionRequest->created_at?->timestamp ?? 0,
            ];
        });

        if ($search !== '') {
            $normalizedSearch = mb_strtolower($search);

            $items = $items->filter(function (array $item) use ($normalizedSearch) {
                return str_contains(mb_strtolower($item['search_name']), $normalizedSearch);
            });
        }

        $items = (match ($sort) {
            'old', 'oldest' => $items->sortBy('connected_at'),
            'az', 'a-z', 'name' => $items->sortBy('search_name', SORT_NATURAL | SORT_FLAG_CASE),
            default => $items->sortByDesc('connected_at'),
        })->values();

        $paginatedItems = $items
            ->slice(($page - 1) * $perPage, $perPage)
            ->values();

        $paginator = new LengthAwarePaginator(
            $paginatedItems,
            $items->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $mutualConnections = $this->buildMutualConnectionsMap($currentUser->id, $counterpartIds);

        $connectionData = $paginator->getCollection()->map(function (array $item) use ($mutualConnections, $followingIds) {
            $connection = $item['payload'];
            $counterpartId = $connection['user']['id'];

            $connection['mutual_connections_count'] = $mutualConnections[$counterpartId]['count'] ?? 0;
            $connection['mutual_connections'] = $mutualConnections[$counterpartId]['preview'] ?? [];

            $connection['user']['is_following'] = in_array($counterpartId, $followingIds);

            return $connection;
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Connections retrieved successfully.',
            'status' => 'success',
            'data' => $connectionData,
            'stats' => [
                'total_connections' => $connections->count(),
                'filtered_connections' => $items->count(),
            ],
            'total' => $paginator->total(),
            'limit' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'total_page' => $paginator->lastPage(),
            'last_page' => $paginator->lastPage(),
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'sort' => $sort,
            ],
        ], 200);
    }

    /*connection request and group invitation are merged in this method,
     so the user will get both connection request and group invitation in the same list,
     and the user can accept or ignore the request/invitation from the same list*/

    // public function requests(Request $request): JsonResponse
    // {
    //     $currentUser = $request->user();
    //     $search = trim((string) $request->query('search', ''));
    //     $perPage = $request->integer('limit', $request->integer('per_page', 10));
    //     $perPage = max(1, min($perPage, 100));
    //     $page = max(1, (int) $request->integer('page', 1));

    //     $connectionRequests = Connection::query()
    //         ->pending()
    //         ->where('receiver_id', $currentUser->id)
    //         ->with([
    //             'sender:id,first_name,last_name,title,profile_image',
    //             'receiver:id,first_name,last_name,title,profile_image',
    //         ])
    //         ->get();

    //     $groupInvitations = GroupInvitation::query()
    //         ->where('invited_user_id', $currentUser->id)
    //         ->with([
    //             'group:id,name,type,logo,creator_id',
    //             'inviter:id,first_name,last_name,title,profile_image',
    //         ])
    //         ->get();

    //     $allRequests = collect();

    //     foreach ($connectionRequests as $conn) {
    //         $counterpart = $conn->sender;
    //         $allRequests->push([
    //             'type' => 'connection_request',
    //             'instance' => $conn,
    //             'search_name' => trim(($counterpart->first_name ?? '') . ' ' . ($counterpart->last_name ?? '')),
    //             'search_title' => (string) ($counterpart->title ?? ''),
    //             'created_at' => $conn->created_at,
    //         ]);
    //     }

    //     foreach ($groupInvitations as $invitation) {
    //         $allRequests->push([
    //             'type' => 'group_invitation',
    //             'instance' => $invitation,
    //             'search_name' => (string) ($invitation->group?->name ?? ''),
    //             'search_title' => '',
    //             'created_at' => $invitation->created_at,
    //         ]);
    //     }

    //     if ($search !== '') {
    //         $normalizedSearch = mb_strtolower($search);
    //         $allRequests = $allRequests->filter(function ($item) use ($normalizedSearch) {
    //             return str_contains(mb_strtolower($item['search_name']), $normalizedSearch)
    //                 || str_contains(mb_strtolower($item['search_title']), $normalizedSearch);
    //         });
    //     }

    //     $allRequests = $allRequests->sortByDesc(fn($item) => $item['created_at']->timestamp)->values();

    //     $totalFiltered = $allRequests->count();
    //     $paginatedItems = $allRequests->slice(($page - 1) * $perPage, $perPage)->values();

    //     $senderIds = $paginatedItems->where('type', 'connection_request')
    //         ->map(fn($item) => $item['instance']->sender_id)
    //         ->unique()->values();
    //     $mutualConnections = $this->buildMutualConnectionsMap($currentUser->id, $senderIds);

    //     $data = $paginatedItems->map(function ($item) use ($currentUser, $mutualConnections) {
    //         if ($item['type'] === 'connection_request') {
    //             /** @var Connection $conn */
    //             $conn = $item['instance'];
    //             $formatted = $this->formatConnectionRequest($conn, $currentUser->id, $mutualConnections, true);

    //             $formatted['request_type'] = 'connection_request';
    //             return $formatted;
    //         } else {
    //             /** @var GroupInvitation $invitation */
    //             $invitation = $item['instance'];
    //             $inviterName = trim(($invitation->inviter?->first_name ?? '') . ' ' . ($invitation->inviter?->last_name ?? ''));

    //             return [
    //                 'request_type' => 'group_invitation',
    //                 'invitation_id' => $invitation->id,
    //                 'group' => [
    //                     'id' => $invitation->group?->id,
    //                     'name' => $invitation->group?->name,
    //                     'type' => $invitation->group?->type,
    //                     'logo_url' => $invitation->group?->logo_url,
    //                 ],
    //                 'inviter' => [
    //                     'id' => $invitation->inviter?->id,
    //                     'name' => $inviterName,
    //                     'title' => $invitation->inviter?->title,
    //                     'profile_image_url' => $invitation->inviter?->profile_image_url,
    //                 ],
    //                 'requested_at' => $invitation->created_at?->toDateTimeString(),
    //             ];
    //         }
    //     });

    //     $lastPage = (int) ceil($totalFiltered / $perPage);

    //     return response()->json([
    //         'success' => true,
    //         'message' => $totalFiltered > 0 ? 'Requests retrieved successfully.' : 'No pending requests found.',
    //         'status' => 'success',
    //         'data' => $data,
    //         'stats' => [
    //             'total_requests' => $connectionRequests->count() + $groupInvitations->count(),
    //             'filtered_requests' => $totalFiltered,
    //         ],
    //         'total' => $totalFiltered,
    //         'limit' => $perPage,
    //         'current_page' => $page,
    //         'total_page' => $lastPage,
    //         'last_page' => $lastPage,
    //         'filters' => [
    //             'search' => $search !== '' ? $search : null,
    //         ],
    //     ], 200);
    // }

    public function requests(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $search = trim((string) $request->query('search', ''));

        $perPage = $request->integer('limit', $request->integer('per_page', 10));
        $perPage = max(1, min($perPage, 100));
        $page = max(1, (int) $request->integer('page', 1));

        $requests = Connection::query()
            ->pending()
            ->where('receiver_id', $currentUser->id)
            ->with([
                'sender:id,first_name,last_name,title,profile_image',
                'receiver:id,first_name,last_name,title,profile_image',
            ])
            ->orderByDesc('responded_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        if ($requests->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No pending connection requests found.',
                'status' => 'success',
                'data' => [],
                'stats' => [
                    'total_requests' => 0,
                    'filtered_requests' => 0,
                ],
                'total' => 0,
                'limit' => $perPage,
                'current_page' => $page,
                'total_page' => 0,
                'last_page' => 0,
                'filters' => [
                    'search' => $search !== '' ? $search : null,
                ],
            ], 200);
        }

        $items = $requests->map(function (Connection $connectionRequest) use ($currentUser): array {
            $counterpart = $connectionRequest->sender_id === $currentUser->id
                ? $connectionRequest->receiver
                : $connectionRequest->sender;

            return [
                'request' => $connectionRequest,
                'search_name' => trim(($counterpart->first_name ?? '').' '.($counterpart->last_name ?? '')),
                'search_title' => (string) ($counterpart->title ?? ''),
            ];
        });

        if ($search !== '') {
            $normalizedSearch = mb_strtolower($search);
            $items = $items->filter(function (array $item) use ($normalizedSearch): bool {
                return str_contains(mb_strtolower($item['search_name']), $normalizedSearch)
                    || str_contains(mb_strtolower($item['search_title']), $normalizedSearch);
            });
        }

        $totalFiltered = $items->count();
        $paginatedItems = $items->slice(($page - 1) * $perPage, $perPage)->values();

        $currentPageRequests = $paginatedItems->pluck('request')->values();

        $counterpartIds = $currentPageRequests->map(function (Connection $connectionRequest) use ($currentUser) {
            return $connectionRequest->sender_id === $currentUser->id
                ? $connectionRequest->receiver_id
                : $connectionRequest->sender_id;
        })->unique()->values();

        $mutualConnections = $this->buildMutualConnectionsMap($currentUser->id, $counterpartIds);

        $lastPage = (int) ceil($totalFiltered / $perPage);

        if ($currentPageRequests->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => $search !== '' ? 'No pending connection requests found for this search.' : 'No pending connection requests found.',
                'status' => 'success',
                'data' => [],
                'stats' => [
                    'total_requests' => $requests->count(),
                    'filtered_requests' => $totalFiltered,
                ],
                'total' => $totalFiltered,
                'limit' => $perPage,
                'current_page' => $page,
                'total_page' => $lastPage,
                'last_page' => $lastPage,
                'filters' => [
                    'search' => $search !== '' ? $search : null,
                ],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Connection requests retrieved successfully.',
            'status' => 'success',
            'data' => $currentPageRequests
                ->map(function (Connection $connectionRequest) use ($currentUser, $mutualConnections) {
                    return $this->formatConnectionRequest($connectionRequest, $currentUser->id, $mutualConnections, true);
                })
                ->values(),
            'stats' => [
                'total_requests' => $requests->count(),
                'filtered_requests' => $totalFiltered,
            ],
            'total' => $totalFiltered,
            'limit' => $perPage,
            'current_page' => $page,
            'total_page' => $lastPage,
            'last_page' => $lastPage,
            'filters' => [
                'search' => $search !== '' ? $search : null,
            ],
        ], 200);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $search = trim((string) $request->query('search', ''));
        $perPage = max(1, min((int) $request->integer('per_page', 12), 50));

        $excludedUserIds = Connection::query()
            ->where(function ($query) {
                $query->where(function ($q) {
                    $q->accepted();
                })
                    ->orWhere(function ($q) {
                        $q->pending();
                    });
            })
            ->where(function ($query) use ($currentUser) {
                $query->where('sender_id', $currentUser->id)
                    ->orWhere('receiver_id', $currentUser->id);
            })
            ->get(['sender_id', 'receiver_id'])
            ->map(function (Connection $connectionRequest) use ($currentUser) {
                return $connectionRequest->sender_id === $currentUser->id
                    ? $connectionRequest->receiver_id
                    : $connectionRequest->sender_id;
            })
            ->unique()
            ->values();

        $suggestionsQuery = User::query()
            ->whereKeyNot($currentUser->id)
            ->whereHas('profile')
            ->when($excludedUserIds->isNotEmpty(), function ($query) use ($excludedUserIds) {
                $query->whereNotIn('id', $excludedUserIds->all());
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('title', 'like', '%'.$search.'%');
                });
            })
            ->select(['id', 'first_name', 'last_name', 'title', 'profile_image', 'cover_image'])
            ->latest('id');

        $paginator = $suggestionsQuery->paginate($perPage);

        $candidateIds = $paginator->getCollection()->pluck('id')->values();

        $mutualConnections = $this->buildMutualConnectionsMap($currentUser->id, $candidateIds);

        $data = $paginator->getCollection()->map(function (User $candidate) use ($mutualConnections) {
            $mutualConnectionData = $mutualConnections[$candidate->id] ?? ['count' => 0, 'preview' => []];

            return [
                'user' => $this->formatUser($candidate),
                'state' => 'not_connected',
                'action_label' => 'Connect',
                'pending_request_id' => null,
                'is_connectable' => true,
                'mutual_connections_count' => $mutualConnectionData['count'],
                'mutual_connections' => $mutualConnectionData['preview'],
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Connection suggestions retrieved successfully.',
            'status' => 'success',
            'data' => $data,
            'stats' => [
                'total_suggestions' => $paginator->total(),
            ],
            'total' => $paginator->total(),
            'limit' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'total_page' => $paginator->lastPage(),
            'last_page' => $paginator->lastPage(),
            'filters' => [
                'search' => $search !== '' ? $search : null,
            ],
        ], 200);
    }

    public function sentPendingRequests(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $search = trim((string) $request->query('search', ''));
        $perPage = $request->integer('limit', $request->integer('per_page', 10));
        $perPage = max(1, min($perPage, 100));
        // $perPage = max(1, min((int) $request->integer('per_page', 12), 50));

        $pendingQuery = Connection::query()
            ->pending()
            ->where('sender_id', $currentUser->id)
            ->whereHas('receiver', function ($query) use ($search) {

                $query->whereHas('profile');
                if ($search !== '') {
                    $query->where(function ($q) use ($search) {
                        $q->where('first_name', 'like', '%'.$search.'%')
                            ->orWhere('last_name', 'like', '%'.$search.'%')
                            ->orWhere('title', 'like', '%'.$search.'%');
                    });
                }
            })
            ->with(['receiver']) //
            ->latest('id');

        $paginator = $pendingQuery->paginate($perPage);

        $candidateIds = $paginator->getCollection()->pluck('receiver_id')->values();
        $mutualConnections = $this->buildMutualConnectionsMap($currentUser->id, $candidateIds);

        $data = $paginator->getCollection()->map(function (Connection $connection) use ($mutualConnections) {
            $candidate = $connection->receiver;
            $mutualConnectionData = $mutualConnections[$candidate->id] ?? ['count' => 0, 'preview' => []];

            return [
                'user' => $this->formatUser($candidate),
                'state' => 'pending_sent',
                'action_label' => 'Pending',
                'pending_request_id' => $connection->id,
                'is_connectable' => false,
                'mutual_connections_count' => $mutualConnectionData['count'],
                'mutual_connections' => $mutualConnectionData['preview'],
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Sent pending requests retrieved successfully.',
            'status' => 'success',
            'data' => $data,
            'stats' => [
                'total_pending' => $paginator->total(),
            ],
            'total' => $paginator->total(),
            'limit' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'total_page' => $paginator->lastPage(),
            'last_page' => $paginator->lastPage(),
            'filters' => [
                'search' => $search !== '' ? $search : null,
            ],
        ], 200);
    }

    // public function suggestions(Request $request): JsonResponse
    // {
    //     $currentUser = $request->user();
    //     $search = trim((string) $request->query('search', ''));
    //     $perPage = max(1, min((int) $request->integer('per_page', 12), 50));

    //     $acceptedCounterpartIds = Connection::query()
    //         ->accepted()
    //         ->where(function ($query) use ($currentUser) {
    //             $query->where('sender_id', $currentUser->id)
    //                 ->orWhere('receiver_id', $currentUser->id);
    //         })
    //         ->get(['sender_id', 'receiver_id'])
    //         ->map(function (Connection $connectionRequest) use ($currentUser) {
    //             return $connectionRequest->sender_id === $currentUser->id
    //                 ? $connectionRequest->receiver_id
    //                 : $connectionRequest->sender_id;
    //         })
    //         ->unique()
    //         ->values();

    //     $suggestionsQuery = User::query()
    //         ->whereKeyNot($currentUser->id)
    //         ->whereHas('profile')
    //         ->when($acceptedCounterpartIds->isNotEmpty(), function ($query) use ($acceptedCounterpartIds) {
    //             $query->whereNotIn('id', $acceptedCounterpartIds->all());
    //         })
    //         ->when($search !== '', function ($query) use ($search) {
    //             $query->where(function ($query) use ($search) {
    //                 $query->where('first_name', 'like', '%' . $search . '%')
    //                     ->orWhere('last_name', 'like', '%' . $search . '%')
    //                     ->orWhere('title', 'like', '%' . $search . '%');
    //             });
    //         })
    //         ->select(['id', 'first_name', 'last_name', 'title', 'profile_image', 'cover_image'])
    //         ->latest('id');

    //     $paginator = $suggestionsQuery->paginate($perPage);

    //     $candidateIds = $paginator->getCollection()->pluck('id')->values();

    //     $outgoingPendingByUserId = Connection::query()
    //         ->pending()
    //         ->where('sender_id', $currentUser->id)
    //         ->whereIn('receiver_id', $candidateIds)
    //         ->get(['id', 'receiver_id'])
    //         ->keyBy('receiver_id');

    //     $incomingPendingByUserId = Connection::query()
    //         ->pending()
    //         ->where('receiver_id', $currentUser->id)
    //         ->whereIn('sender_id', $candidateIds)
    //         ->get(['id', 'sender_id'])
    //         ->keyBy('sender_id');

    //     $mutualConnections = $this->buildMutualConnectionsMap($currentUser->id, $candidateIds);

    //     $data = $paginator->getCollection()->map(function (User $candidate) use ($outgoingPendingByUserId, $incomingPendingByUserId, $mutualConnections) {
    //         $outgoingPendingRequest = $outgoingPendingByUserId->get($candidate->id);
    //         $incomingPendingRequest = $incomingPendingByUserId->get($candidate->id);

    //         $state = 'not_connected';
    //         $actionLabel = 'Connect';
    //         $pendingRequestId = null;

    //         if ($outgoingPendingRequest) {
    //             $state = 'pending_sent';
    //             $actionLabel = 'Pending';
    //             $pendingRequestId = $outgoingPendingRequest->id;
    //         } elseif ($incomingPendingRequest) {
    //             $state = 'pending_received';
    //             $actionLabel = 'Accept';
    //             $pendingRequestId = $incomingPendingRequest->id;
    //         }

    //         $mutualConnectionData = $mutualConnections[$candidate->id] ?? ['count' => 0, 'preview' => []];

    //         return [
    //             'user' => $this->formatUser($candidate),
    //             'state' => $state,
    //             'action_label' => $actionLabel,
    //             'pending_request_id' => $pendingRequestId,
    //             'is_connectable' => $state === 'not_connected',
    //             'mutual_connections_count' => $mutualConnectionData['count'],
    //             'mutual_connections' => $mutualConnectionData['preview'],
    //         ];
    //     })->values();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Connection suggestions retrieved successfully.',
    //         'status' => 'success',
    //         'data' => $data,
    //         'stats' => [
    //             'total_suggestions' => $paginator->total(),
    //         ],
    //         'total' => $paginator->total(),
    //         'limit' => $paginator->perPage(),
    //         'current_page' => $paginator->currentPage(),
    //         'total_page' => $paginator->lastPage(),
    //         'last_page' => $paginator->lastPage(),
    //         'filters' => [
    //             'search' => $search !== '' ? $search : null,
    //         ],
    //     ], 200);
    // }

    public function sendRequest(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $currentUser = $request->user();

        if ((int) $validated['user_id'] === $currentUser->id) {
            throw ValidationException::withMessages([
                'user_id' => ['You cannot send a connection request to yourself.'],
            ]);
        }

        $targetUser = User::query()->findOrFail($validated['user_id']);

        $result = DB::transaction(function () use ($currentUser, $targetUser) {
            $alreadyConnected = Connection::query()
                ->accepted()
                ->where(function ($query) use ($currentUser, $targetUser) {
                    $query->where('sender_id', $currentUser->id)
                        ->where('receiver_id', $targetUser->id)
                        ->orWhere(function ($query) use ($currentUser, $targetUser) {
                            $query->where('sender_id', $targetUser->id)
                                ->where('receiver_id', $currentUser->id);
                        });
                })
                ->first();

            if ($alreadyConnected) {
                return [
                    'status' => 'error',
                    'code' => 409,
                    'message' => 'You are already connected with this user.',
                    'connection' => $alreadyConnected,
                ];
            }

            $reversePendingRequest = Connection::query()
                ->pending()
                ->where('sender_id', $targetUser->id)
                ->where('receiver_id', $currentUser->id)
                ->first();

            if ($reversePendingRequest) {
                $reversePendingRequest->update([
                    'status' => Connection::STATUS_ACCEPTED,
                    'responded_at' => now(),
                ]);

                $this->ensureMutualFollows($currentUser, $targetUser);
                Conversation::betweenUsers($currentUser->id, $targetUser->id);

                return [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Connection request accepted successfully.',
                    'connection' => $reversePendingRequest->fresh(),
                ];
            }

            $connectionRequest = Connection::query()->updateOrCreate(
                [
                    'sender_id' => $currentUser->id,
                    'receiver_id' => $targetUser->id,
                ],
                [
                    'status' => Connection::STATUS_PENDING,
                    'responded_at' => null,
                ]
            );

            return [
                'status' => 'success',
                'code' => 201,
                'message' => 'Connection request sent successfully.',
                'connection' => $connectionRequest->fresh(),
            ];
        });

        if ($result['status'] === 'error') {
            return response()->json([
                'status' => 'error',
                'message' => $result['message'],
            ], $result['code']);
        }

        if ((int) $result['code'] === 201 && $result['connection']->receiver_id === $targetUser->id) {
            // Notify the target user about the new connection request
            $targetUser->notify(new ConnectionRequestReceivedNotification($result['connection'], $currentUser));

            // Optionally, send an email notification for the connection request
            Mail::to($targetUser->email)->queue(new ConnectionRequestMail($currentUser, $targetUser, $result['connection']));
        }

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
            'data' => $this->formatConnectionRequest($result['connection']->loadMissing(['sender', 'receiver']), $currentUser->id, []),
        ], $result['code']);
    }

    public function accept(Request $request, Connection $connectionRequest): JsonResponse
    {
        $currentUser = $request->user();

        if (! $this->receiverCanManage($connectionRequest, $currentUser->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only the request receiver can accept this connection request.',
            ], 403);
        }

        if ($connectionRequest->status === Connection::STATUS_ACCEPTED) {
            return response()->json([
                'status' => 'success',
                'message' => 'Connection request already accepted.',
                'data' => $this->formatConnectionRequest($connectionRequest->loadMissing(['sender', 'receiver']), $currentUser->id, []),
            ], 200);
        }

        if ($connectionRequest->status !== Connection::STATUS_PENDING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending connection requests can be accepted.',
            ], 422);
        }

        $acceptedConnectionRequest = DB::transaction(function () use ($connectionRequest) {
            $connectionRequest->update([
                'status' => Connection::STATUS_ACCEPTED,
                'responded_at' => now(),
            ]);

            $this->ensureMutualFollows($connectionRequest->sender, $connectionRequest->receiver);
            Conversation::betweenUsers($connectionRequest->sender_id, $connectionRequest->receiver_id);

            return $connectionRequest->fresh()->loadMissing(['sender', 'receiver']);
        });

        $acceptedConnectionRequest->sender->notify(new ConnectionRequestAcceptedNotification($acceptedConnectionRequest, $currentUser));

        return response()->json([
            'status' => 'success',
            'message' => 'Connection request accepted successfully.',
            'data' => $this->formatConnectionRequest($acceptedConnectionRequest, $currentUser->id, []),
        ], 200);
    }

    public function ignore(Request $request, Connection $connectionRequest): JsonResponse
    {
        $currentUser = $request->user();

        if (! $this->receiverCanManage($connectionRequest, $currentUser->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only the request receiver can ignore this connection request.',
            ], 403);
        }

        if ($connectionRequest->status !== Connection::STATUS_PENDING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending connection requests can be ignored.',
            ], 422);
        }

        $connectionRequest->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Connection request ignored successfully.',
            'data' => [
                'message' => 'The user can now send you a new connection request if they wish.',
            ],
        ], 200);
    }

    public function removeConnection(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            throw ValidationException::withMessages([
                'user_id' => ['You cannot remove connection with yourself.'],
            ]);
        }

        DB::transaction(function () use ($currentUser, $user) {
            Connection::query()
                ->where(function ($query) use ($currentUser, $user) {
                    $query->where('sender_id', $currentUser->id)
                        ->where('receiver_id', $user->id)
                        ->orWhere(function ($query) use ($currentUser, $user) {
                            $query->where('sender_id', $user->id)
                                ->where('receiver_id', $currentUser->id);
                        });
                })
                ->delete();

            UserFollow::query()
                ->where(function ($query) use ($currentUser, $user) {
                    $query->where('follower_id', $currentUser->id)
                        ->where('following_id', $user->id)
                        ->orWhere(function ($query) use ($currentUser, $user) {
                            $query->where('follower_id', $user->id)
                                ->where('following_id', $currentUser->id);
                        });
                })
                ->delete();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Connection removed successfully.',
            'data' => [
                'user' => $this->formatUser($user),
                'is_connected' => false,
                'is_following' => false,
            ],
        ], 200);
    }

    private function receiverCanManage(Connection $connectionRequest, int $userId): bool
    {
        return $connectionRequest->receiver_id === $userId;
    }

    private function ensureMutualFollows(User $firstUser, User $secondUser): void
    {
        UserFollow::query()->firstOrCreate([
            'follower_id' => $firstUser->id,
            'following_id' => $secondUser->id,
        ]);

        UserFollow::query()->firstOrCreate([
            'follower_id' => $secondUser->id,
            'following_id' => $firstUser->id,
        ]);
    }

    private function buildMutualConnectionsMap(int $currentUserId, Collection $counterpartIds): array
    {
        if ($counterpartIds->isEmpty()) {
            return [];
        }

        $currentConnections = Connection::query()
            ->accepted()
            ->where(function ($query) use ($currentUserId) {
                $query->where('sender_id', $currentUserId)
                    ->orWhere('receiver_id', $currentUserId);
            })
            ->get(['sender_id', 'receiver_id'])
            ->map(function (Connection $connectionRequest) use ($currentUserId) {
                return $connectionRequest->sender_id === $currentUserId
                    ? $connectionRequest->receiver_id
                    : $connectionRequest->sender_id;
            })
            ->unique()
            ->values();

        if ($currentConnections->isEmpty()) {
            return [];
        }

        $adjacency = [];
        foreach ($currentConnections as $connectedUserId) {
            $adjacency[$connectedUserId] = [];
        }

        $relatedConnections = Connection::query()
            ->accepted()
            ->where(function ($query) use ($currentConnections) {
                $query->whereIn('sender_id', $currentConnections->all())
                    ->orWhereIn('receiver_id', $currentConnections->all());
            })
            ->get(['sender_id', 'receiver_id']);

        foreach ($relatedConnections as $connectionRequest) {
            $adjacency[$connectionRequest->sender_id][] = $connectionRequest->receiver_id;
            $adjacency[$connectionRequest->receiver_id][] = $connectionRequest->sender_id;
        }

        $mutualUserIds = collect();
        $map = [];

        foreach ($counterpartIds as $counterpartId) {
            $mutualIds = collect($adjacency[$counterpartId] ?? [])
                ->intersect($currentConnections)
                ->reject(function (int $userId) use ($currentUserId, $counterpartId) {
                    return $userId === $currentUserId || $userId === $counterpartId;
                })
                ->unique()
                ->values();

            $map[$counterpartId] = $mutualIds;
            $mutualUserIds = $mutualUserIds->merge($mutualIds);
        }

        $mutualUsers = User::query()
            ->whereIn('id', $mutualUserIds->unique()->values())
            ->get(['id', 'first_name', 'last_name', 'title', 'profile_image'])
            ->keyBy('id');

        $formatted = [];

        foreach ($map as $counterpartId => $mutualIds) {
            $preview = $mutualIds
                ->map(function (int $userId) use ($mutualUsers) {
                    return $mutualUsers->get($userId);
                })
                ->filter()
                ->map(function (User $user) {
                    return $this->formatUser($user);
                })
                ->take(1)
                ->values();

            $formatted[$counterpartId] = [
                'count' => $mutualIds->count(),
                'preview' => $preview,
            ];
        }

        return $formatted;
    }

    private function formatConnectionRequest(Connection $connectionRequest, int $currentUserId, array $mutualConnections, bool $includeHistoryDetails = false): array
    {
        $counterpart = $connectionRequest->sender_id === $currentUserId
            ? $connectionRequest->receiver
            : $connectionRequest->sender;

        $counterpartData = $this->formatUser($counterpart);
        $mutualConnectionData = $mutualConnections[$counterpart->id] ?? ['count' => 0, 'preview' => []];

        $payload = [
            'id' => $connectionRequest->id,
            'status' => $connectionRequest->status,
            'status_label' => ucfirst($connectionRequest->status),
            'is_incoming' => $connectionRequest->receiver_id === $currentUserId,
            'is_outgoing' => $connectionRequest->sender_id === $currentUserId,
            'can_accept' => false,
            'can_ignore' => false,
            'connected_since' => $connectionRequest->status === Connection::STATUS_ACCEPTED && $connectionRequest->responded_at
                ? $connectionRequest->responded_at->format('d M, Y')
                : null,
            'user' => $counterpartData,
            'mutual_connections_count' => $mutualConnectionData['count'],
            'mutual_connections' => $mutualConnectionData['preview'],
        ];

        if ($includeHistoryDetails) {
            $payload['message'] = $this->requestMessage($connectionRequest, $currentUserId, $counterpartData['name']);
            $payload['direction'] = $connectionRequest->receiver_id === $currentUserId ? 'incoming' : 'outgoing';
            $payload['requested_at'] = $connectionRequest->created_at?->toDateTimeString();
            $payload['responded_at'] = $connectionRequest->responded_at?->toDateTimeString();
            $payload['can_accept'] = $connectionRequest->status === Connection::STATUS_PENDING && $connectionRequest->receiver_id === $currentUserId;
            $payload['can_ignore'] = $connectionRequest->status === Connection::STATUS_PENDING && $connectionRequest->receiver_id === $currentUserId;
        }

        return $payload;
    }

    private function requestMessage(Connection $connectionRequest, int $currentUserId, string $counterpartName): string
    {
        if ($connectionRequest->status === Connection::STATUS_ACCEPTED) {
            return 'You both are now connected';
        }

        if ($connectionRequest->status === Connection::STATUS_IGNORED) {
            return $connectionRequest->receiver_id === $currentUserId
                ? 'You ignored '.$counterpartName.'\'s invitation'
                : $counterpartName.' ignored your invitation';
        }

        return $connectionRequest->sender_id === $currentUserId
            ? 'Connection request sent'
            : $counterpartName.' sent you a connection request';
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => trim(($user->first_name ?? '').' '.($user->last_name ?? '')),
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'title' => $user->title,
            'profile_image' => $user->profile_image,
            'profile_image_url' => $user->profile_image_url,
            'cover_image' => $user->cover_image,
            'cover_image_url' => $user->cover_image_url,
        ];
    }
}
