<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Jobs\CleanupUserFiles;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Prunable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, HasRoles, Notifiable, Prunable, SoftDeletes;

    protected $appends = [
        'profile_image_url',
        'cover_image_url',
    ];

    protected $fillable = [
        'first_name',
        'last_name',
        'username',
        'title',
        'email',
        'mobile',
        'password',
        'has_password',
        'profile_image',
        'cover_image',
        'otp',
        'otp_expires_at',
        'is_verified',
        'stripe_customer_id',
        'terms_accepted_at',
        'two_factor_secret',
        'two_factor_confirmed_at',
        'two_factor_recovery_codes',
        'recovery_email',
        'recovery_email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'mobile',
        'otp',
        'otp_expires_at',
        'is_verified',
        'stripe_customer_id',
        'profile_image',
        'cover_image',
        'email_verified_at',
        'created_at',
        'updated_at',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'terms_accepted_at' => 'datetime',
            'otp_expires_at' => 'datetime',
            'is_verified' => 'boolean',
            'password' => 'hashed',
            'two_factor_confirmed_at' => 'datetime',
            'recovery_email_verified_at' => 'datetime',
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

    public function experiences(): HasMany
    {
        return $this->hasMany(Experience::class);
    }

    public function fcmTokens(): HasMany
    {
        return $this->hasMany(FcmToken::class);
    }

    public function routeNotificationForFcm($notification): array
    {
        return $this->fcmTokens()->pluck('fcm_token')->toArray();
    }

    /**
     * Absolute, public URL suitable for the FCM notification image field.
     * Falls back to the app logo when the user has no profile photo.
     */
    public function notificationImageUrl(): string
    {
        return $this->profile_image
            ? (string) $this->profile_image_url
            : asset('logo.png');
    }

    public function connectionRequestsSent(): HasMany
    {
        return $this->hasMany(Connection::class, 'sender_id');
    }

    public function connectionRequestsReceived(): HasMany
    {
        return $this->hasMany(Connection::class, 'receiver_id');
    }

    public function followers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_follows', 'following_id', 'follower_id')
            ->withTimestamps();
    }

    public function following(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_follows', 'follower_id', 'following_id')
            ->withTimestamps();
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

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function likedPosts()
    {
        return $this->belongsToMany(Post::class, 'post_likes');
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    // -----------------------------------------------------------
    // public function education(): HasOne
    // {
    //     return $this->hasOne(Education::class, 'user_id');
    // }
    // --------------------------------------------------------

    public function prunable()
    {
        return static::onlyTrashed()->where('deleted_at', '<=', now()->subDays(30));
    }

    protected static function booted()
    {
        static::deleting(function ($user) {
            if ($user->isForceDeleting()) {
                $filesToDelete = [];

                if ($user->profile_image) {
                    $filesToDelete[] = $user->profile_image;
                }
                if ($user->cover_image) {
                    $filesToDelete[] = $user->cover_image;
                }

                $user->posts()->with(['media', 'comments.replies'])->each(function ($post) use (&$filesToDelete) {

                    foreach ($post->media as $media) {
                        if ($media->file_path) {
                            $filesToDelete[] = $media->file_path;
                        }
                    }

                    foreach ($post->comments as $comment) {
                        if ($comment->image) {
                            $filesToDelete[] = $comment->image;
                        }
                        foreach ($comment->replies as $reply) {
                            if ($reply->image) {
                                $filesToDelete[] = $reply->image;
                            }
                        }
                    }
                });

                $user->comments()->each(function ($comment) use (&$filesToDelete) {
                    if ($comment->image) {
                        $filesToDelete[] = $comment->image;
                    }
                });

                if (method_exists($user, 'replies')) {
                    $user->replies()->each(function ($reply) use (&$filesToDelete) {
                        if ($reply->image) {
                            $filesToDelete[] = $reply->image;
                        }
                    });
                }

                if (! empty($filesToDelete)) {
                    dispatch(new CleanupUserFiles(array_unique($filesToDelete)));
                }

                LoginActivity::where('user_id', $user->id)->delete();
                $user->profile()?->delete();
                $user->posts()->delete();
                $user->comments()->delete();
                $user->educations()->delete();
                $user->experiences()->delete();
                $user->socialAccounts()->delete();
                $user->fcmTokens()->delete();
                $user->connectionRequestsSent()->delete();
                $user->connectionRequestsReceived()->delete();
                $user->followers()->detach();
                $user->following()->detach();
                $user->likedPosts()->detach();
                $user->groups()->detach();
            }
        });
    }
}
