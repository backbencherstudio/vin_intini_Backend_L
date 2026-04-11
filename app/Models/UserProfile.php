<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'about'
    ];

    protected $casts = [
        'profession' => 'array',
        'interests' => 'array',
    ];
}
