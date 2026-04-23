<?php

namespace App\Http\Controllers;

use App\Models\Connection;
use App\Models\Group;
use App\Models\GroupInvitation;
use App\Models\User;
use App\Notifications\GroupInvitationNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class GroupController extends Controller
{
    public function groupSuggestions(Request $request)
    {
        $user = $request->user();

        $groups = Group::query()
            ->where('type', 'public')
            ->where('discoverability', 'listed')
            ->whereDoesntHave('members', function ($query) use ($user) {
                $query->where('users.id', $user->id);
            })
            ->withCount([
                'members as total_member',
            ])
            ->inRandomOrder()
            ->limit(10)
            ->get([
                'id',
                'name',
                'logo',
            ]);

        $groups = $groups->map(function (Group $group): array {
            return [
                'id' => $group->id,
                'name' => $group->name,
                'logo_url' => $group->logo_url,
                'total_member' => (int) $group->total_member,
            ];
        })->values();

        if ($groups->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No group suggestions available at the moment. Please check back later.',
                'status' => 'success',
                'data' => [],
                'stats' => [
                    'total_groups' => 0,
                ],
                'total' => 0,
                'limit' => 10,
                'current_page' => 1,
                'total_page' => 0,
                'last_page' => 0,
                'filters' => [],
            ], 200);
        }

        return response()->json([
            'success' => true,
            'message' => 'Group suggestions retrieved successfully.',
            'status' => 'success',
            'data' => $groups,
            'stats' => [
                'total_groups' => $groups->count(),
            ],
            'total' => $groups->count(),
            'limit' => 10,
            'current_page' => 1,
            'total_page' => 1,
            'last_page' => 1,
            'filters' => [],
        ], 200);
    }

    public function store(Request $request)
    {
        $data = $request->all();

        if (isset($data['industry']) && is_string($data['industry'])) {
            $data['industry'] = array_map('trim', explode(',', $data['industry']));
        }

        $request->merge($data);

        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:groups,name',
            'description' => 'required|string|max:2500',
            'industry' => 'required|array|max:3',
            'location' => 'nullable|string|max:255',
            'rules' => 'nullable|string|max:2500',
            'type' => 'required|in:public,private',
            'discoverability' => 'required|in:listed,unlisted',
            'allow_member_invites' => 'boolean',
            'require_post_approval' => 'boolean',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('group_logos', 'public');
        }

        if ($request->hasFile('cover_photo')) {
            $validated['cover_photo'] = $request->file('cover_photo')->store('group_covers', 'public');
        }

        $validated['creator_id'] = auth()->id();

        $group = Group::create($validated);

        $group->members()->attach(auth()->id(), ['role' => 'admin']);

        return response()->json([
            'status' => 'success',
            'message' => 'Group created successfully!',
            'data' => $group->load(['creator:id,first_name,last_name,email']),
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $group = Group::with([
            'creator:id,first_name,last_name,email',
            'members' => function ($query) {
                $query->select('users.id', 'first_name', 'last_name', 'email')->limit(10);
            },
        ])
            ->withCount('members')
            ->find($id);

        if (! $group) {
            return response()->json([
                'status' => 'error',
                'message' => 'Group not found',
            ], 404);
        }

        $user = $request->user();

        $isMember = $user ? $group->members()->where('user_id', $user->id)->exists() : false;

        $isAdmin = $user ? ($group->creator_id === $user->id) : false;

        if ($group->type === 'private' && ! $isMember && ! $isAdmin) {
            return response()->json([
                'status' => 'error',
                'message' => 'This is a private group. You must be a member to see details.',
                'data' => [
                    'group' => [
                        'id' => $group->id,
                        'name' => $group->name,
                        'type' => $group->type,
                        'members_count' => $group->members_count,
                    ],
                    'is_current_user_member' => false,
                ],
            ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'group' => $group,
                'is_current_user_member' => $isMember,
            ],
        ], 200);
    }

    public function inviteableUsers(Request $request, $id)
    {
        $group = Group::query()
            ->withCount('members')
            ->find($id);

        if (! $group) {
            return response()->json([
                'status' => 'error',
                'message' => 'Group not found',
            ], 404);
        }

        $currentUser = $request->user();

        if (! $this->canInviteToGroup($group, $currentUser->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not allowed to invite users to this group.',
            ], 403);
        }

        $inviteableUserIds = $this->connectedUserIds($currentUser->id)
            ->diff($group->members()->pluck('users.id')->map(fn($userId) => (int) $userId))
            ->diff($this->pendingInvitationUserIds($group->id))
            ->values();

        $perPage = max(1, min((int) $request->integer('per_page', 15), 50));
        $search = trim((string) $request->query('search', ''));

        $query = User::query()
            ->whereIn('id', $inviteableUserIds)
            ->select(['id', 'first_name', 'last_name', 'title', 'profile_image'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery
                        ->where('first_name', 'like', '%' . $search . '%')
                        ->orWhere('last_name', 'like', '%' . $search . '%')
                        ->orWhere('title', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('first_name')
            ->orderBy('last_name');

        $users = $query->paginate($perPage);

        $data = $users->getCollection()->map(function (User $user): array {
            return [
                'id' => $user->id,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'title' => $user->title,
                'profile_image_url' => $user->profile_image_url,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'message' => 'Inviteable users retrieved successfully.',
            'status' => 'success',
            'data' => $data,
            'stats' => [
                'total_users' => $users->total(),
            ],
            'total' => $users->total(),
            'limit' => $users->perPage(),
            'current_page' => $users->currentPage(),
            'total_page' => $users->lastPage(),
            'last_page' => $users->lastPage(),
            'filters' => [
                'group_id' => $group->id,
                'search' => $search !== '' ? $search : null,
            ],
        ], 200);
    }

    public function sendInvitations(Request $request, $id)
    {
        $group = Group::query()
            ->withCount('members')
            ->find($id);

        if (! $group) {
            return response()->json([
                'status' => 'error',
                'message' => 'Group not found',
            ], 404);
        }

        if (! $this->canInviteToGroup($group, $request->user()->id)) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not allowed to invite users to this group.',
            ], 403);
        }

        $validated = $request->validate([
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
        ]);

        $currentUser = $request->user();
        $inviteableUserIds = $this->connectedUserIds($currentUser->id)
            ->diff($group->members()->pluck('users.id')->map(fn($userId) => (int) $userId))
            ->diff($this->pendingInvitationUserIds($group->id))
            ->values();

        $requestedUserIds = collect($validated['user_ids'])->map(fn($userId) => (int) $userId)->values();
        $invalidUserIds = $requestedUserIds->diff($inviteableUserIds)->values();

        if ($invalidUserIds->isNotEmpty()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some selected users are not eligible for invitation.',
                'data' => [
                    'invalid_user_ids' => $invalidUserIds,
                ],
            ], 422);
        }

        $invitees = User::query()
            ->whereIn('id', $requestedUserIds)
            ->get(['id', 'first_name', 'last_name', 'title', 'profile_image']);

        $invitations = collect();

        foreach ($invitees as $invitee) {
            $invitation = GroupInvitation::create([
                'group_id' => $group->id,
                'inviter_id' => $currentUser->id,
                'invited_user_id' => $invitee->id,
            ]);

            $invitations->push($invitation);

            $invitee->notify(new GroupInvitationNotification($invitation, $group, $currentUser));
        }

        return response()->json([
            'success' => true,
            'message' => 'Group invitations sent successfully.',
            'status' => 'success',
            'data' => [
                'group' => [
                    'id' => $group->id,
                    'name' => $group->name,
                ],
                'invited_users' => $invitees->map(function (User $user) use ($invitations): array {
                    $invitation = $invitations->firstWhere('invited_user_id', $user->id);

                    return [
                        'invitation_id' => $invitation?->id,
                        'id' => $user->id,
                        'first_name' => $user->first_name,
                        'last_name' => $user->last_name,
                        'title' => $user->title,
                        'profile_image_url' => $user->profile_image_url,
                    ];
                })->values(),
            ],
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $group = Group::findOrFail($id);

        if ($group->creator_id !== auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized! You can only edit your own groups.',
            ], 403);
        }

        $input = $request->all();
        if (isset($input['industry']) && is_string($input['industry']) && ! empty($input['industry'])) {
            $input['industry'] = array_map('trim', explode(',', $input['industry']));
        }
        $request->merge($input);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255|unique:groups,name,' . $id,
            'description' => 'sometimes|required|string|max:2500',
            'industry' => 'nullable|array|max:3',
            'location' => 'nullable|string|max:255',
            'rules' => 'nullable|string|max:2500',
            'type' => 'sometimes|required|in:public,private',
            'discoverability' => 'sometimes|required|in:listed,unlisted',
            'allow_member_invites' => 'nullable',
            'require_post_approval' => 'nullable',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
        ]);

        if ($request->hasFile('logo')) {
            if ($group->logo && Storage::disk('public')->exists($group->logo)) {
                Storage::disk('public')->delete($group->logo);
            }
            $validated['logo'] = $request->file('logo')->store('group_logos', 'public');
        }

        if ($request->hasFile('cover_photo')) {
            if ($group->cover_photo && Storage::disk('public')->exists($group->cover_photo)) {
                Storage::disk('public')->delete($group->cover_photo);
            }
            $validated['cover_photo'] = $request->file('cover_photo')->store('group_covers', 'public');
        }

        if ($request->has('allow_member_invites')) {
            $validated['allow_member_invites'] = filter_var($request->allow_member_invites, FILTER_VALIDATE_BOOLEAN);
        }
        if ($request->has('require_post_approval')) {
            $validated['require_post_approval'] = filter_var($request->require_post_approval, FILTER_VALIDATE_BOOLEAN);
        }

        $group->update($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Group updated successfully!',
            'data' => $group->fresh(),
        ], 200);
    }

    public function myCreatedGroups(Request $request)
    {
        $userId = auth()->id();

        $totalCreatedEver = Group::where('creator_id', $userId)->count();

        $query = Group::where('creator_id', $userId)->withCount('members');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $groups = $query->latest()->paginate(10);

        if ($groups->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => $request->has('search') ? 'No groups found matching your search.' : 'You haven’t created any groups yet.',
                'status' => 'success',
                'total_created_groups_count' => $totalCreatedEver,
                'data' => [],
                'stats' => [
                    'total_created_groups_count' => $totalCreatedEver,
                ],
                'total' => 0,
                'limit' => 10,
                'current_page' => $groups->currentPage(),
                'total_page' => 0,
                'last_page' => 0,
                'filters' => [
                    'search' => $request->has('search') ? $request->input('search') : null,
                ],
            ], 200);
        }

        $data = $groups->getCollection()->values();

        return response()->json([
            'success' => true,
            'message' => 'Created groups retrieved successfully.',
            'status' => 'success',
            'total_created_groups_count' => $totalCreatedEver,
            'data' => $data,
            'stats' => [
                'total_created_groups_count' => $totalCreatedEver,
            ],
            'total' => $groups->total(),
            'limit' => $groups->perPage(),
            'current_page' => $groups->currentPage(),
            'total_page' => $groups->lastPage(),
            'last_page' => $groups->lastPage(),
            'filters' => [
                'search' => $request->has('search') ? $request->input('search') : null,
            ],
        ], 200);
    }

    public function myJoinedGroups(Request $request)
    {
        $user = auth()->user();

        $baseQuery = $user->groups()->where('groups.creator_id', '!=', $user->id);

        $totalJoinedEver = (clone $baseQuery)->count();

        $query = $baseQuery
            ->with(['creator:id,first_name,last_name,email,profile_image'])
            ->withCount('members');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $groups = $query->latest()->paginate(10);

        if ($groups->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => $request->has('search') ? 'No groups match your search.' : 'You haven’t joined any groups yet.',
                'status' => 'success',
                'total_joined_count' => $totalJoinedEver,
                'data' => [],
                'stats' => [
                    'total_joined_count' => $totalJoinedEver,
                ],
                'total' => 0,
                'limit' => 10,
                'current_page' => $groups->currentPage(),
                'total_page' => 0,
                'last_page' => 0,
                'filters' => [
                    'search' => $request->has('search') ? $request->input('search') : null,
                ],
            ], 200);
        }

        $data = $groups->getCollection()->values();

        return response()->json([
            'success' => true,
            'message' => 'Joined groups retrieved successfully.',
            'status' => 'success',
            'total_joined_count' => $totalJoinedEver,
            'data' => $data,
            'stats' => [
                'total_joined_count' => $totalJoinedEver,
            ],
            'total' => $groups->total(),
            'limit' => $groups->perPage(),
            'current_page' => $groups->currentPage(),
            'total_page' => $groups->lastPage(),
            'last_page' => $groups->lastPage(),
            'filters' => [
                'search' => $request->has('search') ? $request->input('search') : null,
            ],
        ], 200);
    }

    public function joinGroup(Request $request, $group_id = null)
    {
        $targetGroupId = $group_id ?? $request->group_id;

        if (! $targetGroupId) {
            return response()->json(['status' => 'error', 'message' => 'Group ID is required'], 422);
        }

        $group = Group::findOrFail($targetGroupId);

        if ($group->members()->where('user_id', auth()->id())->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are already a member of this group',
            ], 400);
        }

        $group->members()->attach(auth()->id(), ['role' => 'member']);

        return response()->json([
            'status' => 'success',
            'message' => 'Joined successfully!',
            'group_name' => $group->name,
        ], 200);
    }

    public function leaveGroup(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id',
        ]);

        $group = Group::findOrFail($request->group_id);

        $isMember = $group->members()->where('user_id', auth()->id())->exists();

        if (! $isMember) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not a member of this group',
            ], 400);
        }

        if ($group->creator_id === auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Creator cannot leave the group. You must delete the group or transfer ownership.',
            ], 403);
        }

        $group->members()->detach(auth()->id());

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully left the group',
        ], 200);
    }

    public function acceptInvitation(Request $request, int $invitationId)
    {
        $invitation = GroupInvitation::query()
            ->with('group')
            ->where('id', $invitationId)
            ->where('invited_user_id', $request->user()->id)
            ->first();

        if (! $invitation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invitation not found.',
            ], 404);
        }

        $group = $invitation->group;
        $currentUserId = $request->user()->id;

        if (! $group->members()->where('user_id', $currentUserId)->exists()) {
            $group->members()->attach($currentUserId, ['role' => 'member']);
        }

        $invitation->delete();

        $this->deleteGroupInvitationNotification($request->user(), $group->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Invitation accepted successfully.',
            'data' => [
                'group_id' => $group->id,
                'group_name' => $group->name,
            ],
        ], 200);
    }

    public function ignoreInvitation(Request $request, int $invitationId)
    {
        $invitation = GroupInvitation::query()
            ->with('group')
            ->where('id', $invitationId)
            ->where('invited_user_id', $request->user()->id)
            ->first();

        if (! $invitation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invitation not found.',
            ], 404);
        }

        $group = $invitation->group;

        $invitation->delete();

        $this->deleteGroupInvitationNotification($request->user(), $group->id);

        return response()->json([
            'status' => 'success',
            'message' => 'Invitation ignored successfully.',
        ], 200);
    }

    public function invitationRequests(Request $request)
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = max(1, min((int) $request->integer('per_page', 10), 50));
        $page = max(1, (int) $request->integer('page', 1));

        $invitations = GroupInvitation::query()
            ->where('invited_user_id', $request->user()->id)
            ->with([
                'group:id,name,type,logo,creator_id',
                'inviter:id,first_name,last_name,title,profile_image',
            ])
            ->when($search !== '', function ($query) use ($search) {
                $query->whereHas('group', function ($groupQuery) use ($search) {
                    $groupQuery->where('name', 'like', '%' . $search . '%');
                });
            })
            ->latest('id')
            ->paginate($perPage, page: $page);

        $data = $invitations->getCollection()
            ->map(function (GroupInvitation $invitation): array {
                $inviterName = trim(($invitation->inviter?->first_name ?? '') . ' ' . ($invitation->inviter?->last_name ?? ''));

                return [
                    'invitation_id' => $invitation->id,
                    'group' => [
                        'id' => $invitation->group?->id,
                        'name' => $invitation->group?->name,
                        'type' => $invitation->group?->type,
                        'logo_url' => $invitation->group?->logo_url,
                    ],
                    'inviter' => [
                        'id' => $invitation->inviter?->id,
                        'name' => $inviterName,
                        'title' => $invitation->inviter?->title,
                        'profile_image_url' => $invitation->inviter?->profile_image_url,
                    ],
                    'requested_at' => $invitation->created_at?->toDateTimeString(),
                ];
            })
            ->values();

        if ($invitations->total() === 0) {
            return response()->json([
                'success' => true,
                'message' => 'No pending group invitations found.',
                'status' => 'success',
                'data' => [],
                'stats' => [
                    'total_invitations' => 0,
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

        return response()->json([
            'success' => true,
            'message' => 'Group invitations retrieved successfully.',
            'status' => 'success',
            'data' => $data,
            'stats' => [
                'total_invitations' => $invitations->total(),
            ],
            'total' => $invitations->total(),
            'limit' => $invitations->perPage(),
            'current_page' => $invitations->currentPage(),
            'total_page' => $invitations->lastPage(),
            'last_page' => $invitations->lastPage(),
            'filters' => [
                'search' => $search !== '' ? $search : null,
            ],
        ], 200);
    }

    private function canInviteToGroup(Group $group, int $userId): bool
    {
        if ($group->type === 'private') {
            return $group->creator_id === $userId
                || $group->members()
                ->where('user_id', $userId)
                ->wherePivot('role', 'admin')
                ->exists();
        }

        return $group->creator_id === $userId
            || $group->members()->where('user_id', $userId)->exists();
    }

    private function pendingInvitationUserIds(int $groupId): Collection
    {
        return GroupInvitation::query()
            ->where('group_id', $groupId)
            ->pluck('invited_user_id')
            ->map(fn($userId) => (int) $userId)
            ->values();
    }

    private function deleteGroupInvitationNotification(User $user, int $groupId): void
    {
        $user->notifications()
            ->where('type', GroupInvitationNotification::class)
            ->where('data->group_id', $groupId)
            ->delete();
    }

    private function connectedUserIds(int $userId): Collection
    {
        return Connection::query()
            ->accepted()
            ->where(function ($query) use ($userId) {
                $query->where('sender_id', $userId)
                    ->orWhere('receiver_id', $userId);
            })
            ->get(['sender_id', 'receiver_id'])
            ->toBase()
            ->map(function (Connection $connectionRequest) use ($userId) {
                return (int) ($connectionRequest->sender_id === $userId
                    ? $connectionRequest->receiver_id
                    : $connectionRequest->sender_id);
            })
            ->unique()
            ->values();
    }
}
