<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Industry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class IndustryController extends Controller
{
    public function store(Request $request)
    {
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
            'authorization_confirmed' => ['required', 'accepted'],
        ]);

        try {
            DB::beginTransaction();

            $slug = $validated['slug'] ?? Str::slug($validated['name']);

            $originalSlug = $slug;
            $counter = 1;

            while (Industry::where('slug', $slug)->exists()) {
                $slug = $originalSlug . '-' . $counter++;
            }

            if ($request->hasFile('logo')) {
                $validated['logo'] = $request->file('logo')->store(
                    'industries/logos',
                    'public'
                );
            }

            if ($request->hasFile('cover_image')) {
                $validated['cover_image'] = $request->file('cover_image')->store(
                    'industries/covers',
                    'public'
                );
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

            return response()->json([
                'success' => false,
                'message' => 'Failed to create industry.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }
}
