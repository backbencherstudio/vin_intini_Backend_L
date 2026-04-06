<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Group extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'logo', 
        'cover_photo',
        'industry',
        'location',
        'rules',
        'creator_id',
        'type',
        'discoverability',
        'allow_member_invites',
        'require_post_approval'
    ];

    protected $casts = [
        'industry' => 'array',
        'allow_member_invites' => 'boolean',
        'require_post_approval' => 'boolean',
    ];

    public function members()
    {
        // Your existing pivot table: group_users
        return $this->belongsToMany(User::class, 'group_users')->withPivot('role')->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
