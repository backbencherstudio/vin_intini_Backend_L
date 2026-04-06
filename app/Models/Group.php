<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    protected $fillable = ['name', 'description', 'creator_id', 'is_private', 'logo', 'cover_photo'];

    // Group members
    public function members()
    {
        return $this->belongsToMany(User::class)->withPivot('role')->withTimestamps();
    }

    // Group creator
    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }
}
