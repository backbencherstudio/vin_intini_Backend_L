<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ConnectionRequest;
use App\Models\User;
use App\Models\UserFollow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserConnectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        $connections = ConnectionRequest::query()
            ->accepted()
            ->forUser($currentUser->id)
            ->with([
                'sender:id,first_name,last_name,title,profile_image',
                'receiver:id,first_name,last_name,title,profile_image',
            ])
            ->orderByDesc('responded_at')
            ->orderByDesc('id')
            ->get();

        $counterpartIds = $connections
            ->map(function (ConnectionRequest $connectionRequest) use ($currentUser) {
                return $connectionRequest->sender_id === $currentUser->id
                    ? $connectionRequest->receiver_id
                    : $connectionRequest->sender_id;
            })
            ->unique()
            ->values();

        $mutualConnections = $this->buildMutualConnectionsMap($currentUser->id, $counterpartIds);

        return response()->json([
            'status' => 'success',
            'data' => $connections
                ->map(function (ConnectionRequest $connectionRequest) use ($currentUser, $mutualConnections) {
                    return $this->formatConnectionRequest($connectionRequest, $currentUser->id, $mutualConnections);
                })
                ->values(),
        ], 200);
    }

    public function requests(Request $request): JsonResponse
    {
        $currentUser = $request->user();

        $requests = ConnectionRequest::query()
            ->forUser($currentUser->id)
            ->with([
                'sender:id,first_name,last_name,title,profile_image',
                'receiver:id,first_name,last_name,title,profile_image',
            ])
            ->orderByDesc('responded_at')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $counterpartIds = $requests
            ->map(function (ConnectionRequest $connectionRequest) use ($currentUser) {
                return $connectionRequest->sender_id === $currentUser->id
                    ? $connectionRequest->receiver_id
                    : $connectionRequest->sender_id;
            })
            ->unique()
            ->values();

        $mutualConnections = $this->buildMutualConnectionsMap($currentUser->id, $counterpartIds);

        return response()->json([
            'status' => 'success',
            'data' => $requests
                ->map(function (ConnectionRequest $connectionRequest) use ($currentUser, $mutualConnections) {
                    return $this->formatConnectionRequest($connectionRequest, $currentUser->id, $mutualConnections, true);
                })
                ->values(),
        ], 200);
    }

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
            $alreadyConnected = ConnectionRequest::query()
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

            $reversePendingRequest = ConnectionRequest::query()
                ->pending()
                ->where('sender_id', $targetUser->id)
                ->where('receiver_id', $currentUser->id)
                ->first();

            if ($reversePendingRequest) {
                $reversePendingRequest->update([
                    'status' => ConnectionRequest::STATUS_ACCEPTED,
                    'responded_at' => now(),
                ]);

                $this->ensureMutualFollows($currentUser, $targetUser);

                return [
                    'status' => 'success',
                    'code' => 200,
                    'message' => 'Connection request accepted successfully.',
                    'connection' => $reversePendingRequest->fresh(),
                ];
            }

            $connectionRequest = ConnectionRequest::query()->updateOrCreate(
                [
                    'sender_id' => $currentUser->id,
                    'receiver_id' => $targetUser->id,
                ],
                [
                    'status' => ConnectionRequest::STATUS_PENDING,
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

        return response()->json([
            'status' => 'success',
            'message' => $result['message'],
            'data' => $this->formatConnectionRequest($result['connection']->loadMissing(['sender', 'receiver']), $currentUser->id, []),
        ], $result['code']);
    }

    public function accept(Request $request, ConnectionRequest $connectionRequest): JsonResponse
    {
        $currentUser = $request->user();

        $this->ensureReceiverCanAct($connectionRequest, $currentUser->id);

        if ($connectionRequest->status === ConnectionRequest::STATUS_ACCEPTED) {
            return response()->json([
                'status' => 'success',
                'message' => 'Connection request already accepted.',
                'data' => $this->formatConnectionRequest($connectionRequest->loadMissing(['sender', 'receiver']), $currentUser->id, []),
            ], 200);
        }

        if ($connectionRequest->status !== ConnectionRequest::STATUS_PENDING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending connection requests can be accepted.',
            ], 422);
        }

        DB::transaction(function () use ($connectionRequest) {
            $connectionRequest->update([
                'status' => ConnectionRequest::STATUS_ACCEPTED,
                'responded_at' => now(),
            ]);

            $this->ensureMutualFollows($connectionRequest->sender, $connectionRequest->receiver);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Connection request accepted successfully.',
            'data' => $this->formatConnectionRequest($connectionRequest->fresh()->loadMissing(['sender', 'receiver']), $currentUser->id, []),
        ], 200);
    }

    public function ignore(Request $request, ConnectionRequest $connectionRequest): JsonResponse
    {
        $currentUser = $request->user();

        $this->ensureReceiverCanAct($connectionRequest, $currentUser->id);

        if ($connectionRequest->status === ConnectionRequest::STATUS_IGNORED) {
            return response()->json([
                'status' => 'success',
                'message' => 'Connection request already ignored.',
                'data' => $this->formatConnectionRequest($connectionRequest->loadMissing(['sender', 'receiver']), $currentUser->id, []),
            ], 200);
        }

        if ($connectionRequest->status !== ConnectionRequest::STATUS_PENDING) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only pending connection requests can be ignored.',
            ], 422);
        }

        $connectionRequest->update([
            'status' => ConnectionRequest::STATUS_IGNORED,
            'responded_at' => now(),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Connection request ignored successfully.',
            'data' => $this->formatConnectionRequest($connectionRequest->fresh()->loadMissing(['sender', 'receiver']), $currentUser->id, []),
        ], 200);
    }

    public function followers(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $followingIds = UserFollow::query()
            ->where('follower_id', $currentUser->id)
            ->pluck('following_id')
            ->all();

        $followers = UserFollow::query()
            ->where('following_id', $currentUser->id)
            ->with('follower:id,first_name,last_name,title,profile_image')
            ->latest('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $followers->map(function (UserFollow $follow) use ($followingIds) {
                return [
                    'id' => $follow->id,
                    'user' => $this->formatUser($follow->follower),
                    'is_following_back' => in_array($follow->follower_id, $followingIds, true),
                    'followed_at' => optional($follow->created_at)?->toDateTimeString(),
                ];
            })->values(),
        ], 200);
    }

    public function following(Request $request): JsonResponse
    {
        $currentUser = $request->user();
        $followerIds = UserFollow::query()
            ->where('following_id', $currentUser->id)
            ->pluck('follower_id')
            ->all();

        $following = UserFollow::query()
            ->where('follower_id', $currentUser->id)
            ->with('following:id,first_name,last_name,title,profile_image')
            ->latest('id')
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $following->map(function (UserFollow $follow) use ($followerIds) {
                return [
                    'id' => $follow->id,
                    'user' => $this->formatUser($follow->following),
                    'is_followed_back' => in_array($follow->following_id, $followerIds, true),
                    'followed_at' => optional($follow->created_at)?->toDateTimeString(),
                ];
            })->values(),
        ], 200);
    }

    public function unfollow(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        UserFollow::query()
            ->where('follower_id', $currentUser->id)
            ->where('following_id', $user->id)
            ->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Unfollowed successfully.',
            'data' => [
                'user' => $this->formatUser($user),
                'is_following' => false,
            ],
        ], 200);
    }

    public function follow(Request $request, User $user): JsonResponse
    {
        $currentUser = $request->user();

        if ($currentUser->id === $user->id) {
            throw ValidationException::withMessages([
                'user_id' => ['You cannot follow yourself.'],
            ]);
        }

        $follow = UserFollow::query()->firstOrCreate([
            'follower_id' => $currentUser->id,
            'following_id' => $user->id,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => $follow->wasRecentlyCreated
                ? 'Followed successfully.'
                : 'You are already following this user.',
            'data' => [
                'user' => $this->formatUser($user),
                'is_following' => true,
            ],
        ], $follow->wasRecentlyCreated ? 201 : 200);
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
            ConnectionRequest::query()
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

    private function ensureReceiverCanAct(ConnectionRequest $connectionRequest, int $userId): void
    {
        if ($connectionRequest->receiver_id !== $userId) {
            abort(403, 'You are not allowed to manage this connection request.');
        }
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

        $currentConnections = ConnectionRequest::query()
            ->accepted()
            ->where(function ($query) use ($currentUserId) {
                $query->where('sender_id', $currentUserId)
                    ->orWhere('receiver_id', $currentUserId);
            })
            ->get(['sender_id', 'receiver_id'])
            ->map(function (ConnectionRequest $connectionRequest) use ($currentUserId) {
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

        $relatedConnections = ConnectionRequest::query()
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

    private function formatConnectionRequest(ConnectionRequest $connectionRequest, int $currentUserId, array $mutualConnections, bool $includeHistoryDetails = false): array
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
            'connected_since' => $connectionRequest->status === ConnectionRequest::STATUS_ACCEPTED && $connectionRequest->responded_at
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
            $payload['can_accept'] = $connectionRequest->status === ConnectionRequest::STATUS_PENDING && $connectionRequest->receiver_id === $currentUserId;
            $payload['can_ignore'] = $connectionRequest->status === ConnectionRequest::STATUS_PENDING && $connectionRequest->receiver_id === $currentUserId;
        }

        return $payload;
    }

    private function requestMessage(ConnectionRequest $connectionRequest, int $currentUserId, string $counterpartName): string
    {
        if ($connectionRequest->status === ConnectionRequest::STATUS_ACCEPTED) {
            return 'You both are now connected';
        }

        if ($connectionRequest->status === ConnectionRequest::STATUS_IGNORED) {
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
        ];
    }
}
