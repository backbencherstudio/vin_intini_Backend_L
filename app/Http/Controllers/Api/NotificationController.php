<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min((int) $request->integer('limit', $request->integer('per_page', 20)), 50));
        $page = max(1, (int) $request->integer('current_page', $request->integer('page', 1)));
        $unreadOnly = (bool) $request->boolean('unread_only', false);
        $search = trim((string) $request->query('search', ''));

        $isIndustry = $request->has('is_industry') ? $request->boolean('is_industry') : null;

        $query = $request->user()->notifications();

        // Industry filter logic
        if ($isIndustry === true) {
            $industryId = $request->user()->industry?->id
                ?? $request->user()->company_id
                ?? $request->query('industry_id');

            if ($industryId) {
                $query->where('data->industry_id', (int) $industryId);
            } else {
                $query->whereNotNull('data->industry_id');
            }
        } elseif ($isIndustry === false) {
            $query->whereNull('data->industry_id');
        }

        // Search filter
        if ($search !== '') {
            $query = $query->where(function ($notificationQuery) use ($search) {
                $notificationQuery
                    ->where('type', 'like', '%'.$search.'%')
                    ->orWhere('data', 'like', '%'.$search.'%');
            });
        }

        // Stats calculation (current filter onujayi exact unread count)
        $statsQuery = clone $query;
        $unreadCount = (clone $statsQuery)->whereNull('read_at')->count();

        // Unread only filter
        if ($unreadOnly) {
            $query = $query->whereNull('read_at');
        }

        $notifications = $query->orderByDesc('created_at')->paginate($perPage, page: $page);
        $items = $notifications->items();

        // Batch preload any missing senders to prevent N+1 queries
        $missingUserIds = [];
        $missingIndustryIds = [];

        foreach ($items as $item) {
            $data = is_string($item->data) ? json_decode($item->data, true) : $item->data;
            if (is_array($data)) {
                if (empty($data['sender_name']) || empty($data['sender_username']) || empty($data['sender_profile_image_url'])) {
                    $uId = $data['sender_id'] ?? $data['user_id'] ?? $data['acceptor_id'] ?? $data['inviter_id'] ?? $data['applicant_id'] ?? null;
                    if ($uId) {
                        $missingUserIds[] = (int) $uId;
                    }
                }
                if (! empty($data['industry_id']) && (empty($data['sender_username']) || empty($data['industry_slug']))) {
                    $missingIndustryIds[] = (int) $data['industry_id'];
                }
            }
        }

        $preloadedUsers = ! empty($missingUserIds)
            ? User::whereIn('id', array_unique($missingUserIds))->get()->keyBy('id')
            : collect();

        $preloadedIndustries = ! empty($missingIndustryIds)
            ? Industry::whereIn('id', array_unique($missingIndustryIds))->get()->keyBy('id')
            : collect();

        $formattedNotifications = collect($items)
            ->map(fn ($n) => $this->formatNotification($n, $preloadedUsers, $preloadedIndustries))
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully',
            'stats' => [
                'total_notifications' => $statsQuery->count(),
                'unread_notifications' => $unreadCount,
            ],
            'data' => $formattedNotifications,
            'total' => $notifications->total(),
            'limit' => $notifications->perPage(),
            'current_page' => $notifications->currentPage(),
            'total_page' => $notifications->lastPage(),
            'last_page' => $notifications->lastPage(),
            'filters' => [
                'unread_only' => $unreadOnly,
                'is_industry' => $isIndustry,
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

    private function formatNotification(Model $notification, ?Collection $preloadedUsers = null, ?Collection $preloadedIndustries = null): array
    {
        $data = $notification->data;

        if (is_string($data)) {
            $data = json_decode($data, true) ?: [];
        }

        $type = $data['type'] ?? class_basename($notification->type);

        // 1. Resolve missing sender user details without N+1 queries
        $userId = $data['sender_id'] ?? $data['user_id'] ?? $data['acceptor_id'] ?? $data['inviter_id'] ?? $data['applicant_id'] ?? null;
        if ($userId && (empty($data['sender_name']) || empty($data['sender_username']) || empty($data['sender_profile_image_url']))) {
            $user = $preloadedUsers?->get($userId) ?? User::find($userId);
            if ($user) {
                $data['sender_id'] = $data['sender_id'] ?? $user->id;
                $data['sender_name'] = $data['sender_name'] ?? trim(($user->first_name ?? '').' '.($user->last_name ?? ''));
                $data['sender_username'] = $data['sender_username'] ?? $user->username;
                $data['sender_profile_image_url'] = $data['sender_profile_image_url'] ?? $user->profile_image_url;
            }
        }

        // 2. Normalization based on notification type
        if ($type === 'JobApplicationReceivedNotification') {
            // Sender is the applicant (User), not the industry
            if (! empty($data['applicant_name'])) {
                $data['sender_name'] = $data['applicant_name'];
            }
            if (! empty($data['applicant_username'])) {
                $data['sender_username'] = $data['applicant_username'];
            }
            $applicantPhoto = $data['sender_profile_image_url'] ?? $data['applicant_profile_url'] ?? null;
            if (! empty($applicantPhoto)) {
                $data['sender_profile_image_url'] = $applicantPhoto;
                $data['profile_image_url'] = $applicantPhoto;
            }
        } elseif ($type === 'JobApplicationStatusUpdatedNotification' || (! empty($data['industry_id']) && empty($data['sender_name']))) {
            // Sender is the industry / company
            $industryId = $data['industry_id'] ?? null;
            $industry = ($industryId && $preloadedIndustries) ? $preloadedIndustries->get($industryId) : null;

            if ($industry) {
                $data['industry_name'] = $data['industry_name'] ?? $industry->name;
                $data['industry_slug'] = $data['industry_slug'] ?? $industry->slug;
            }

            $logoUrl = $data['sender_profile_image_url'] ?? $data['industry_logo_url'] ?? $data['industry_logo'] ?? $industry?->logo;

            if (! empty($data['industry_name'])) {
                $data['sender_name'] = $data['industry_name'];
            }
            if (! empty($logoUrl)) {
                $data['sender_profile_image_url'] = $logoUrl;
            }
            if (empty($data['sender_username'])) {
                $data['sender_username'] = $data['industry_slug'] ?? ($industry?->slug ?? '');
            }
        }

        // 3. Normalize sender for connection & invitation types
        if (isset($data['acceptor_name']) && empty($data['sender_name'])) {
            $data['sender_name'] = $data['acceptor_name'];
        }
        if (isset($data['acceptor_username']) && empty($data['sender_username'])) {
            $data['sender_username'] = $data['acceptor_username'];
        }
        if (isset($data['acceptor_profile_image_url']) && empty($data['sender_profile_image_url'])) {
            $data['sender_profile_image_url'] = $data['acceptor_profile_image_url'];
        }
        if (isset($data['inviter_username']) && empty($data['sender_username'])) {
            $data['sender_username'] = $data['inviter_username'];
        }
        if (isset($data['inviter_name']) && empty($data['sender_name'])) {
            $data['sender_name'] = $data['inviter_name'];
        }

        // Maintain username key if sender_username is present for backwards compatibility
        if (! empty($data['sender_username']) && empty($data['username'])) {
            $data['username'] = $data['sender_username'];
        }

        // 4. Grammar fix for job application status update message to cleanly join with bold sender name in UI
        if (isset($data['message']) && str_starts_with($data['message'], 'Your application for ') && str_contains($data['message'], ' has been updated to ')) {
            $data['message'] = str_replace(
                ['Your application for ', ' has been updated to '],
                ['updated your application for ', ' to '],
                $data['message']
            );
        }

        // 5. Clean up redundant duplicate & stale keys
        unset(
            $data['unread_count'],
            $data['avatar'],
            $data['image'],
            $data['sender_profile_image'],
            $data['applicant_profile_image'],
            $data['applicant_profile_url'],
            $data['industry_logo'],
            $data['industry_logo_url']
        );

        // For industry notifications, sender is an industry so remove user-specific profile_image_url and username
        if ($type === 'JobApplicationStatusUpdatedNotification') {
            unset($data['profile_image_url'], $data['username']);
        }

        // Remove irrelevant cross-domain keys if this is NOT that type of notification
        if (! in_array($type, ['ConnectionRequestAcceptedNotification', 'ConnectionRequestReceivedNotification', 'GroupInvitationAcceptedNotification'])) {
            unset($data['acceptor_username'], $data['acceptor_name'], $data['acceptor_profile_image_url'], $data['acceptor_profile_image']);
        }
        if ($type !== 'GroupInvitationNotification') {
            unset($data['inviter_username'], $data['inviter_name']);
        }
        if (! str_contains($type, 'JobApplication')) {
            unset($data['applicant_name'], $data['applicant_profile_url']);
        }

        return [
            'id' => $notification->id,
            'type' => $type,
            'data' => $data,
            'read_at' => $notification->read_at?->toIso8601String(),
            'is_read' => $notification->read_at !== null,
            'created_at' => $notification->created_at?->toIso8601String(),
        ];
    }
}
