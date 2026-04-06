<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['name' => 'required|string|unique:groups']);

        $group = Group::create([
            'name' => $request->name,
            'description' => $request->description,
            'creator_id' => auth()->id(),
        ]);

        // Creator ke automatic member banaye deya
        $group.members()->attach(auth()->id(), ['role' => 'admin']);

        return response()->json(['message' => 'Group created!', 'group' => $group]);
    }
}
