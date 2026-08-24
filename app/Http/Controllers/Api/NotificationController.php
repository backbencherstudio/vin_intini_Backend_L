<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('limit', $request->integer('per_page', 20)), 50));
        $page = max(1, (int) $request->integer('current_page', $request->integer('page', 1)));
        $unreadOnly = (bool) $request->boolean('unread_only', false);
        $search = trim((string) $request->query('search', ''));

        $query = $request->user()->notifications();

        if ($unreadOnly) {
            $query = $query->whereNull('read_at');
        }

        if ($search !== '') {
            $query = $query->where(function ($notificationQuery) use ($search) {
                $notificationQuery
                    ->where('type', 'like', '%'.$search.'%')
                    ->orWhere('data', 'like', '%'.$search.'%');
            });
        }

        $notifications = $query->orderByDesc('created_at')->paginate($perPage, page: $page);

        $formattedNotifications = collect($notifications->items())
            ->map(fn($n) => $this->formatNotification($n))
            ->filter()
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully',
            'stats' => [
                'total_notifications' => $notifications->total(),
                'unread_notifications' => $request->user()->unreadNotifications()->count(),
            ],
            'data' => $formattedNotifications,
            'total' => $notifications->total(),
            'limit' => $notifications->perPage(),
            'current_page' => $notifications->currentPage(),
            'total_page' => $notifications->lastPage(),
            'last_page' => $notifications->lastPage(),
            'filters' => [
                'unread_only' => $unreadOnly,
                'search' => $search !== '' ? $search : null,
            ],
        ], 200);
    }

    public function unreadCount(Request $request): JsonResponse
    {
        $count = $request->user()->unreadNotifications()->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'unread_count' => $count,
            ],
        ], 200);
    }

    public function markAsRead(Request $request, string $notificationId): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notificationId);

        if ($notification->read_at === null) {
            $notification->markAsRead();
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read.',
            'data' => $this->formatNotification($notification),
        ], 200);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $unread = $request->user()->unreadNotifications();
        $count = $unread->count();
        $unread->update(['read_at' => now()]);

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications marked as read.',
            'data' => [
                'updated_count' => $count,
            ],
        ], 200);
    }

    public function delete(Request $request, string $notificationId): JsonResponse
    {
        $notification = $request->user()->notifications()->findOrFail($notificationId);
        $notification->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Notification deleted successfully.',
        ], 200);
    }

    public function deleteAll(Request $request): JsonResponse
    {
        $count = $request->user()->notifications()->count();
        $request->user()->notifications()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications deleted successfully.',
            'data' => [
                'deleted_count' => $count,
            ],
        ], 200);
    }

    private function formatNotification(Model $notification): ?array
    {
        $data = $notification->data;

        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        $userId = $data['sender_id'] ?? $data['user_id'] ?? $data['acceptor_id'] ?? $data['inviter_id'] ?? null;

        if ($userId) {
            $user = User::find($userId);

            if (!$user) {
                return null;
            }

            $data['sender_username'] = $user->username;
            $data['username'] = $user->username;
            $data['acceptor_username'] = $user->username;
            $data['inviter_username'] = $user->username;
            $data['sender_name'] = $user->first_name . ' ' . $user->last_name;
            $data['profile_image_url'] = $user->profile_image_url;
        }

        return [
            'id' => $notification->id,
            'type' => $data['type'] ?? class_basename($notification->type),
            'data' => $data,
            'read_at' => $notification->read_at?->toIso8601String(),
            'is_read' => $notification->read_at !== null,
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }



    // public function index(Request $request): JsonResponse
    // {
    //     $perPage = max(1, min((int) $request->integer('limit', $request->integer('per_page', 20)), 50));
    //     $page = max(1, (int) $request->integer('current_page', $request->integer('page', 1)));
    //     $unreadOnly = (bool) $request->boolean('unread_only', false);
    //     $search = trim((string) $request->query('search', ''));

    //     $query = $request->user()->notifications();

    //     if ($unreadOnly) {
    //         $query = $query->whereNull('read_at');
    //     }

    //     if ($search !== '') {
    //         $query = $query->where(function ($notificationQuery) use ($search) {
    //             $notificationQuery
    //                 ->where('type', 'like', '%' . $search . '%')
    //                 ->orWhere('data', 'like', '%' . $search . '%');
    //         });
    //     }

    //     $statsQuery = clone $query;

    //     $notifications = $query->orderByDesc('created_at')->paginate($perPage, page: $page);

    //     $totalNotifications = $statsQuery->count();
    //     $unreadNotifications = (clone $statsQuery)->whereNull('read_at')->count();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Notifications retrieved successfully',
    //         'stats' => [
    //             'total_notifications' => $totalNotifications,
    //             'unread_notifications' => $unreadNotifications,
    //         ],
    //         'data' => $notifications->getCollection()->map(fn($notification) => $this->formatNotification($notification))->values(),
    //         'total' => $notifications->total(),
    //         'limit' => $notifications->perPage(),
    //         'current_page' => $notifications->currentPage(),
    //         'total_page' => $notifications->lastPage(),
    //         'last_page' => $notifications->lastPage(),
    //         'filters' => [
    //             'unread_only' => $unreadOnly,
    //             'search' => $search !== '' ? $search : null,
    //         ],
    //     ], 200);
    // }

    // public function unreadCount(Request $request): JsonResponse
    // {
    //     $count = $request->user()->notifications()->whereNull('read_at')->count();

    //     return response()->json([
    //         'status' => 'success',
    //         'data' => [
    //             'unread_count' => $count,
    //         ],
    //     ], 200);
    // }

    // public function markAsRead(Request $request, string $notificationId): JsonResponse
    // {
    //     $notification = $request->user()->notifications()->findOrFail($notificationId);

    //     if ($notification->read_at === null) {
    //         $notification->update(['read_at' => now()]);
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Notification marked as read.',
    //         'data' => $this->formatNotification($notification),
    //     ], 200);
    // }

    // public function markAllAsRead(Request $request): JsonResponse
    // {
    //     $count = $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'All notifications marked as read.',
    //         'data' => [
    //             'updated_count' => $count,
    //         ],
    //     ], 200);
    // }

    // public function delete(Request $request, string $notificationId): JsonResponse
    // {
    //     $notification = $request->user()->notifications()->findOrFail($notificationId);
    //     $notification->delete();

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'Notification deleted successfully.',
    //     ], 200);
    // }

    // public function deleteAll(Request $request): JsonResponse
    // {
    //     $count = $request->user()->notifications()->delete();

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => 'All notifications deleted successfully.',
    //         'data' => [
    //             'deleted_count' => $count,
    //         ],
    //     ], 200);
    // }

    // private function formatNotification(Model $notification): array
    // {
    //     $data = $notification->data;

    //     if (is_string($data)) {
    //         $data = json_decode($data, true);
    //     }

    //     $usernameKeys = ['sender_username', 'username', 'acceptor_username', 'inviter_username'];
    //     $hasUsername = false;

    //     foreach ($usernameKeys as $key) {
    //         if (isset($data[$key])) {
    //             $hasUsername = true;
    //             break;
    //         }
    //     }

    //     if (!$hasUsername) {
    //         $userId = $data['sender_id'] ?? $data['user_id'] ?? $data['acceptor_id'] ?? $data['inviter_id'] ?? null;

    //         if ($userId) {
    //             $user = \App\Models\User::find($userId);
    //             if ($user) {
    //                 $data['sender_username'] = $user->username;
    //                 $data['username'] = $user->username;
    //                 $data['acceptor_username'] = $user->username;
    //                 $data['inviter_username'] = $user->username;
    //             }
    //         }
    //     }

    //     return [
    //         'id' => $notification->id,
    //         'type' => $data['type'] ?? class_basename($notification->type),
    //         'data' => $data,
    //         'read_at' => $notification->read_at?->toIso8601String(),
    //         'is_read' => $notification->read_at !== null,
    //         'created_at' => $notification->created_at?->toIso8601String(),
    //     ];
    // }
}
