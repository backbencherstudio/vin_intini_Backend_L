<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Group extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Group $group) {
            if (empty($group->slug)) {
                $baseSlug = Str::slug($group->name) ?: 'group';
                $finalSlug = $baseSlug;
                $counter = 1;
                while (static::where('slug', $finalSlug)->exists()) {
                    $finalSlug = $baseSlug.'-'.$counter;
                    $counter++;
                }
                $group->slug = $finalSlug;
            }
        });
    }

    protected $appends = [
        'logo_url',
        'cover_photo_url',
    ];

    protected $fillable = [
        'name',
        'slug',
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
        'require_post_approval',
    ];

    protected $casts = [
        'industry' => 'array',
        'allow_member_invites' => 'boolean',
        'require_post_approval' => 'boolean',
    ];

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_users')->withPivot('role')->withTimestamps();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::disk('public')->url($this->logo) : null;
    }

    public function getCoverPhotoUrlAttribute(): ?string
    {
        return $this->cover_photo ? Storage::disk('public')->url($this->cover_photo) : null;
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'group_users')
            ->withPivot('status');
    }
}
