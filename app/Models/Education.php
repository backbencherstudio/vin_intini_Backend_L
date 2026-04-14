<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Education extends Model
{
    use HasFactory;

    protected $table = 'educations';

    protected $fillable = [
        'user_id',
        'institution_id',
        'degree',
        'field_study',
        'start_month',
        'start_year',
        'end_month',
        'end_year',
        'grade',
        'description',
        'activities',
        'is_current',
        'skills_id',
    ];

    protected $casts = [
        'skills_id' => 'array',
        'is_current' => 'boolean',
    ];

    protected $appends = [
        'skills_data',
        'status',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function getSkillsDataAttribute(): array
    {
        if (! $this->skills_id) {
            return [];
        }

        return Skill::query()
            ->select(['id', 'name'])
            ->whereIn('id', $this->skills_id)
            ->orderBy('name')
            ->get()
            ->all();
    }

    public function getStatusAttribute(): string
    {
        return $this->is_current ? 'Present' : 'Complete';
    }
}
