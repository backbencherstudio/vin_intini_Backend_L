<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experience extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'skills_id' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function getSkillsDataAttribute()
    {
        if (! $this->skills_id) {
            return [];
        }

        return Skill::whereIn('id', $this->skills_id)->get(['id', 'name']);
    }

    protected $appends = ['skills_data'];

    public function getFormattedStartDateAttribute()
    {
        return $this->start_date ? $this->start_date->format('M Y') : null;
    }

    public function getFormattedEndDateAttribute()
    {
        if ($this->is_current) {
            return 'Present';
        }

        return $this->end_date ? $this->end_date->format('M Y') : null;
    }
}
