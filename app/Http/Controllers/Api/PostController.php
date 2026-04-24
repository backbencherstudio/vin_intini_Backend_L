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
use App\Models\Group;
use App\Models\Notification;

class PostController extends Controller
{
    public function store(Request $request)
    {
        $user = auth('api')->user();

        $validated = $request->validate([
            'description' => 'nullable|string|max:5000',
            'visibility' => 'required|in:public,connections,groups',
            'who_can_comment' => 'required|in:anyone,connections,no_one',
            'group_ids' => 'required_if:visibility,groups|array',
            'group_ids.*' => 'exists:groups,id',
            'media' => 'nullable|array|max:10',
            'media.*' => 'file|max:20480',
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

            $posts = [];

            $uploadedMedia = [];

            if ($request->hasFile('media')) {
                foreach ($request->file('media') as $index => $file) {

                    $mime = $file->getMimeType();
                    $type = str_contains($mime, 'video') ? 'video' : 'image';

                    $path = $file->store('posts', 'public');

                    $uploadedMedia[] = [
                        'file_path' => $path,
                        'type' => $type,
                        'order' => $index,
                    ];
                }
            }

            if ($request->visibility === 'groups') {

                foreach ($request->group_ids as $groupId) {

                    $post = Post::create([
                        'user_id' => $user->id,
                        'description' => $request->description,
                        'visibility' => 'groups',
                        'who_can_comment' => $request->who_can_comment,
                    ]);

                    $post->groups()->sync([$groupId]);

                    foreach ($uploadedMedia as $media) {
                        $post->media()->create($media);
                    }

                    $posts[] = $post;
                }

            } else {

                $post = Post::create([
                    'user_id' => $user->id,
                    'description' => $request->description,
                    'visibility' => $request->visibility,
                    'who_can_comment' => $request->who_can_comment,
                ]);

                foreach ($uploadedMedia as $media) {
                    $post->media()->create($media);
                }

                $posts[] = $post;
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Post(s) created successfully',
                'data' => $posts
            ], 200);

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
            'media:id,post_id,file_path,type,order'
        ])->findOrFail($id);

        if ($post->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!in_array($post->visibility, ['public', 'connections'])) {
            return response()->json([
                'success' => false,
                'message' => 'Group posts cannot be edited from here'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Post fetched successfully',
            'data' => [
                'id' => $post->id,
                'description' => $post->description,
                'visibility' => $post->visibility,
                'who_can_comment' => $post->who_can_comment,
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

    public function updateProfilePost(Request $request, $id)
    {
        $user = auth('api')->user();

        $post = Post::with('media')->findOrFail($id);

        if ($post->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        if (!in_array($post->visibility, ['public', 'connections'])) {
            return response()->json([
                'success' => false,
                'message' => 'Group posts cannot be updated from profile'
            ], 403);
        }

        $validated = $request->validate([
            'description' => 'nullable|string|max:5000',

            'visibility' => 'required|in:public,connections',
            'who_can_comment' => 'required|in:anyone,connections,no_one',
            'media' => 'nullable|array|max:10',
            'media.*' => 'file|mimes:jpg,jpeg,png,mp4,mov,webm|max:20480',
            'remove_media_ids' => 'nullable|array',
            'remove_media_ids.*' => 'exists:post_media,id',
        ]);

        DB::beginTransaction();

        try {

            $post->update([
                'description' => $request->description,
                'visibility' => $request->visibility,
                'who_can_comment' => $request->who_can_comment,
            ]);

            if ($request->filled('remove_media_ids')) {

                $mediaToDelete = $post->media()
                    ->whereIn('id', $request->remove_media_ids)
                    ->get();

                foreach ($mediaToDelete as $media) {

                    $count = PostMedia::where('file_path', $media->file_path)->count();

                    if ($count === 1) {
                        Storage::disk('public')->delete($media->file_path);
                    }

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
                'media'
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

    public function editGroupPost($postId, $groupId)
    {
        $user = auth('api')->user();

        $post = Post::with([
            'media:id,post_id,file_path,type,order'
        ])->findOrFail($postId);

        if ($post->visibility !== 'groups') {
            return response()->json([
                'success' => false,
                'message' => 'This is not a group post'
            ], 400);
        }

        $postGroup = PostGroup::where('post_id', $postId)
            ->where('group_id', $groupId)
            ->where('remove_status', 0)
            ->first();

        if (!$postGroup) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found in this group'
            ], 404);
        }

        $group = Group::findOrFail($groupId);

        if ($post->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Only post creator can edit this post'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'message' => 'Group post fetched successfully',
            'data' => [
                'post_id' => $post->id,
                'group' => [
                    'id' => $group->id,
                    'name' => $group->name,
                ],
                'description' => $post->description,
                'visibility' => $post->visibility,
                'who_can_comment' => $post->who_can_comment,

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

    // public function destroy($id)
    // {
    //     $user = auth('api')->user();

    //     $post = Post::with(['media'])->findOrFail($id);

    //     if ($post->user_id !== $user->id) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Unauthorized'
    //         ], 403);
    //     }

    //     DB::beginTransaction();

    //     try {

    //         foreach ($post->media as $media) {
    //             Storage::disk('public')->delete($media->file_path);
    //         }

    //         $post->groups()->detach();
    //         $post->likes()->delete();
    //         $post->comments()->delete();

    //         $post->delete();

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'message' => 'Post deleted successfully'
    //         ]);

    //     } catch (\Throwable $e) {

    //         DB::rollBack();

    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Post deletion failed',
    //             'error' => app()->environment('local') ? $e->getMessage() : null
    //         ], 500);
    //     }
    // }

    public function removeFromGroup($postId, $groupId)
    {
        $user = auth('api')->user();

        $postGroup = PostGroup::where('post_id', $postId)
            ->where('group_id', $groupId)
            ->firstOrFail();

        $post = $postGroup->post;
        $group = $postGroup->group;

        if (
            $post->user_id !== $user->id &&
            $group->creator_id !== $user->id
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $postGroup->update([
            'remove_status' => 1
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Post removed from group'
        ]);
    }

}
