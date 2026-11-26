<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Experience;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'all');

        $companies = Company::query()
            ->when($search, function ($query, $search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($status === 'pending', function ($query): void {
                $query->where('is_verified', false);
            })
            ->when($status === 'verified', function ($query): void {
                $query->where('is_verified', true);
            })
            ->withCount('experiences as total_experiences')
            ->when(
                $status === 'pending',
                fn ($query) => $query->orderBy('created_at', 'desc'),
                fn ($query) => $query->orderBy('is_verified', 'asc')->orderBy('total_experiences', 'desc')
            )
            ->paginate(20);

        $data = $companies->getCollection()->map(fn ($company, $index): array => [
            'sl_no' => (($companies->currentPage() - 1) * $companies->perPage()) + $index + 1,
            'id' => $company->id,
            'name' => $company->name,
            'is_verified' => (bool) $company->is_verified,
            'total_experiences' => $company->total_experiences,
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => [
                'pending_count' => Company::where('is_verified', false)->count(),
                'verified_count' => Company::where('is_verified', true)->count(),
            ],
            'pagination' => [
                'current_page' => $companies->currentPage(),
                'per_page' => $companies->perPage(),
                'total' => $companies->total(),
                'last_page' => $companies->lastPage(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'is_verified' => 'sometimes|boolean',
        ]);

        $cleanName = preg_replace('/\s+/', ' ', trim($validated['name'])) ?? trim($validated['name']);

        $duplicate = Company::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($cleanName)])
            ->first();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'An organization with this name already exists.',
            ], 422);
        }

        $company = Company::create([
            'name' => $cleanName,
            'is_verified' => $request->has('is_verified') ? (bool) $validated['is_verified'] : true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Organization added successfully.',
            'data' => $company,
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'is_verified' => 'required|boolean',
        ]);

        $company = Company::findOrFail($id);
        $company->update([
            'is_verified' => (bool) $validated['is_verified'],
        ]);

        return response()->json([
            'success' => true,
            'message' => $company->is_verified
                ? 'Organization approved and added to public suggestions.'
                : 'Organization hidden from public suggestions.',
            'data' => $company,
        ]);
    }

    public function rename(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'is_verified' => 'sometimes|boolean',
        ]);

        $company = Company::findOrFail($id);
        $cleanName = preg_replace('/\s+/', ' ', trim($validated['name'])) ?? trim($validated['name']);

        $duplicate = Company::query()
            ->where('id', '!=', $company->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($cleanName)])
            ->first();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'An organization with this name already exists. Please merge into it instead.',
            ], 422);
        }

        $updateData = ['name' => $cleanName];
        if (array_key_exists('is_verified', $validated)) {
            $updateData['is_verified'] = (bool) $validated['is_verified'];
        }

        $company->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Organization updated successfully.',
            'data' => $company,
        ]);
    }

    public function merge(Request $request, $id)
    {
        $validated = $request->validate([
            'target_company_id' => 'required|integer|exists:companies,id',
        ]);

        $source = Company::findOrFail($id);
        $target = Company::findOrFail($validated['target_company_id']);

        if ($source->id === $target->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot merge an organization into itself.',
            ], 422);
        }

        DB::transaction(function () use ($source, $target): void {
            Experience::where('company_id', $source->id)
                ->update(['company_id' => $target->id]);

            UserProfile::where('current_position_id', $source->id)
                ->update(['current_position_id' => $target->id]);

            $source->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "Merged \"{$source->name}\" into \"{$target->name}\".",
        ]);
    }

    public function destroy($id)
    {
        $company = Company::findOrFail($id);

        $hasLinks = Experience::where('company_id', $company->id)->exists()
            || UserProfile::where('current_position_id', $company->id)->exists();

        if ($hasLinks) {
            return response()->json([
                'success' => false,
                'message' => 'This organization has linked experiences. Merge it into a verified organization instead.',
            ], 422);
        }

        $company->delete();

        return response()->json([
            'success' => true,
            'message' => 'Organization deleted successfully.',
        ]);
    }
}
