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

    public function edit($id)
    {
        $user = auth('api')->user();

        $post = Post::with([
            'media:id,post_id,file_path,type,order',
            'groups:id,name'
        ])->findOrFail($id);

        if ($post->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Post fetched successfully',
            'data' => [
                'id' => $post->id,
                'description' => $post->description,
                'visibility' => $post->visibility,

                'groups' => $post->groups->map(function ($group) {
                    return [
                        'id' => $group->id,
                        'name' => $group->name,
                    ];
                }),

                'media' => $post->media->map(function ($media) {
                    return [
                        'id' => $media->id,
                        'type' => $media->type,
                        'url' => $media->url,
                        'order' => $media->order,
                    ];
                }),
            ]
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = auth('api')->user();

        $post = Post::with('media')->findOrFail($id);

        if ($post->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validated = $request->validate([
            'description' => 'nullable|string|max:5000',

            'visibility' => [
                'required',
                Rule::in(['public', 'connections', 'groups'])
            ],

            'group_ids' => 'required_if:visibility,groups|array',
            'group_ids.*' => 'exists:groups,id',

            'media' => 'nullable|array|max:10',
            'media.*' => 'file|mimes:jpg,jpeg,png,mp4,mov,webm|max:20480',

            'remove_media_ids' => 'nullable|array',
            'remove_media_ids.*' => 'exists:post_media,id',
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

            $post->update([
                'description' => $request->description,
                'visibility' => $request->visibility,
            ]);

            if ($request->visibility === 'groups') {
                $post->groups()->sync($request->group_ids);
            } else {
                $post->groups()->detach();
            }

            if ($request->filled('remove_media_ids')) {

                $mediaToDelete = $post->media()
                    ->whereIn('id', $request->remove_media_ids)
                    ->get();

                foreach ($mediaToDelete as $media) {
                    Storage::disk('public')->delete($media->file_path);
                    $media->delete();
                }
            }

            if ($request->hasFile('media')) {

                $existingCount = $post->media()->count();

                foreach ($request->file('media') as $index => $file) {

                    $mime = $file->getMimeType();
                    $type = str_contains($mime, 'video') ? 'video' : 'image';

                    $path = $file->store('posts', 'public');

                    $post->media()->create([
                        'file_path' => $path,
                        'type' => $type,
                        'order' => $existingCount + $index,
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
                'message' => 'Post updated successfully',
                'data' => $post
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Post update failed',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function destroy($id)
    {
        $user = auth('api')->user();

        $post = Post::with(['media'])->findOrFail($id);

        if ($post->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        DB::beginTransaction();

        try {

            foreach ($post->media as $media) {
                Storage::disk('public')->delete($media->file_path);
            }

            $post->groups()->detach();
            $post->likes()->delete();
            $post->comments()->delete();

            $post->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Post deleted successfully'
            ]);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Post deletion failed',
                'error' => app()->environment('local') ? $e->getMessage() : null
            ], 500);
        }
    }

}
