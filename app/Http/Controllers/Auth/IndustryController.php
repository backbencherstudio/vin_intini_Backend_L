<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\RecruiterCommentLike;
use App\Models\RecruiterPost;
use App\Models\RecruiterPostComment;
use App\Models\RecruiterPostLike;
use App\Models\Subscription;
use App\Services\OptimizedImageUploadService;
use App\Services\RecruiterMediaUploadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class IndustryController extends Controller
{
    public function store(Request $request)
    {
        $subscription = Subscription::where('user_id', auth()->id())
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>', now());
            })
            ->whereHas('plan', function ($query) {
                $query->where('status', 'active');
            })
            ->with('plan')
            ->latest('id')
            ->first();


        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'You need an active subscription to create a company page.',
            ], 403);
        }

        $features = $subscription->plan->features ?? [];

        if (!in_array('company_profile', $features, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Your current plan does not include company page creation.',
            ], 403);
        }

        // Check if user already has a company page
        $existingIndustry = Industry::where('created_by', auth()->id())->first();

        if ($existingIndustry) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a company page.',
            ], 409);
        }


        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', 'alpha_dash', 'unique:industries,slug'],
            'industry_category_id' => ['required', 'integer', 'exists:industry_categories,id'],
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

            $originalSlug = $slug;
            $counter = 1;


            while (Industry::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

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


            $validated['slug'] = $slug;

            $validated['authorization_confirmed'] = true;
            $validated['authorization_confirmed_at'] = now();

            $validated['created_by'] = auth()->id();

            $industry = Industry::create($validated);

            DB::commit();

            $industry->load('category');

            return response()->json([
                'success' => true,
                'message' => 'Company page created successfully.',
                'data' => [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'slug' => $industry->slug,

                    'industry_category_id' => $industry->industry_category_id,

                    'website' => $industry->website,
                    'address' => $industry->address,
                    'company_size' => $industry->company_size,

                    'logo' => $industry->logo
                        ? Storage::disk('public')->url($industry->logo)
                        : null,

                    'cover_image' => $industry->cover_image
                        ? Storage::disk('public')->url($industry->cover_image)
                        : null,

                    'tagline' => $industry->tagline,
                    'description' => $industry->description,

                    'authorization_confirmed' =>
                    $industry->authorization_confirmed,

                    'authorization_confirmed_at' =>
                    $industry->authorization_confirmed_at,

                    'created_by' => $industry->created_by,

                    'created_at' => $industry->created_at,
                    'updated_at' => $industry->updated_at,
                ],
            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            if ($logoPath) {
                Storage::disk('public')->delete($logoPath);
            }

            if ($coverImagePath) {
                Storage::disk('public')->delete($coverImagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create company page.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }


    public function show(Request $request)
    {
        $industry = Industry::where('created_by', auth()->id())
            ->with('category')
            ->first();

        if (!$industry) {
            return response()->json([
                'success' => false,
                'message' => 'Industry not found.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Industry retrieved successfully.',
            'data' => [
                'id' => $industry->id,
                'name' => $industry->name,
                'slug' => $industry->slug,
                'industry_category_id' => $industry->industry_category_id,
                'category' => $industry->category->category_name,
                'address' => $industry->address,
                'website' => $industry->website,
                'company_size' => $industry->company_size,

                'logo' => $industry->logo
                    ? Storage::disk('public')->url($industry->logo)
                    : null,

                'cover_image' => $industry->cover_image
                    ? Storage::disk('public')->url($industry->cover_image)
                    : null,

                'tagline' => $industry->tagline,
                'description' => $industry->description,

            ],
        ], 200);
    }


    public function update(Request $request)
    {
        $subscription = Subscription::where('user_id', auth()->id())
            ->where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('current_period_end')
                    ->orWhere('current_period_end', '>', now());
            })
            ->whereHas('plan', function ($query) {
                $query->where('status', 'active');
            })
            ->with('plan')
            ->latest('id')
            ->first();

        if (!$subscription) {
            return response()->json([
                'success' => false,
                'message' => 'You need an active subscription to update your industry.',
            ], 403);
        }

        $features = $subscription->plan->features ?? [];

        if (!in_array('company_profile', $features, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Your current plan does not include industry management.',
            ], 403);
        }


        $industry = Industry::where('created_by', auth()->id())->first();

        if (!$industry) {
            return response()->json([
                'success' => false,
                'message' => 'Industry not found.',
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

            'industry_category_id' => ['sometimes', 'integer', 'exists:industry_categories,id'],

            'website' => ['sometimes', 'nullable', 'url', 'max:255'],
            'address' => ['sometimes', 'nullable', 'string', 'max:2000'],
            'company_size' => ['sometimes', 'nullable', 'string', 'max:100'],
            'logo' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'cover_image' => ['sometimes', 'nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'tagline' => ['sometimes', 'nullable', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string', 'max:5000'],
        ]);


        $oldLogoPath = $industry->logo;
        $oldCoverImagePath = $industry->cover_image;

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

                if ($slug === '') {
                    $slug = 'industry';
                }

                $originalSlug = $slug;
                $counter = 1;

                while (
                    Industry::where('slug', $slug)
                    ->where('id', '!=', $industry->id)
                    ->exists()
                ) {
                    $slug = $originalSlug . '-' . $counter++;
                }

                $validated['slug'] = $slug;
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

            if ($newLogoPath && $oldLogoPath) {
                Storage::disk('public')->delete($oldLogoPath);
            }

            if ($newCoverImagePath && $oldCoverImagePath) {
                Storage::disk('public')->delete($oldCoverImagePath);
            }

            $industry->refresh();
            $industry->load('category');

            return response()->json([
                'success' => true,
                'message' => 'Industry updated successfully.',
                'data' => [
                    'id' => $industry->id,
                    'name' => $industry->name,
                    'slug' => $industry->slug,

                    'industry_category_id' => $industry->industry_category_id,

                    'website' => $industry->website,
                    'address' => $industry->address,
                    'company_size' => $industry->company_size,

                    'logo' => $industry->logo
                        ? Storage::disk('public')->url($industry->logo)
                        : null,

                    'cover_image' => $industry->cover_image
                        ? Storage::disk('public')->url($industry->cover_image)
                        : null,

                    'tagline' => $industry->tagline,
                    'description' => $industry->description,

                    'created_by' => $industry->created_by,

                    'created_at' => $industry->created_at,
                    'updated_at' => $industry->updated_at,
                ],
            ], 200);
        } catch (\Throwable $e) {

            DB::rollBack();


            if ($newLogoPath) {
                Storage::disk('public')->delete($newLogoPath);
            }

            if ($newCoverImagePath) {
                Storage::disk('public')->delete($newCoverImagePath);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to update industry.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }


    public function storePost(Request $request, RecruiterMediaUploadService $mediaUploadService)
    {
        $userId = auth()->id();

        $industry = Industry::where(
            'created_by',
            $userId
        )->first();

        if (!$industry) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have an industry.',
            ], 403);
        }

        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:10000'],

            'media' => ['nullable', 'array', 'max:10'],

            'media.*' => ['file', 'mimes:jpg,jpeg,png,webp,mp4,mov,webm', 'max:102400'],
        ]);

        if ($request->hasFile('media')) {

            $mediaFiles = $request->file('media');

            $videoCount = collect($mediaFiles)
                ->filter(function ($file) {
                    return str_starts_with(
                        $file->getMimeType() ?? '',
                        'video/'
                    );
                })
                ->count();

            $imageCount = collect($mediaFiles)
                ->filter(function ($file) {
                    return str_starts_with(
                        $file->getMimeType() ?? '',
                        'image/'
                    );
                })
                ->count();

            if ($videoCount > 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can upload a maximum of 1 video per post.',
                ], 422);
            }

            if ($videoCount === 1 && $imageCount > 9) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can upload a maximum of 9 images with 1 video.',
                ], 422);
            }

            if ($videoCount === 0 && $imageCount > 10) {
                return response()->json([
                    'success' => false,
                    'message' => 'You can upload a maximum of 10 images per post.',
                ], 422);
            }
        }


        $content = trim($validated['content'] ?? '');

        if (
            blank($content) &&
            !$request->hasFile('media')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Post must contain text or media.',
            ], 422);
        }


        $uploadedFiles = [];

        DB::beginTransaction();

        try {

            $post = RecruiterPost::create([
                'industry_id' => $industry->id,
                'created_by' => $userId,
                'content' => $content ?: null,
            ]);

            if ($request->hasFile('media')) {

                foreach ($request->file('media') as $index => $file) {

                    $media = $mediaUploadService->upload($file);

                    $uploadedFiles[] = $media['file_path'];

                    $post->media()->create([
                        'type' => $media['type'],
                        'path' => $media['file_path'],
                        'sort_order' => $index,
                    ]);
                }
            }


            DB::commit();

            $post->load('media');

            return response()->json([
                'success' => true,
                'message' => 'Recruiter post created successfully.',

                'data' => [
                    'id' => $post->id,

                    'industry_id' => $post->industry_id,

                    'created_by' => $post->created_by,

                    'content' => $post->content,

                    'media' => $post->media->map(function ($media) {

                        return [
                            'id' => $media->id,

                            'type' => $media->type,

                            'url' => Storage::disk('public')
                                ->url($media->path),

                            'sort_order' => $media->sort_order,
                        ];
                    })->values(),

                    'created_at' => $post->created_at,

                    'updated_at' => $post->updated_at,
                ],
            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            foreach ($uploadedFiles as $file) {

                if (Storage::disk('public')->exists($file)) {
                    Storage::disk('public')->delete($file);
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to create recruiter post.',

                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,

            ], 500);
        }
    }


    public function indexPost(Request $request)
    {
        $perPage = min(
            (int) $request->get('per_page', 10),
            100
        );

        $posts = RecruiterPost::with([
            'media' => function ($query) {
                $query->orderBy('sort_order');
            },
            'industry',
        ])
            ->withExists([
                'likes as is_liked' => function ($query) {
                    $query->where(
                        'user_id',
                        auth()->id()
                    );
                },
            ])
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,

            'message' => 'Recruiter posts fetched successfully.',

            'data' => collect($posts->items())->map(function ($post) {

                return [
                    'id' => $post->id,
                    'industry_name' => $post->industry?->name,
                    'tagline' => $post->industry?->tagline,
                    'time_ago' => $post->created_at
                        ? $post->created_at->diffForHumans()
                        : null,

                    'logo' => $post->industry?->logo
                        ? Storage::disk('public')->url(
                            $post->industry->logo
                        )
                        : null,

                    'content' => $post->content,



                    'media' => $post->media
                        ->map(function ($media) {

                            return [
                                'id' => $media->id,

                                'type' => $media->type,

                                'url' => Storage::disk('public')
                                    ->url($media->path),

                                'sort_order' => $media->sort_order,
                            ];
                        })
                        ->values(),
                    'likes_count' => $post->likes_count,

                    'comments_count' => $post->comments_count,

                    'is_liked' => (bool) $post->is_liked,
                ];
            })->values(),

            'pagination' => [
                'current_page' => $posts->currentPage(),

                'per_page' => $posts->perPage(),

                'total' => $posts->total(),

                'last_page' => $posts->lastPage(),

                'has_more_pages' => $posts->hasMorePages(),
            ],

        ], 200);
    }


    public function latestPosts()
    {
        $userId = auth()->id();

        $industry = Industry::where('created_by', $userId)->first();

        if (!$industry) {
            return response()->json([
                'success' => false,
                'message' => 'Company page not found.',
            ], 404);
        }

        $posts = RecruiterPost::with([
            'media' => function ($query) {
                $query->orderBy('sort_order');
            },
            'industry',
        ])
            ->where('industry_id', $industry->id)

            ->withExists([
                'likes as is_liked' => function ($query) use ($userId) {
                    $query->where('user_id', $userId);
                },
            ])
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'success' => true,

            'message' => 'Latest company posts fetched successfully.',

            'data' => $posts->map(function ($post) {

                return [
                    'id' => $post->id,

                    'industry_name' => $post->industry?->name,

                    'tagline' => $post->industry?->tagline,

                    'time_ago' => $post->created_at
                        ? $post->created_at->diffForHumans()
                        : null,

                    'logo' => $post->industry?->logo
                        ? Storage::disk('public')->url(
                            $post->industry->logo
                        )
                        : null,

                    'content' => $post->content,

                    'media' => $post->media
                        ->map(function ($media) {

                            return [
                                'id' => $media->id,

                                'type' => $media->type,

                                'url' => Storage::disk('public')
                                    ->url($media->path),

                                'sort_order' => $media->sort_order,
                            ];
                        })
                        ->values(),

                    'likes_count' => $post->likes_count,

                    'comments_count' => $post->comments_count,

                    'is_liked' => (bool) $post->is_liked,
                ];
            })->values(),
        ], 200);
    }


    public function togglePostLike($postId)
    {
        $userId = auth()->id();

        $post = RecruiterPost::find($postId);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        DB::beginTransaction();

        try {

            $like = RecruiterPostLike::where('post_id', $postId)
                ->where('user_id', $userId)
                ->first();

            if ($like) {

                $like->delete();

                $post->decrement('likes_count');

                $liked = false;

                $message = 'Post unliked successfully.';
            } else {

                RecruiterPostLike::create([
                    'post_id' => $postId,
                    'user_id' => $userId,
                ]);

                $post->increment('likes_count');

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

                    'likes_count' => $post->likes_count,
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
        $perPage = min(
            (int) $request->get('per_page', 10),
            100
        );

        $post = RecruiterPost::find($postId);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        $likes = RecruiterPostLike::with([
            'user:id,username,first_name,last_name,profile_image'
        ])
            ->where('post_id', $postId)

            ->whereHas('user', function ($query) {
                $query->whereNull('deleted_at');
            })

            ->latest()

            ->paginate($perPage);

        $users = collect($likes->items())
            ->map(function ($like) {

                if (!$like->user) {
                    return null;
                }

                return [
                    'id' => $like->user->id,

                    'username' => $like->user->username,

                    'name' => trim(
                        ($like->user->first_name ?? '') .
                            ' ' .
                            ($like->user->last_name ?? '')
                    ),

                    'profile_image' =>
                    $like->user->profile_image_url,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'success' => true,

            'message' => 'Post liked users fetched successfully.',

            'data' => $users,

            'pagination' => [
                'current_page' => $likes->currentPage(),

                'per_page' => $likes->perPage(),

                'total' => $likes->total(),

                'last_page' => $likes->lastPage(),

                'has_more_pages' => $likes->hasMorePages(),
            ],
        ], 200);
    }


    public function storeComment(Request $request, $postId, OptimizedImageUploadService $imageUploadService)
    {
        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:5000'],

            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if (
            blank($validated['comment'] ?? null)
            && !$request->hasFile('image')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Comment must contain text or image.',
            ], 422);
        }

        $post = RecruiterPost::find($postId);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        DB::beginTransaction();

        try {

            $imagePath = null;

            if ($request->hasFile('image')) {

                $imagePath = $imageUploadService->store(
                    $request->file('image'),
                    'recruiters/comments'
                );
            }

            $comment = RecruiterPostComment::create([
                'post_id' => $post->id,
                'user_id' => auth()->id(),
                'parent_id' => null,
                'comment' => $validated['comment'] ?? null,
                'image' => $imagePath,
            ]);

            $post->increment('comments_count');

            DB::commit();

            $comment->load('user');

            return response()->json([
                'success' => true,

                'message' => 'Comment added successfully.',

                'data' => [
                    'id' => $comment->id,

                    'post_id' => $comment->post_id,

                    'user_id' => $comment->user_id,

                    'comment' => $comment->comment,

                    'image' => $comment->image
                        ? Storage::disk('public')
                        ->url($comment->image)
                        : null,

                    'likes_count' => $comment->likes_count,

                    'created_at' => $comment->created_at,

                    'time_ago' => $comment->created_at
                        ->diffForHumans(),
                ],
            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            if (
                $imagePath &&
                Storage::disk('public')->exists($imagePath)
            ) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'success' => false,

                'message' => 'Failed to add comment.',

                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }


    public function replyComment(Request $request, $commentId, OptimizedImageUploadService $imageUploadService)
    {
        $validated = $request->validate([
            'comment' => ['nullable', 'string', 'max:5000'],

            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);

        if (
            blank($validated['comment'] ?? null)
            && !$request->hasFile('image')
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Reply must contain text or image.',
            ], 422);
        }

        $parentComment = RecruiterPostComment::find(
            $commentId
        );

        if (!$parentComment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found.',
            ], 404);
        }

        DB::beginTransaction();

        try {

            $imagePath = null;

            if ($request->hasFile('image')) {

                $imagePath = $imageUploadService->store(
                    $request->file('image'),
                    'recruiters/comments'
                );
            }

            $reply = RecruiterPostComment::create([
                'post_id' => $parentComment->post_id,

                'user_id' => auth()->id(),

                'parent_id' => $parentComment->id,

                'comment' => $validated['comment'] ?? null,

                'image' => $imagePath,
            ]);

            $parentComment->post
                ->increment('comments_count');

            DB::commit();

            $reply->load('user');

            return response()->json([
                'success' => true,

                'message' => 'Reply added successfully.',

                'data' => [
                    'id' => $reply->id,

                    'post_id' => $reply->post_id,

                    'parent_id' => $reply->parent_id,

                    'user_id' => $reply->user_id,

                    'comment' => $reply->comment,

                    'image' => $reply->image
                        ? Storage::disk('public')
                        ->url($reply->image)
                        : null,

                    'likes_count' => $reply->likes_count,

                    'created_at' => $reply->created_at,

                    'time_ago' => $reply->created_at
                        ->diffForHumans(),
                ],
            ], 201);
        } catch (\Throwable $e) {

            DB::rollBack();

            if (
                $imagePath &&
                Storage::disk('public')->exists($imagePath)
            ) {
                Storage::disk('public')->delete($imagePath);
            }

            return response()->json([
                'success' => false,

                'message' => 'Failed to add reply.',

                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }


    public function toggleCommentLike($commentId)
    {
        $userId = auth()->id();

        $comment = RecruiterPostComment::find($commentId);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found.',
            ], 404);
        }

        DB::beginTransaction();

        try {

            $like = RecruiterCommentLike::where(
                'comment_id',
                $commentId
            )
                ->where(
                    'user_id',
                    $userId
                )
                ->first();

            if ($like) {

                $like->delete();

                $comment->update([
                    'likes_count' => max(
                        0,
                        $comment->likes_count - 1
                    ),
                ]);

                $liked = false;

                $message = 'Comment unliked successfully.';
            } else {

                RecruiterCommentLike::create([
                    'comment_id' => $commentId,
                    'user_id' => $userId,
                ]);

                $comment->increment('likes_count');

                $liked = true;

                $message = 'Comment liked successfully.';
            }

            DB::commit();

            $comment->refresh();

            return response()->json([
                'success' => true,

                'message' => $message,

                'data' => [
                    'comment_id' => $comment->id,

                    'liked' => $liked,

                    'likes_count' => $comment->likes_count,
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
        $perPage = min(
            (int) $request->get('per_page', 10),
            100
        );

        $comment = RecruiterPostComment::find($commentId);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found.',
            ], 404);
        }

        $likes = RecruiterCommentLike::with([
            'user:id,username,first_name,last_name,profile_image'
        ])
            ->where('comment_id', $commentId)

            ->whereHas('user', function ($query) {
                $query->whereNull('deleted_at');
            })

            ->latest()

            ->paginate($perPage);

        $users = collect($likes->items())
            ->map(function ($like) {

                if (!$like->user) {
                    return null;
                }

                return [
                    'id' => $like->user->id,

                    'username' => $like->user->username,

                    'name' => trim(
                        ($like->user->first_name ?? '') .
                            ' ' .
                            ($like->user->last_name ?? '')
                    ),

                    'profile_image' => $like->user->profile_image_url,
                ];
            })
            ->filter()
            ->values();

        return response()->json([
            'success' => true,

            'message' => 'Comment liked users fetched successfully.',

            'data' => $users,

            'pagination' => [
                'current_page' => $likes->currentPage(),

                'per_page' => $likes->perPage(),

                'total' => $likes->total(),

                'last_page' => $likes->lastPage(),

                'has_more_pages' => $likes->hasMorePages(),
            ],
        ], 200);
    }


    public function commentList(Request $request, $postId)
    {
        $perPage = min(
            (int) $request->get('per_page', 10),
            100
        );

        $post = RecruiterPost::find($postId);

        if (!$post) {
            return response()->json([
                'success' => false,
                'message' => 'Post not found.',
            ], 404);
        }

        $comments = RecruiterPostComment::with([
            'user:id,username,first_name,last_name,title,profile_image',
        ])
            ->withExists([
                'likes as is_liked' => function ($query) {
                    $query->where(
                        'user_id',
                        auth()->id()
                    );
                },
            ])
            ->withCount('replies')
            ->where('post_id', $postId)
            ->whereNull('parent_id')
            ->latest()
            ->paginate($perPage);

        $commentIds = collect($comments->items())
            ->pluck('id')
            ->values();

        $replies = collect();

        if ($commentIds->isNotEmpty()) {

            $rankedReplies = DB::query()
                ->fromSub(
                    RecruiterPostComment::query()
                        ->select([
                            'id',
                            'post_id',
                            'parent_id',
                            'user_id',
                            'comment',
                            'image',
                            'likes_count',
                            'created_at',
                        ])
                        ->selectRaw(
                            'ROW_NUMBER() OVER (
                            PARTITION BY parent_id
                            ORDER BY created_at DESC, id DESC
                        ) as reply_rank'
                        )
                        ->whereIn(
                            'parent_id',
                            $commentIds
                        ),
                    'ranked_replies'
                )
                ->where('reply_rank', '<=', 3)
                ->get();

            $replyIds = $rankedReplies
                ->pluck('id')
                ->values();

            if ($replyIds->isNotEmpty()) {

                $replies = RecruiterPostComment::with([
                    'user:id,username,first_name,last_name,title,profile_image',
                ])
                    ->withExists([
                        'likes as is_liked' => function ($query) {
                            $query->where(
                                'user_id',
                                auth()->id()
                            );
                        },
                    ])
                    ->whereIn('id', $replyIds)
                    ->get()
                    ->groupBy('parent_id');
            }
        }


        $data = collect($comments->items())
            ->map(function ($comment) use ($replies) {

                return [
                    'id' => $comment->id,

                    'post_id' =>
                    $comment->post_id,

                    'parent_id' =>
                    $comment->parent_id,

                    'user' => $comment->user ? [
                        'id' =>
                        $comment->user->id,

                        'username' =>
                        $comment->user->username,

                        'name' => trim(
                            ($comment->user->first_name ?? '') .
                                ' ' .
                                ($comment->user->last_name ?? '')
                        ),

                        'title' =>
                        $comment->user->title,

                        'profile_image' =>
                        $comment->user->profile_image_url,
                    ] : null,

                    'comment' =>
                    $comment->comment,

                    'image' => $comment->image
                        ? Storage::disk('public')->url(
                            $comment->image
                        )
                        : null,

                    'likes_count' =>
                    $comment->likes_count,

                    'is_liked' =>
                    (bool) $comment->is_liked,

                    'replies_count' =>
                    $comment->replies_count,

                    'created_at' =>
                    $comment->created_at,

                    'time_ago' =>
                    $comment->created_at
                        ? $comment->created_at->diffForHumans()
                        : null,

                    'replies' =>
                    $replies
                        ->get($comment->id, collect())
                        ->sortByDesc(function ($reply) {
                            return $reply->created_at;
                        })
                        ->values()
                        ->map(function ($reply) {

                            return [
                                'id' =>
                                $reply->id,

                                'post_id' =>
                                $reply->post_id,

                                'parent_id' =>
                                $reply->parent_id,

                                'user' => $reply->user ? [
                                    'id' =>
                                    $reply->user->id,

                                    'username' =>
                                    $reply->user->username,

                                    'name' => trim(
                                        ($reply->user->first_name ?? '') .
                                            ' ' .
                                            ($reply->user->last_name ?? '')
                                    ),

                                    'title' =>
                                    $reply->user->title,

                                    'profile_image' =>
                                    $reply->user->profile_image_url,
                                ] : null,

                                'comment' =>
                                $reply->comment,

                                'image' => $reply->image
                                    ? Storage::disk('public')->url(
                                        $reply->image
                                    )
                                    : null,

                                'likes_count' =>
                                $reply->likes_count,

                                'is_liked' =>
                                (bool) $reply->is_liked,

                                'created_at' =>
                                $reply->created_at,

                                'time_ago' =>
                                $reply->created_at
                                    ? $reply->created_at->diffForHumans()
                                    : null,
                            ];
                        }),
                ];
            })
            ->values();


        return response()->json([
            'success' => true,

            'message' =>
            'Post comments fetched successfully.',

            'data' => $data,

            'pagination' => [
                'current_page' =>
                $comments->currentPage(),

                'per_page' =>
                $comments->perPage(),

                'total' =>
                $comments->total(),

                'last_page' =>
                $comments->lastPage(),

                'has_more_pages' =>
                $comments->hasMorePages(),
            ],
        ], 200);
    }


    public function replyList(Request $request, $commentId)
    {
        $perPage = min(
            (int) $request->get('per_page', 10),
            100
        );

        $comment = RecruiterPostComment::whereNull('parent_id')
            ->find($commentId);

        if (!$comment) {
            return response()->json([
                'success' => false,
                'message' => 'Comment not found.',
            ], 404);
        }

        $replies = RecruiterPostComment::with([
            'user:id,username,first_name,last_name,title,profile_image',
        ])
            ->withExists([
                'likes as is_liked' => function ($query) {
                    $query->where(
                        'user_id',
                        auth()->id()
                    );
                },
            ])
            ->where(
                'parent_id',
                $commentId
            )
            ->latest()
            ->paginate($perPage);

        $data = collect($replies->items())
            ->map(function ($reply) {

                return [
                    'id' => $reply->id,

                    'post_id' =>
                    $reply->post_id,

                    'parent_id' =>
                    $reply->parent_id,

                    'user' => $reply->user ? [
                        'id' =>
                        $reply->user->id,

                        'username' =>
                        $reply->user->username,

                        'name' => trim(
                            ($reply->user->first_name ?? '') .
                                ' ' .
                                ($reply->user->last_name ?? '')
                        ),

                        'title' =>
                        $reply->user->title,

                        'profile_image' =>
                        $reply->user->profile_image_url,
                    ] : null,

                    'comment' =>
                    $reply->comment,

                    'image' => $reply->image
                        ? Storage::disk('public')->url(
                            $reply->image
                        )
                        : null,

                    'time_ago' =>
                    $reply->created_at
                        ? $reply->created_at->diffForHumans()
                        : null,

                    'is_liked' =>
                    (bool) $reply->is_liked,

                    'likes_count' =>
                    $reply->likes_count,

                ];
            })
            ->values();


        return response()->json([
            'success' => true,

            'message' =>
            'Comment replies fetched successfully.',

            'data' => $data,

            'pagination' => [
                'current_page' =>
                $replies->currentPage(),

                'per_page' =>
                $replies->perPage(),

                'total' =>
                $replies->total(),

                'last_page' =>
                $replies->lastPage(),

                'has_more_pages' =>
                $replies->hasMorePages(),
            ],
        ], 200);
    }
}
