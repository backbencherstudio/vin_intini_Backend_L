<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    protected $guarded = [];

    protected $casts = [
        'skills_id' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current' => 'boolean'
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getSkillsDataAttribute()
    {
        if (!$this->skills_id) return [];
        return Skill::whereIn('id', $this->skills_id)->get(['id', 'name']);
    }

    protected $appends = ['skills_data'];
}
