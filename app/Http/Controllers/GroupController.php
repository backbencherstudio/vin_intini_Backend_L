<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

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
