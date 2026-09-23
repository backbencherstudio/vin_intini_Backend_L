<?php

namespace App\Models;

use App\Enums\IndustryJobPostStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndustryJobPost extends Model
{
    use HasFactory;

    protected $table = 'industry_job_posts';

    protected $fillable = [
        'job_id',
        'industry_id',
        'created_by',
        'job_title',
        'position',
        'slug',
        'job_description',
        'work_mode',
        'employment_type',
        'level',
        'experience',
        'state_id',
        'city_id',
        'email',
        'phone_number',
        'salary_min',
        'salary_max',
        'location_url',
        'employment_offering',
        'network_type',
        'website',
        'tags',
        'views_count',
        'likes_count',
        'announcement_start_date',
        'announcement_end_date',
        'status',
        'information_confirmed',
        'submitted_at',
        'reviewed_at',
        'rejection_reason',
    ];

    protected $casts = [
        'status' => IndustryJobPostStatus::class,
        'tags' => 'array',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
        'views_count' => 'integer',
        'likes_count' => 'integer',
        'state_id' => 'integer',
        'city_id' => 'integer',
        'industry_id' => 'integer',
        'created_by' => 'integer',
        'announcement_start_date' => 'date',
        'announcement_end_date' => 'date',
        'information_confirmed' => 'boolean',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(IndustryJobPostView::class, 'industry_job_post_id');
    }

    public function saves(): HasMany
    {
        return $this->hasMany(IndustryJobPostSave::class, 'industry_job_post_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(IndustryJobPostLike::class, 'industry_job_post_id');
    }

    public function isSavedBy(?User $user): bool
    {
        if (!$user) return false;

        if ($this->relationLoaded('saves')) {
            return $this->saves->contains('user_id', $user->id);
        }

        return $this->saves()->where('user_id', $user->id)->exists();
    }

    public function isLikedBy(?User $user): bool
    {
        if (!$user) return false;

        if ($this->relationLoaded('likes')) {
            return $this->likes->contains('user_id', $user->id);
        }

        return $this->likes()->where('user_id', $user->id)->exists();
    }
}
