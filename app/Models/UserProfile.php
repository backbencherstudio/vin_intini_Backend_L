<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'country',
        'postal_code',
        'profession',
        'highest_degree',
        'study_category',
        'study_subcategory',
        'institution',
        'graduation_year',
        'interests',
        'skills_id',
        'current_position_id',
        'current_institute_id',
        'about',
    ];

    protected $casts = [
        'profession' => 'array',
        'interests' => 'array',
        'skills_id' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function currentPosition(): BelongsTo
    {
        return $this->belongsTo(Experience::class, 'current_position_id');
    }

    public function currentInstitute(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'current_institute_id');
    }
}
