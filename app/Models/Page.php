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
        'founder_photo', 
        'founder_bio',
        'what_we_do_image',
        'team_members',
        'features_videos',
        'faqs',
        'is_active'
    ];

    protected $casts = [
        'team_members' => 'array',
        'features_videos' => 'array',
        'faqs' => 'array',
    ];
}
