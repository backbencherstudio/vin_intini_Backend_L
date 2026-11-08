<?php

namespace App\Services\Social;

use App\Models\Post;
use App\Models\User;
use App\Services\MediaUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PostService
{
    public function __construct(
        protected MediaUploadService $mediaService
    ) {}

    /**
     * Create post(s) for a user with safe file staging and rollback cleanup.
     *
     * @param  array<string, mixed>  $data
     * @param  array<int, UploadedFile>  $files
     * @return array<int, Post>
     */
    public function createPost(User $user, array $data, array $files = []): array
    {
        $uploadedMedia = [];

        try {
            foreach ($files as $index => $file) {
                $media = $this->mediaService->upload($file);
                $uploadedMedia[] = [
                    'file_path' => $media['file_path'],
                    'type' => $media['type'],
                    'order' => $index,
                ];
            }

            return DB::transaction(function () use ($user, $data, $uploadedMedia): array {
                $posts = [];

                if (($data['visibility'] ?? 'public') === 'groups') {
                    $groupIds = $data['group_ids'] ?? [];

                    foreach ($groupIds as $groupId) {
                        $post = Post::create([
                            'user_id' => $user->id,
                            'description' => $data['description'] ?? null,
                            'visibility' => 'groups',
                            'who_can_comment' => $data['who_can_comment'] ?? 'anyone',
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
                        'description' => $data['description'] ?? null,
                        'visibility' => $data['visibility'] ?? 'public',
                        'who_can_comment' => $data['who_can_comment'] ?? 'anyone',
                    ]);

                    foreach ($uploadedMedia as $media) {
                        $post->media()->create($media);
                    }

                    $posts[] = $post;
                }

                return $posts;
            });
        } catch (\Throwable $e) {
            // Clean up any uploaded media on failure to prevent orphaned storage files
            foreach ($uploadedMedia as $media) {
                $filePath = $media['file_path'] ?? null;
                if ($filePath && Storage::disk('public')->exists($filePath)) {
                    Storage::disk('public')->delete($filePath);
                }
            }

            throw $e;
        }
    }
}
