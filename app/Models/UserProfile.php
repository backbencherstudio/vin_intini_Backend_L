<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserProfile extends Model
{
    protected $fillable = [
        'user_id',
        'country',
        'state_id',
        'address',
        'postal_code',
        'profession',
        'highest_degree',
        'study_category',
        'field_study',
        'institution',
        'graduation_year',
        'interests',
        'skills_id',
        'current_position_id',
        'current_institute_id',
        'about',

        'notify_jobs',
        'notify_publications',
        'notify_residency',
        'notify_offers',

        'privacy_profile_activity',
        'privacy_profile_visibility',
    ];

    protected $casts = [
        'profession' => 'array',
        'interests' => 'array',
        'skills_id' => 'array',

        'notify_jobs' => 'boolean',
        'notify_publications' => 'boolean',
        'notify_residency' => 'boolean',
        'notify_offers' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // public function currentPosition(): BelongsTo
    // {
    //     return $this->belongsTo(Experience::class, 'current_position_id');
    // }

    public function currentPosition(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'current_position_id');
    }

    public function currentInstitute(): BelongsTo
    {
        return $this->belongsTo(Institution::class, 'current_institute_id');
    }

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function state()
    {
        return $this->belongsTo(State::class, 'state_id');
    }
}
