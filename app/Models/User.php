<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasRoles, Notifiable;

    protected $appends = [
        'profile_image_url',
        'cover_image_url',
    ];

    protected $fillable = [
        'first_name',
        'last_name',
        'title',
        'email',
        'mobile',
        'password',
        'profile_image',
        'cover_image',
        'otp',
        'otp_expires_at',
        'is_verified',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'is_verified' => 'boolean',
            'password' => 'hashed',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_users', 'user_id', 'group_id')
            ->withPivot('role')
            ->withTimestamps();
    }

    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function profile()
    {
        return $this->hasOne(UserProfile::class);
    }

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class);
    }

    public function getProfileImageUrlAttribute(): ?string
    {
        $value = $this->profile_image;
        if (! $value) {
            return null;
        }

        if (preg_match('/^https?:\\/\\//i', $value) === 1) {
            return $value;
        }

        return asset('storage/'.ltrim($value, '/'));
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        $value = $this->cover_image;
        if (! $value) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $value) === 1) {
            return $value;
        }

        return asset('storage/'.ltrim($value, '/'));
    }
}
