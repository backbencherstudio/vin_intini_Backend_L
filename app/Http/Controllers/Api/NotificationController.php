<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
                    ->where('type', 'like', '%' . $search . '%')
                    ->orWhere('data', 'like', '%' . $search . '%');
            });
        }

        $statsQuery = clone $query;

        $notifications = $query->orderByDesc('created_at')->paginate($perPage, page: $page);

        $totalNotifications = $statsQuery->count();
        $unreadNotifications = (clone $statsQuery)->whereNull('read_at')->count();

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully',
            'stats' => [
                'total_notifications' => $totalNotifications,
                'unread_notifications' => $unreadNotifications,
            ],
            'data' => $notifications->getCollection()->map(fn($notification) => $this->formatNotification($notification))->values(),
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
        $count = $request->user()->notifications()->whereNull('read_at')->count();

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
            $notification->update(['read_at' => now()]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Notification marked as read.',
            'data' => $this->formatNotification($notification),
        ], 200);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $count = $request->user()->notifications()->whereNull('read_at')->update(['read_at' => now()]);

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
        $count = $request->user()->notifications()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'All notifications deleted successfully.',
            'data' => [
                'deleted_count' => $count,
            ],
        ], 200);
    }

    private function formatNotification(Model $notification): array
    {
        $data = $notification->data;
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return [
            'id' => $notification->id,
            'type' => $notification->type,
            'data' => $data,
            'read_at' => $notification->read_at?->toDateTimeString(),
            'is_read' => $notification->read_at !== null,
            'created_at' => $notification->created_at?->toDateTimeString(),
        ];
    }

    public function realtimeConfig(Request $request): JsonResponse
    {
        $pusherConnection = config('broadcasting.connections.pusher', []);

        return response()->json([
            'status' => 'success',
            'data' => [
                'enabled' => config('broadcasting.default') === 'pusher'
                    && filled(data_get($pusherConnection, 'key'))
                    && filled(data_get($pusherConnection, 'app_id')),
                'broadcaster' => 'pusher',
                'pusher' => [
                    'key' => data_get($pusherConnection, 'key'),
                    'cluster' => data_get($pusherConnection, 'options.cluster'),
                    'host' => data_get($pusherConnection, 'options.host'),
                    'port' => data_get($pusherConnection, 'options.port'),
                    'scheme' => data_get($pusherConnection, 'options.scheme'),
                ],
                'auth_endpoint' => url('/api/broadcasting/auth'),
                'user_channel' => 'App.Models.User.{id}',
                'notifications' => [
                    'received' => 'App\\Notifications\\ConnectionRequestReceivedNotification',
                    'accepted' => 'App\\Notifications\\ConnectionRequestAcceptedNotification',
                ],
                'api' => [
                    'list' => url('/api/notifications'),
                    'unread_count' => url('/api/notifications/unread-count'),
                ],
            ],
        ], 200);
    }
}
