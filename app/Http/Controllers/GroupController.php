<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GroupController extends Controller
{
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
            'data' => $group->load('creator')
        ], 201);
    }

    public function show(Request $request, $id)
    {
        $group = Group::with(['creator', 'members' => function ($query) {
            $query->limit(10);
        }])
            ->withCount('members')
            ->findOrFail($id);

        $user = $request->user();

        $isMember = $user ? $group->members()->where('user_id', $user->id)->exists() : false;

        $isAdmin = $user ? ($group->creator_id === $user->id) : false;

        if ($group->type === 'private' && !$isMember && !$isAdmin) {
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
                'is_current_user_member' => false
            ]
        ], 403);
        }

        return response()->json([
            'status' => 'success',
            'data' => [
                'group' => $group,
                'is_current_user_member' => $isMember
            ]
        ], 200);
    }

    public function update(Request $request, $id)
    {
        $group = Group::findOrFail($id);

        if ($group->creator_id !== auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unauthorized! You can only edit your own groups.'
            ], 403);
        }

        $input = $request->all();
        if (isset($input['industry']) && is_string($input['industry']) && !empty($input['industry'])) {
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
            'data' => $group->fresh()
        ], 200);
    }




    public function joinGroup(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id'
        ]);

        $groupId = $request->group_id;
        $group = Group::findOrFail($groupId);

        if ($group->members()->where('user_id', auth()->id())->exists()) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are already a member of this group'
            ], 400);
        }

        $group->members()->attach(auth()->id(), ['role' => 'member']);

        return response()->json([
            'status' => 'success',
            'message' => 'Joined successfully!',
            'group_name' => $group->name
        ], 200);
    }

    public function leaveGroup(Request $request)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id'
        ]);

        $group = Group::findOrFail($request->group_id);

        $isMember = $group->members()->where('user_id', auth()->id())->exists();

        if (!$isMember) {
            return response()->json([
                'status' => 'error',
                'message' => 'You are not a member of this group'
            ], 400);
        }

        if ($group->creator_id === auth()->id()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Creator cannot leave the group. You must delete the group or transfer ownership.'
            ], 403);
        }

        $group->members()->detach(auth()->id());

        return response()->json([
            'status' => 'success',
            'message' => 'Successfully left the group'
        ], 200);
    }
}
