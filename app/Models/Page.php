<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Page extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'vision',
        'mission',
        'strategy',
        'founder_info',
        'team_members',
        'features_videos',
        'faqs',
        'is_active'
    ];

    protected $casts = [
        'founder_info' => 'array',
        'team_members' => 'array',
        'features_videos' => 'array',
        'faqs' => 'array',
    ];
}
