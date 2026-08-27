<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use App\Models\Subscription;
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
                'message' => 'You need an active subscription to create an industry.',
            ], 403);
        }

        $features = $subscription->plan->features ?? [];

        if (!in_array('company_profile', $features, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Your current plan does not include industry creation.',
            ], 403);
        }

        // Check if user already has an industry
        $existingIndustry = Industry::where('created_by', auth()->id())->first();

        if ($existingIndustry) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an industry.',
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
                'message' => 'Industry created successfully.',
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
                'message' => 'Failed to create industry.',
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

                'category' => $industry->category->category_name,
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

        // Get authenticated user's industry
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
}
