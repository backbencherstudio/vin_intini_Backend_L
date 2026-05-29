<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'description',
        'visibility',
        'who_can_comment',
        'total_like',
        'total_comment',
    ];

    /**
     * Restrict a query to company posts only.
     */
    public function scopeForIndustry(Builder $query): Builder
    {
        return $query->whereHas('industryLink');
    }

    /**
     * Restrict a query to regular user posts, excluding company posts.
     */
    public function scopeForUser(Builder $query): Builder
    {
        return $query->whereDoesntHave('industryLink');
    }

    /**
     * Determine whether a company post is readable by the given user.
     *
     * Company posts use the `followers` and `private` visibilities, which the
     * generic post endpoints do not model. Only meaningful when the post
     * belongs to an industry.
     */
    public function isVisibleTo(?User $user): bool
    {
        if ($user && (int) $this->user_id === (int) $user->id) {
            return true;
        }

        if ($this->visibility === 'public') {
            return true;
        }

        $industryId = $this->industryLink?->industry_id;

        if ($this->visibility === 'followers' && $industryId) {
            return IndustryFollow::where('industry_id', $industryId)
                ->where('user_id', $user?->id)
                ->exists();
        }

        return false;
    }

    /**
     * Determine whether this post is published on a company page.
     */
    public function isCompanyPost(): bool
    {
        return $this->relationLoaded('industryLink')
            ? $this->industryLink !== null
            : $this->industryLink()->exists();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The mapping row linking this post to a company page, if any.
     */
    public function industryLink(): HasOne
    {
        return $this->hasOne(PostIndustry::class, 'post_id');
    }

    /**
     * The company page(s) this post is published on. A post is limited to one
     * by the unique index on `post_industry.post_id`.
     */
    public function industry(): BelongsToMany
    {
        return $this->belongsToMany(Industry::class, 'post_industry', 'post_id', 'industry_id');
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'post_groups');
    }

    public function likes()
    {
        return $this->hasMany(PostLike::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function media()
    {
        return $this->hasMany(PostMedia::class)->orderBy('order');
    }

    protected static function booted()
    {
        static::deleting(function ($post) {
            $media = $post->relationLoaded('media')
                ? $post->media
                : $post->media()->get();

            $media->each(function ($item) {
                if ($item->file_path) {
                    Storage::disk('public')->delete($item->file_path);
                }

                $item->delete();
            });

            $comments = $post->relationLoaded('comments')
                ? $post->comments
                : $post->comments()->get();

            $comments->each(function ($comment) {
                $comment->delete();
            });

            // The mapping is removed explicitly rather than relying on the
            // cascade so that a company page delete, which never loads posts
            // through this hook, cannot leave the post side dangling.
            $post->industryLink()->delete();
        });
    }
}
