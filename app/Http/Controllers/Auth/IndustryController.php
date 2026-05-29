<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentLike;
use App\Models\Industry;
use App\Models\IndustryFollow;
use App\Models\Post;
use App\Models\PostIndustry;
use App\Models\PostLike;
use App\Models\Reply;
use App\Models\ReplyLike;
use App\Models\Subscription;
use App\Models\User;
use App\Services\IndustryMediaUploadService;
use App\Services\OptimizedImageUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IndustryController extends Controller
{
    /**
     * Visibility values a company post may use.
     */
    private const POST_VISIBILITIES = ['public', 'followers', 'private'];

    private const PROFILE_FEATURE = 'company_profile';

    public function store(Request $request)
    {
        $subscription = $this->activeSubscription(auth()->id());

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'You need an active subscription to create a company page.',
            ], 403);
        }

        if (! $this->planAllowsCompanyProfile($subscription)) {
            return response()->json([
                'success' => false,
                'message' => 'Your current plan does not include company page creation.',
            ], 403);
        }

        $existingIndustry = $this->ownedIndustry(auth()->id());

        if ($existingIndustry) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a company page.',
            ], 409);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:industries,slug'],
            'industry' => ['required', 'string', 'max:255'],
            'website' => ['nullable', 'url', 'max:255'],
            'address' => ['nullable', 'string', 'max:2000'],
            'company_size' => ['nullable', 'string', 'max:100'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'authorization_confirmed' => ['required', 'accepted'],
        ]);

        $logoPath = null;
        $coverImagePath = null;

        try {
            DB::beginTransaction();

            $slug = $validated['slug'] ?? Str::slug($validated['name']);

            $validated['slug'] = $this->uniqueIndustrySlug($slug);

            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store(
                    'industries/logos',
                    'public'
                );

                $validated['logo'] = $logoPath;
            }

            if ($request->hasFile('cover_image')) {
                $coverImagePath = $request->file('cover_image')->store(
                    'industries/covers',
                    'public'
                );

                $validated['cover_image'] = $coverImagePath;
            }

            $validated['authorization_confirmed'] = true;
            $validated['authorization_confirmed_at'] = now();
            $validated['created_by'] = auth()->id();

            $industry = Industry::create($validated);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Company page created successfully.',
                'data' => [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'slug' => $industry->slug,
                    'industry' => $industry->industry,
                    'website' => $industry->website,
                    'address' => $industry->address,
                    'company_size' => $industry->company_size,
                    'logo' => $this->publicUrl($industry->getRawOriginal('logo')),
                    'cover_image' => $this->publicUrl($industry->getRawOriginal('cover_image')),
                    'tagline' => $industry->tagline,
                    'description' => $industry->description,
                    'authorization_confirmed' => $industry->authorization_confirmed,
                    'authorization_confirmed_at' => $industry->authorization_confirmed_at,
                    'created_by' => $industry->created_by,
                    'created_at' => $industry->created_at,
                    'updated_at' => $industry->updated_at,
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->deleteFiles([$logoPath, $coverImagePath]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create company page.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }

    public function show($industryId)
    {
        $industry = Industry::find($industryId);

        if (! $industry) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found.',
            ], 404);
        }

        $followersCount = IndustryFollow::where(
            'industry_id',
            $industry->id
        )->count();

        $isFollowing = IndustryFollow::where('industry_id', $industry->id)
            ->where('user_id', auth()->id())
            ->exists();

        return response()->json([
            'success' => true,
            'message' => 'Company retrieved successfully.',
            'data' => [
                'id' => $industry->id,
                'name' => $industry->name,
                'slug' => $industry->slug,
                'industry' => $industry->industry,
                'address' => $industry->address,
                'website' => $industry->website,
                'company_size' => $industry->company_size,
                'logo' => $this->publicUrl($industry->getRawOriginal('logo')),
                'cover_image' => $this->publicUrl($industry->getRawOriginal('cover_image')),
                'tagline' => $industry->tagline,
                'description' => $industry->description,
                'followers_count' => $followersCount,
                'is_owner' => (int) $industry->created_by === (int) auth()->id(),
                'is_following' => $isFollowing,
            ],
        ], 200);
    }

    public function update(Request $request)
    {
        $subscription = $this->activeSubscription(auth()->id());

        if (! $subscription) {
            return response()->json([
                'success' => false,
                'message' => 'You need an active subscription to update your company.',
            ], 403);
        }

        if (! $this->planAllowsCompanyProfile($subscription)) {
            return response()->json([
                'success' => false,
                'message' => 'Your current plan does not include company management.',
            ], 403);
        }

        $industry = $this->ownedIndustry(auth()->id());

        if (! $industry) {
            return response()->json([
                'success' => false,
                'message' => 'Company not found.',
            ], 404);
        }

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'slug' => [
                'sometimes',
                'string',
                'max:255',
                'alpha_dash',
                Rule::unique('industries', 'slug')->ignore($industry->id),
            ],
            'industry' => ['sometimes', 'string', 'max:255'],
            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'company_size' => ['sometimes', 'nullable', 'string', 'max:100'],
            'logo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cover_image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'tagline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);

        $oldLogoPath = $industry->getRawOriginal('logo');
        $oldCoverImagePath = $industry->getRawOriginal('cover_image');

        $newLogoPath = null;
        $newCoverImagePath = null;

        try {
            DB::beginTransaction();

            if (array_key_exists('name', $validated)) {
                $slug = $validated['slug'] ?? Str::slug($validated['name']);
            } elseif (array_key_exists('slug', $validated)) {
                $slug = $validated['slug'];
            }

            if (isset($slug)) {
                $validated['slug'] = $this->uniqueIndustrySlug($slug, $industry->id);
            }

            if ($request->hasFile('logo')) {
                $newLogoPath = $request->file('logo')->store(
                    'industries/logos',
                    'public'
                );

                $validated['logo'] = $newLogoPath;
            }

            if ($request->hasFile('cover_image')) {
                $newCoverImagePath = $request->file('cover_image')->store(
                    'industries/covers',
                    'public'
                );

                $validated['cover_image'] = $newCoverImagePath;
            }

            $industry->update($validated);

            DB::commit();

            if ($newLogoPath) {
                $this->deleteFiles([$oldLogoPath]);
            }

            if ($newCoverImagePath) {
                $this->deleteFiles([$oldCoverImagePath]);
            }

            $industry->refresh();

            return response()->json([
                'success' => true,
                'message' => 'Company page updated successfully.',
                'data' => [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'slug' => $industry->slug,
                    'industry' => $industry->industry,
                    'website' => $industry->website,
                    'address' => $industry->address,
                    'company_size' => $industry->company_size,
                    'logo' => $this->publicUrl($industry->getRawOriginal('logo')),
                    'cover_image' => $this->publicUrl($industry->getRawOriginal('cover_image')),
                    'tagline' => $industry->tagline,
                    'description' => $industry->description,
                    'created_by' => $industry->created_by,
                    'created_at' => $industry->created_at,
                    'updated_at' => $industry->updated_at,
                ],
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->deleteFiles([$newLogoPath, $newCoverImagePath]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update company page.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }

    public function deleteCompany()
    {
        $userId = auth()->id();

        if (! $this->activeSubscription($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'Your subscription is not active. Please renew your subscription to delete your company page.',
            ], 403);
        }

        $industry = $this->ownedIndustry($userId);

        if (! $industry) {
            return response()->json([
                'success' => false,
                'message' => 'Company page not found.',
            ], 404);
        }

        $logoPath = $industry->getRawOriginal('logo');
        $coverImagePath = $industry->getRawOriginal('cover_image');

        $posts = Post::query()
            ->whereHas('industryLink', fn ($query) => $query->where('industry_id', $industry->id))
            ->with(['media', 'comments.replies'])
            ->get();

        $stats = [
            'deleted_posts' => $posts->count(),
            'deleted_post_media' => $posts->sum(
                fn ($post) => $post->media->count()
            ),
            'deleted_comment_images' => $posts->sum(
                fn ($post) => $post->comments->sum(
                    fn ($comment) => (int) (bool) $comment->image
                        + $comment->replies->filter(
                            fn ($reply) => (bool) $reply->image
                        )->count()
                )
            ),
        ];

        try {
            DB::beginTransaction();

            foreach ($posts as $post) {
                $post->delete();
            }

            IndustryFollow::where('industry_id', $industry->id)->delete();

            $industry->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete company page.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }

        $this->deleteFiles([$logoPath, $coverImagePath]);

        return response()->json([
            'success' => true,
            'message' => 'Company page and all related data deleted successfully.',
            'data' => array_merge(['company_id' => $industry->id], $stats),
        ], 200);
    }

    public function storePost(Request $request, IndustryMediaUploadService $mediaUploadService)
    {
        $userId = auth()->id();

        if (! $this->activeSubscription($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'Your subscription is not active. Please renew your subscription to create a post.',
            ], 403);
        }

        $industry = $this->ownedIndustry($userId);

        if (! $industry) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have a company page.',
            ], 403);
        }

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:10000'],
            'visibility' => ['required', Rule::in(self::POST_VISIBILITIES)],
            'media' => ['nullable', 'array', 'max:10'],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,webm', 'max:102400'],
        ]);

        if (! $this->mediaCountsAreValid($request, 'You can upload')) {
            return response()->json([
                'success' => false,
                'message' => $this->mediaValidationMessage($request),
            ], 422);
        }

        $content = trim($validated['content'] ?? '');

        if (blank($content) && ! $request->hasFile('media')) {
            return response()->json([
                'success' => false,
                'message' => 'Post must contain text or media.',
            ], 422);
        }

        $uploadedFiles = [];

        try {
            DB::beginTransaction();

            $post = Post::create([
                'user_id' => $userId,
                'description' => $content ?: null,
                'visibility' => $validated['visibility'],
            ]);

            PostIndustry::create([
                'post_id' => $post->id,
                'industry_id' => $industry->id,
            ]);

            foreach ($this->uploadPostMedia($request, $mediaUploadService, $uploadedFiles) as $index => $media) {
                $post->media()->create([
                    'type' => $media['type'],
                    'file_path' => $media['file_path'],
                    'order' => $index,
                ]);
            }

            DB::commit();

            $post->load('media');

            return response()->json([
                'success' => true,
                'message' => 'Company post created successfully.',
                'data' => $this->postResource($post, false),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->deleteFiles($uploadedFiles);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create company post.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }

    public function updatePost(Request $request, $postId, IndustryMediaUploadService $mediaUploadService)
    {
        $userId = auth()->id();

        $post = $this->findCompanyPost($postId);

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        if ((int) $post->user_id !== (int) $userId) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to edit this post.',
            ], 403);
        }

        if (! $this->activeSubscription($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'Your subscription is not active. Please renew your subscription to edit a post.',
            ], 403);
        }

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:10000'],
            'visibility' => ['sometimes', Rule::in(self::POST_VISIBILITIES)],
            'media' => ['nullable', 'array', 'max:10'],
            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,webm', 'max:102400'],
        ]);

        $hasNewMedia = $request->hasFile('media');

        if (
            ! array_key_exists('content', $validated)
            && ! array_key_exists('visibility', $validated)
            && ! $hasNewMedia
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Nothing to update.',
            ], 422);
        }

        if ($hasNewMedia && ! $this->mediaCountsAreValid($request, 'You can upload')) {
            return response()->json([
                'success' => false,
                'message' => $this->mediaValidationMessage($request),
            ], 422);
        }

        $existingMediaCount = $post->media()->count();

        $content = array_key_exists('content', $validated)
            ? trim($validated['content'] ?? '')
            : $post->description;

        if (blank($content) && ! $hasNewMedia && $existingMediaCount === 0) {
            return response()->json([
                'success' => false,
                'message' => 'Post must contain text or media.',
            ], 422);
        }

        $uploadedFiles = [];

        try {
            DB::beginTransaction();

            if (array_key_exists('content', $validated)) {
                $post->description = $content ?: null;
            }

            if (array_key_exists('visibility', $validated)) {
                $post->visibility = $validated['visibility'];
            }

            if ($post->isDirty()) {
                $post->save();
            }

            if ($hasNewMedia) {
                $oldMediaPaths = $post->media()->pluck('file_path')->all();

                $post->media()->delete();

                foreach ($this->uploadPostMedia($request, $mediaUploadService, $uploadedFiles) as $index => $media) {
                    $post->media()->create([
                        'type' => $media['type'],
                        'file_path' => $media['file_path'],
                        'order' => $index,
                    ]);
                }

                DB::afterCommit(fn () => $this->deleteFiles($oldMediaPaths));
            }

            DB::commit();

            $post->load('media');

            return response()->json([
                'success' => true,
                'message' => 'Company post updated successfully.',
                'data' => $this->postResource($post, false),
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->deleteFiles($uploadedFiles);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update company post.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }

    public function deletePost($postId)
    {
        $userId = auth()->id();

        $post = $this->findCompanyPost($postId);

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        if ((int) $post->user_id !== (int) $userId) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to delete this post.',
            ], 403);
        }

        if (! $this->activeSubscription($userId)) {
            return response()->json([
                'success' => false,
                'message' => 'Your subscription is not active. Please renew your subscription to delete a post.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $post->delete();

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete company post.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Company post deleted successfully.',
            'data' => [
                'post_id' => $post->id,
            ],
        ], 200);
    }

    public function indexPost(Request $request, $industryId)
    {
        $perPage = $this->perPage($request);

        $industry = Industry::find($industryId);

        if (! $industry) {
            return response()->json([
                'success' => false,
                'message' => 'Company page not found.',
            ], 404);
        }

        $userId = auth()->id();

        $posts = Post::query()
            ->with(['media', 'industryLink.industry'])
            ->whereHas('industryLink', fn ($query) => $query->where('industry_id', $industry->id))
            ->where(fn ($query) => $this->visibleCompanyPostsQuery($query, $userId))
            ->withExists([
                'likes as is_liked' => fn ($query) => $query->where('user_id', $userId),
            ])
            ->orderByDesc('posts.created_at')
            ->orderByDesc('posts.id')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Company posts fetched successfully.',
            'data' => collect($posts->items())
                ->map(fn ($post) => $this->postResource($post, true))
                ->values(),
            'pagination' => $this->pagination($posts),
        ], 200);
    }

    public function latestPosts()
    {
        $userId = auth()->id();

        $industry = $this->ownedIndustry($userId);

        if (! $industry) {
            return response()->json([
                'success' => false,
                'message' => 'Company page not found.',
            ], 404);
        }

        $posts = Post::query()
            ->with(['media', 'industryLink.industry'])
            ->whereHas('industryLink', fn ($query) => $query->where('industry_id', $industry->id))
            ->withExists([
                'likes as is_liked' => fn ($query) => $query->where('user_id', $userId),
            ])
            ->orderByDesc('posts.created_at')
            ->orderByDesc('posts.id')
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Latest company posts fetched successfully.',
            'data' => $posts
                ->map(fn ($post) => $this->postResource($post, true))
                ->values(),
        ], 200);
    }

    public function togglePostLike($postId)
    {
        $userId = auth()->id();

        $post = $this->findCompanyPost($postId);

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        if (! $this->canViewPost($post, $userId)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to like this post.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $like = PostLike::where('post_id', $post->id)
                ->where('user_id', $userId)
                ->first();

            if ($like) {
                $like->delete();

                $post->update([
                    'total_like' => max(0, (int) $post->total_like - 1),
                ]);

                $liked = false;
                $message = 'Post unliked successfully.';
            } else {
                PostLike::create([
                    'post_id' => $post->id,
                    'user_id' => $userId,
                ]);

                $post->increment('total_like');

                $liked = true;
                $message = 'Post liked successfully.';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'post_id' => $post->id,
                    'liked' => $liked,
                    'likes_count' => (int) $post->total_like,
                ],
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update post like.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }

    public function likeList(Request $request, $postId)
    {
        $perPage = $this->perPage($request);

        $post = $this->findCompanyPost($postId);

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        if (! $this->canViewPost($post, auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to view likes on this post.',
            ], 403);
        }

        $likes = PostLike::with([
            'user:id,username,first_name,last_name,profile_image',
        ])
            ->where('post_id', $post->id)
            ->whereHas('user', fn ($query) => $query->whereNull('deleted_at'))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Post liked users fetched successfully.',
            'data' => collect($likes->items())
                ->map(fn ($like) => $this->likedUserResource($like->user))
                ->filter()
                ->values(),
            'pagination' => $this->pagination($likes),
        ], 200);
    }

    public function storeComment(Request $request, $postId, OptimizedImageUploadService $imageUploadService)
    {
        $post = $this->findCompanyPost($postId);

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if (blank($validated['comment'] ?? null) && ! $request->hasFile('image')) {
            return response()->json([
                'success' => false,
                'message' => 'Comment must contain text or image.',
            ], 422);
        }

        if (! $this->canViewPost($post, auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to comment on this post.',
            ], 403);
        }

        $imagePath = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $imagePath = $imageUploadService->store(
                    $request->file('image'),
                    'industries/comments'
                );
            }

            $comment = Comment::create([
                'post_id' => $post->id,
                'user_id' => auth()->id(),
                'comment' => $validated['comment'] ?? null,
                'image' => $imagePath,
            ]);

            $post->increment('total_comment');
            $post->refresh();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Comment added successfully.',
                'data' => $this->commentResource($comment),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->deleteFiles([$imagePath]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add comment.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }

    public function commentList(Request $request, $postId)
    {
        $perPage = $this->perPage($request);

        $post = $this->findCompanyPost($postId);

        if (! $post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        if (! $this->canViewPost($post, auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to view comments on this post.',
            ], 403);
        }

        $userId = auth()->id();

        $comments = Comment::with([
            'user:id,username,first_name,last_name,title,profile_image',
        ])
            ->withExists([
                'likes as is_liked' => fn ($query) => $query->where('user_id', $userId),
            ])
            ->withCount('replies')
            ->where('post_id', $post->id)
            ->latest()
            ->paginate($perPage);

        $commentIds = collect($comments->items())->pluck('id')->values();

        $replies = $this->latestRepliesByParent($commentIds, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Post comments fetched successfully.',
            'data' => collect($comments->items())
                ->map(function ($comment) use ($replies) {
                    $resource = $this->commentResource($comment);

                    $resource['replies'] = $replies
                        ->get($comment->id, collect())
                        ->map(fn ($reply) => $this->replyResource($reply))
                        ->values();

                    return $resource;
                })
                ->values(),
            'pagination' => $this->pagination($comments),
        ], 200);
    }

    public function deleteComment($commentId)
    {
        $userId = auth()->id();

        $comment = $this->findCompanyComment($commentId);

        if (! $comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found.',
            ], 404);
        }

        $post = $comment->post;

        if (
            (int) $comment->user_id !== (int) $userId
            && (int) $post->user_id !== (int) $userId
        ) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to delete this comment.',
            ], 403);
        }

        $replyCount = $comment->replies()->count();

        $deletedCount = 1 + $replyCount;

        try {
            DB::beginTransaction();

            $comment->delete();

            $post->update([
                'total_comment' => max(0, (int) $post->total_comment - $deletedCount),
            ]);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete comment.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => $replyCount > 0
                ? 'Comment and replies deleted successfully.'
                : 'Comment deleted successfully.',
            'data' => [
                'comment_id' => $comment->id,
                'deleted_count' => $deletedCount,
                'comments_count' => (int) $post->total_comment,
            ],
        ], 200);
    }

    public function toggleCommentLike($commentId)
    {
        $userId = auth()->id();

        $comment = $this->findCompanyComment($commentId);

        if (! $comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found.',
            ], 404);
        }

        if (! $this->canViewPost($comment->post, $userId)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to like this comment.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $like = CommentLike::where('comment_id', $comment->id)
                ->where('user_id', $userId)
                ->first();

            if ($like) {
                $like->delete();

                $comment->update([
                    'like_count' => max(0, (int) $comment->like_count - 1),
                ]);

                $liked = false;
                $message = 'Comment unliked successfully.';
            } else {
                CommentLike::create([
                    'comment_id' => $comment->id,
                    'user_id' => $userId,
                ]);

                $comment->increment('like_count');

                $liked = true;
                $message = 'Comment liked successfully.';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'comment_id' => $comment->id,
                    'liked' => $liked,
                    'likes_count' => (int) $comment->like_count,
                ],
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update comment like.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }

    public function commentLikeList(Request $request, $commentId)
    {
        $perPage = $this->perPage($request);

        $comment = $this->findCompanyComment($commentId);

        if (! $comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found.',
            ], 404);
        }

        if (! $this->canViewPost($comment->post, auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to view comment likes.',
            ], 403);
        }

        $likes = CommentLike::with([
            'user:id,username,first_name,last_name,profile_image',
        ])
            ->where('comment_id', $comment->id)
            ->whereHas('user', fn ($query) => $query->whereNull('deleted_at'))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Comment liked users fetched successfully.',
            'data' => collect($likes->items())
                ->map(fn ($like) => $this->likedUserResource($like->user))
                ->filter()
                ->values(),
            'pagination' => $this->pagination($likes),
        ], 200);
    }

    public function replyComment(Request $request, $commentId, OptimizedImageUploadService $imageUploadService)
    {
        $parentComment = $this->findCompanyComment($commentId);

        if (! $parentComment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found.',
            ], 404);
        }

        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:5000'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if (blank($validated['comment'] ?? null) && ! $request->hasFile('image')) {
            return response()->json([
                'success' => false,
                'message' => 'Reply must contain text or image.',
            ], 422);
        }

        $post = $parentComment->post;

        if (! $this->canViewPost($post, auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to reply to this comment.',
            ], 403);
        }

        $imagePath = null;

        try {
            DB::beginTransaction();

            if ($request->hasFile('image')) {
                $imagePath = $imageUploadService->store(
                    $request->file('image'),
                    'industries/comments'
                );
            }

            $reply = Reply::create([
                'post_id' => $post->id,
                'comment_id' => $parentComment->id,
                'user_id' => auth()->id(),
                'reply' => $validated['comment'] ?? null,
                'image' => $imagePath,
            ]);

            $parentComment->increment('reply_count');
            $post->increment('total_comment');

            $parentComment->refresh();
            $post->refresh();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Reply added successfully.',
                'data' => $this->replyResource($reply),
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            $this->deleteFiles([$imagePath]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to add reply.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }

    public function replyList(Request $request, $commentId)
    {
        $perPage = $this->perPage($request);

        $comment = $this->findCompanyComment($commentId);

        if (! $comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found.',
            ], 404);
        }

        if (! $this->canViewPost($comment->post, auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to view replies on this comment.',
            ], 403);
        }

        $replies = Reply::with([
            'user:id,username,first_name,last_name,title,profile_image',
        ])
            ->withExists([
                'likes as is_liked' => fn ($query) => $query->where('user_id', auth()->id()),
            ])
            ->where('comment_id', $comment->id)
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Comment replies fetched successfully.',
            'data' => collect($replies->items())
                ->map(fn ($reply) => $this->replyResource($reply))
                ->values(),
            'pagination' => $this->pagination($replies),
        ], 200);
    }

    public function toggleReplyLike($replyId)
    {
        $userId = auth()->id();

        $reply = $this->findCompanyReply($replyId);

        if (! $reply) {
            return response()->json([
                'success' => false,
                'message' => 'Reply not found.',
            ], 404);
        }

        if (! $this->canViewPost($reply->post, $userId)) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to like this reply.',
            ], 403);
        }

        try {
            DB::beginTransaction();

            $like = ReplyLike::where('reply_id', $reply->id)
                ->where('user_id', $userId)
                ->first();

            if ($like) {
                $like->delete();

                $reply->update([
                    'like_count' => max(0, (int) $reply->like_count - 1),
                ]);

                $liked = false;
                $message = 'Reply unliked successfully.';
            } else {
                ReplyLike::create([
                    'reply_id' => $reply->id,
                    'user_id' => $userId,
                ]);

                $reply->increment('like_count');

                $liked = true;
                $message = 'Reply liked successfully.';
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'reply_id' => $reply->id,
                    'liked' => $liked,
                    'likes_count' => (int) $reply->like_count,
                ],
            ], 200);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update reply like.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }

    public function replyLikeList(Request $request, $replyId)
    {
        $perPage = $this->perPage($request);

        $reply = $this->findCompanyReply($replyId);

        if (! $reply) {
            return response()->json([
                'success' => false,
                'message' => 'Reply not found.',
            ], 404);
        }

        if (! $this->canViewPost($reply->post, auth()->id())) {
            return response()->json([
                'success' => false,
                'message' => 'You are not allowed to view reply likes.',
            ], 403);
        }

        $likes = ReplyLike::with([
            'user:id,username,first_name,last_name,profile_image',
        ])
            ->where('reply_id', $reply->id)
            ->whereHas('user', fn ($query) => $query->whereNull('deleted_at'))
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Reply liked users fetched successfully.',
            'data' => collect($likes->items())
                ->map(fn ($like) => $this->likedUserResource($like->user))
                ->filter()
                ->values(),
            'pagination' => $this->pagination($likes),
        ], 200);
    }

    public function toggleFollow($industryId)
    {
        $userId = auth()->id();

        $industry = Industry::find($industryId);

        if (! $industry) {
            return response()->json([
                'success' => false,
                'message' => 'Company page not found.',
            ], 404);
        }

        if ((int) $industry->created_by === (int) $userId) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot follow your own company page.',
            ], 422);
        }

        $follow = IndustryFollow::where('industry_id', $industry->id)
            ->where('user_id', $userId)
            ->first();

        if ($follow) {
            $follow->delete();

            $following = false;
            $message = 'Company page unfollowed successfully.';
        } else {
            IndustryFollow::create([
                'industry_id' => $industry->id,
                'user_id' => $userId,
            ]);

            $following = true;
            $message = 'Company page followed successfully.';
        }

        return response()->json([
            'success' => true,
            'message' => $message,
            'data' => [
                'industry_id' => $industry->id,
                'is_following' => $following,
                'followers_count' => IndustryFollow::where(
                    'industry_id',
                    $industry->id
                )->count(),
            ],
        ], 200);
    }

    /**
     * Fetch a post only when it belongs to a company page. Regular user posts
     * are invisible to every endpoint in this controller.
     */
    private function findCompanyPost($postId): ?Post
    {
        return Post::query()
            ->with('industryLink.industry')
            ->whereHas('industryLink')
            ->find($postId);
    }

    /**
     * Fetch a comment only when its post belongs to a company page.
     */
    private function findCompanyComment($commentId): ?Comment
    {
        return Comment::query()
            ->whereHas(
                'post',
                fn ($query) => $query->forIndustry()
            )
            ->with('post')
            ->find($commentId);
    }

    /**
     * Fetch a reply only when its post belongs to a company page.
     */
    private function findCompanyReply($replyId): ?Reply
    {
        return Reply::query()
            ->whereHas(
                'post',
                fn ($query) => $query->forIndustry()
            )
            ->with('post')
            ->find($replyId);
    }

    private function canViewPost(Post $post, int $userId): bool
    {
        return $post->isVisibleTo(User::find($userId));
    }

    /**
     * Restrict a company post query to posts the given user may read.
     */
    private function visibleCompanyPostsQuery($query, int $userId)
    {
        return $query->where(function ($query) use ($userId) {
            $query->where('posts.user_id', $userId)

                ->orWhere('visibility', 'public')

                ->orWhere(function ($query) use ($userId) {
                    $query->where('visibility', 'followers')
                        ->whereExists(function ($subQuery) use ($userId) {
                            $subQuery->select(DB::raw(1))
                                ->from('industry_follows')
                                ->join(
                                    'post_industry',
                                    'post_industry.industry_id',
                                    '=',
                                    'industry_follows.industry_id'
                                )
                                ->whereColumn('post_industry.post_id', 'posts.id')
                                ->where('industry_follows.user_id', $userId);
                        });
                });
        });
    }

    /**
     * The three most recent replies per comment, grouped by parent comment id.
     */
    private function latestRepliesByParent($commentIds, int $userId)
    {
        if ($commentIds->isEmpty()) {
            return collect();
        }

        $rankedReplies = DB::query()
            ->fromSub(
                Reply::query()
                    ->select([
                        'id',
                        'post_id',
                        'comment_id',
                        'user_id',
                        'reply',
                        'image',
                        'like_count',
                        'created_at',
                    ])
                    ->selectRaw(
                        'ROW_NUMBER() OVER (
                            PARTITION BY comment_id
                            ORDER BY created_at DESC, id DESC
                        ) as reply_rank'
                    )
                    ->whereIn('comment_id', $commentIds),
                'ranked_replies'
            )
            ->where('reply_rank', '<=', 3)
            ->pluck('id');

        if ($rankedReplies->isEmpty()) {
            return collect();
        }

        return Reply::with([
            'user:id,username,first_name,last_name,title,profile_image',
        ])
            ->withExists([
                'likes as is_liked' => fn ($query) => $query->where('user_id', $userId),
            ])
            ->whereIn('id', $rankedReplies)
            ->get()
            ->groupBy('comment_id');
    }

    private function uploadPostMedia(
        Request $request,
        IndustryMediaUploadService $mediaUploadService,
        array &$uploadedFiles
    ): array {
        $media = [];

        if (! $request->hasFile('media')) {
            return $media;
        }

        foreach ($request->file('media') as $file) {
            $uploaded = $mediaUploadService->upload($file);

            $uploadedFiles[] = $uploaded['file_path'];

            $media[] = $uploaded;
        }

        return $media;
    }

    /**
     * @return array{images: int, videos: int}
     */
    private function mediaCounts(Request $request): array
    {
        if (! $request->hasFile('media')) {
            return ['images' => 0, 'videos' => 0];
        }

        $counts = ['images' => 0, 'videos' => 0];

        foreach ($request->file('media') as $file) {
            $mime = $file->getMimeType() ?? '';

            if (str_starts_with($mime, 'video/')) {
                $counts['videos']++;
            } elseif (str_starts_with($mime, 'image/')) {
                $counts['images']++;
            }
        }

        return $counts;
    }

    private function mediaCountsAreValid(Request $request, string $subject): bool
    {
        ['images' => $images, 'videos' => $videos] = $this->mediaCounts($request);

        if ($videos > 1) {
            return false;
        }

        if ($videos === 1 && $images > 9) {
            return false;
        }

        return ! ($videos === 0 && $images > 10);
    }

    private function mediaValidationMessage(Request $request): string
    {
        ['images' => $images, 'videos' => $videos] = $this->mediaCounts($request);

        if ($videos > 1) {
            return 'You can upload a maximum of 1 video per post.';
        }

        if ($videos === 1 && $images > 9) {
            return 'You can upload a maximum of 9 images with 1 video.';
        }

        return 'You can upload a maximum of 10 images per post.';
    }

    private function uniqueIndustrySlug(string $slug, ?int $ignoreId = null): string
    {
        if ($slug === '') {
            $slug = 'industry';
        }

        $original = $slug;
        $counter = 1;

        while (
            Industry::where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $original.'-'.$counter++;
        }

        return $slug;
    }

    private function activeSubscription(int $userId): ?Subscription
    {
        return Subscription::where('user_id', $userId)
            ->where('status', 'active')
            ->whereNotNull('current_period_end')
            ->where('current_period_end', '>', now())
            ->whereHas('plan', fn ($query) => $query->where('status', 'active'))
            ->with('plan')
            ->latest('id')
            ->first();
    }

    private function planAllowsCompanyProfile(?Subscription $subscription): bool
    {
        return in_array(
            self::PROFILE_FEATURE,
            $subscription?->plan?->features ?? [],
            true
        );
    }

    private function ownedIndustry(int $userId): ?Industry
    {
        return Industry::where('created_by', $userId)->first();
    }

    private function perPage(Request $request): int
    {
        return max(1, min((int) $request->get('per_page', 10), 100));
    }

    private function pagination($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'last_page' => $paginator->lastPage(),
            'has_more_pages' => $paginator->hasMorePages(),
        ];
    }

    private function publicUrl(?string $path): ?string
    {
        return $path ? Storage::disk('public')->url($path) : null;
    }

    private function deleteFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if ($path && Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function postResource(Post $post, bool $withEngagement): array
    {
        $industryLink = $post->relationLoaded('industryLink')
            ? $post->industryLink
            : $post->industryLink()->first();

        $industry = $industryLink?->industry;

        $data = [
            'post_id' => $post->id,
            'company_id' => $industryLink?->industry_id,
            'created_by' => $post->user_id,
            'content' => $post->description,
            'visibility' => $post->visibility,
            'media' => $post->media
                ->map(fn ($media) => [
                    'id' => $media->id,
                    'type' => $media->type,
                    'url' => $this->publicUrl($media->file_path),
                    'sort_order' => $media->order,
                ])
                ->values(),
            'created_at' => $post->created_at,
            'updated_at' => $post->updated_at,
        ];

        if ($industry) {
            $data = array_merge([
                'company_name' => $industry->name,
                'tagline' => $industry->tagline,
                'logo' => $this->publicUrl($industry->getRawOriginal('logo')),
            ], $data);
        }

        if ($withEngagement) {
            $data['time_ago'] = $post->created_at
                ? $post->created_at->diffForHumans()
                : null;

            $data['likes_count'] = (int) $post->total_like;
            $data['comments_count'] = (int) $post->total_comment;
            $data['is_liked'] = (bool) $post->is_liked;
        }

        return $data;
    }

    private function commentResource(Comment $comment): array
    {
        return [
            'id' => $comment->id,
            'post_id' => $comment->post_id,
            'user_id' => $comment->user_id,
            'user' => $this->commentAuthorResource($comment),
            'comment' => $comment->comment,
            'image' => $this->publicUrl($comment->image),
            'likes_count' => (int) $comment->like_count,
            'replies_count' => (int) ($comment->replies_count ?? $comment->replies()->count()),
            'is_liked' => (bool) $comment->is_liked,
            'created_at' => $comment->created_at,
            'time_ago' => $comment->created_at
                ? $comment->created_at->diffForHumans()
                : null,
        ];
    }

    private function replyResource(Reply $reply): array
    {
        return [
            'id' => $reply->id,
            'post_id' => $reply->post_id,
            'comment_id' => $reply->comment_id,
            'user_id' => $reply->user_id,
            'user' => $this->commentAuthorResource($reply),
            'comment' => $reply->reply,
            'image' => $this->publicUrl($reply->image),
            'likes_count' => (int) $reply->like_count,
            'is_liked' => (bool) $reply->is_liked,
            'created_at' => $reply->created_at,
            'time_ago' => $reply->created_at
                ? $reply->created_at->diffForHumans()
                : null,
        ];
    }

    private function commentAuthorResource($model): ?array
    {
        if (! $model->relationLoaded('user') || ! $model->user) {
            return null;
        }

        return [
            'id' => $model->user->id,
            'username' => $model->user->username,
            'name' => trim(
                ($model->user->first_name ?? '').' '.($model->user->last_name ?? '')
            ),
            'title' => $model->user->title,
            'profile_image' => $model->user->profile_image_url,
        ];
    }

    private function likedUserResource($user): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'id' => $user->id,
            'username' => $user->username,
            'name' => trim(
                ($user->first_name ?? '').' '.($user->last_name ?? '')
            ),
            'profile_image' => $user->profile_image_url,
        ];
    }
}
