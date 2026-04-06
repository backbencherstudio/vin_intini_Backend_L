<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:groups,name',
            'description' => 'required|string|max:2500',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'cover_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:5120',
            'industry' => 'nullable|array|max:3',
            'location' => 'nullable|string|max:255',
            'rules' => 'nullable|string|max:2500',
            'type' => 'required|in:public,private',
            'discoverability' => 'required|in:listed,unlisted',
            'allow_member_invites' => 'boolean',
            'require_post_approval' => 'boolean',
        ]);

        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('group_logos', 'public');
            $validated['logo'] = $logoPath;
        }

        if ($request->hasFile('cover_photo')) {
            $coverPath = $request->file('cover_photo')->store('group_covers', 'public');
            $validated['cover_photo'] = $coverPath;
        }

        // Set the creator
        $validated['creator_id'] = auth()->id();

        // Create the group with all settings
        $group = Group::create($validated);

        $group->members()->attach(auth()->id(), ['role' => 'admin']);

        return response()->json([
            'message' => 'Group created successfully!',
            'group' => $group
        ], 201);
    }
}
