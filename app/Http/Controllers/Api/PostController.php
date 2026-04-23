<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\PostLike;
use App\Models\PostGroup;
use App\Models\Comment;
use App\Models\Notification;

class PostController extends Controller
{
    public function store(Request $request)
    {
        $user = auth('api')->user();

        $validated = $request->validate([
            'description' => 'nullable|string|max:5000',

            'visibility' => [
                'required',
                Rule::in(['public', 'connections', 'groups'])
            ],

            'group_ids' => 'required_if:visibility,groups|array',
            'group_ids.*' => 'exists:groups,id',

            'media' => 'nullable|array|max:10',

            'media.*' => 'file|max:20480', // 20MB max
        ]);

        if ($request->visibility === 'groups') {
            $userGroupIds = $user->groups()->pluck('groups.id')->toArray();

            foreach ($request->group_ids as $gid) {
                if (!in_array($gid, $userGroupIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized group selected'
                    ], 403);
                }
            }
        }

        DB::beginTransaction();

        try {

            $post = Post::create([
                'user_id' => $user->id,
                'description' => $request->description,
                'visibility' => $request->visibility,
            ]);

            if ($request->visibility === 'groups') {
                $post->groups()->sync($request->group_ids);
            }

            if ($request->hasFile('media')) {

                foreach ($request->file('media') as $index => $file) {

                    $mime = $file->getMimeType();
                    $type = str_contains($mime, 'video') ? 'video' : 'image';

                    $path = $file->store('posts', 'public');

                    $post->media()->create([
                        'file_path' => $path,
                        'type' => $type,
                        'order' => $index,
                    ]);
                }
            }

            DB::commit();

            $post->load([
                'user:id,first_name,last_name,profile_image',
                'media',
                'groups:id,name'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully',
                'data' => $post
            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Post creation failed',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

}
