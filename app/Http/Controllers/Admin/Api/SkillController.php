<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Api;

use App\Http\Controllers\Controller;
use App\Models\Education;
use App\Models\Experience;
use App\Models\Skill;
use App\Models\UserProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SkillController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $status = $request->input('status', 'all');

        $skills = Skill::query()
            ->when($search, function ($query, $search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->when($status === 'pending', function ($query): void {
                $query->where('is_verified', false);
            })
            ->when($status === 'verified', function ($query): void {
                $query->where('is_verified', true);
            })
            ->when(
                $status === 'pending',
                fn ($query) => $query->orderBy('created_at', 'desc'),
                fn ($query) => $query->orderBy('is_verified', 'asc')->orderBy('name', 'asc')
            )
            ->paginate(20);

        $data = $skills->getCollection()->map(fn ($skill, $index): array => [
            'sl_no' => (($skills->currentPage() - 1) * $skills->perPage()) + $index + 1,
            'id' => $skill->id,
            'name' => $skill->name,
            'is_verified' => (bool) $skill->is_verified,
            'usage_count' => $this->countSkillUsage((int) $skill->id),
        ]);

        return response()->json([
            'success' => true,
            'data' => $data,
            'summary' => [
                'pending_count' => Skill::where('is_verified', false)->count(),
                'verified_count' => Skill::where('is_verified', true)->count(),
            ],
            'pagination' => [
                'current_page' => $skills->currentPage(),
                'per_page' => $skills->perPage(),
                'total' => $skills->total(),
                'last_page' => $skills->lastPage(),
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

        $duplicate = Skill::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($cleanName)])
            ->first();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'A skill with this name already exists.',
            ], 422);
        }

        $skill = Skill::create([
            'name' => $cleanName,
            'is_verified' => $request->has('is_verified') ? (bool) $validated['is_verified'] : true,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Skill added successfully.',
            'data' => $skill,
        ], 201);
    }

    public function updateStatus(Request $request, $id)
    {
        $validated = $request->validate([
            'is_verified' => 'required|boolean',
        ]);

        $skill = Skill::findOrFail($id);
        $skill->update([
            'is_verified' => (bool) $validated['is_verified'],
        ]);

        return response()->json([
            'success' => true,
            'message' => $skill->is_verified
                ? 'Skill approved and added to public suggestions.'
                : 'Skill hidden from public suggestions.',
            'data' => $skill,
        ]);
    }

    public function rename(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|min:2|max:255',
            'is_verified' => 'sometimes|boolean',
        ]);

        $skill = Skill::findOrFail($id);
        $cleanName = preg_replace('/\s+/', ' ', trim($validated['name'])) ?? trim($validated['name']);

        $duplicate = Skill::query()
            ->where('id', '!=', $skill->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($cleanName)])
            ->first();

        if ($duplicate) {
            return response()->json([
                'success' => false,
                'message' => 'A skill with this name already exists. Please merge into it instead.',
            ], 422);
        }

        $updateData = ['name' => $cleanName];
        if (array_key_exists('is_verified', $validated)) {
            $updateData['is_verified'] = (bool) $validated['is_verified'];
        }

        $skill->update($updateData);

        return response()->json([
            'success' => true,
            'message' => 'Skill updated successfully.',
            'data' => $skill,
        ]);
    }

    public function merge(Request $request, $id)
    {
        $validated = $request->validate([
            'target_skill_id' => 'required|integer|exists:skills,id',
        ]);

        $source = Skill::findOrFail($id);
        $target = Skill::findOrFail($validated['target_skill_id']);

        if ($source->id === $target->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot merge a skill into itself.',
            ], 422);
        }

        DB::transaction(function () use ($source, $target): void {
            $this->replaceSkillIdInModel(Experience::class, (int) $source->id, (int) $target->id);
            $this->replaceSkillIdInModel(Education::class, (int) $source->id, (int) $target->id);
            $this->replaceSkillIdInModel(UserProfile::class, (int) $source->id, (int) $target->id);

            $source->delete();
        });

        return response()->json([
            'success' => true,
            'message' => "Merged \"{$source->name}\" into \"{$target->name}\".",
        ]);
    }

    public function destroy($id)
    {
        $skill = Skill::findOrFail($id);

        if ($this->countSkillUsage((int) $skill->id) > 0) {
            return response()->json([
                'success' => false,
                'message' => 'This skill is used by profiles or experiences. Merge it into a verified skill instead.',
            ], 422);
        }

        $skill->delete();

        return response()->json([
            'success' => true,
            'message' => 'Skill deleted successfully.',
        ]);
    }

    private function countSkillUsage(int $skillId): int
    {
        return Experience::whereJsonContains('skills_id', $skillId)->count()
            + Education::whereJsonContains('skills_id', $skillId)->count()
            + UserProfile::whereJsonContains('skills_id', $skillId)->count();
    }

    /**
     * @param  class-string<Experience|Education|UserProfile>  $modelClass
     */
    private function replaceSkillIdInModel(string $modelClass, int $sourceId, int $targetId): void
    {
        $modelClass::query()
            ->whereJsonContains('skills_id', $sourceId)
            ->each(function ($record) use ($sourceId, $targetId): void {
                $ids = collect($record->skills_id ?? [])
                    ->map(fn ($item) => (int) $item === $sourceId ? $targetId : (int) $item)
                    ->unique()
                    ->values()
                    ->all();

                $record->update(['skills_id' => $ids]);
            });
    }
}
