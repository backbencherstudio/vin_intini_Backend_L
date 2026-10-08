<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IndustryJobApplication extends Model
{
    use HasFactory;

    protected $table = 'industry_job_applications';

    protected $fillable = [
        'application_id',
        'job_id',
        'applicant_id',
        'application_type',
        'full_name',
        'email',
        'phone_number',
        'experiences',
        'current_position',
        'expected_salary',
        'location',
        'linkedin_url',
        'portfolio_url',
        'cover_letter',
        'about_yourself',
        'skills',
        'resume_path',
        'status',
    ];

    protected static function booted(): void
    {
        static::creating(function (IndustryJobApplication $application) {
            if (empty($application->application_id)) {
                $application->application_id = static::generateUniqueApplicationId((int) ($application->job_id ?: 1));
            }
        });
    }

    /**
     * Generate a job-scoped unique application ID without database looping.
     * Incorporates job_id as prefix with dynamic suffix length:
     * - 1 to 3 digit job IDs produce a clean 6-character code (e.g. 7K9M2, 429B7Q, 5008XF)
     * - 4+ digit job IDs scale cleanly with at least 3 random chars (e.g. 10008XF -> 7 chars)
     */
    public static function generateUniqueApplicationId(int $jobId): string
    {
        $prefix = (string) $jobId;
        $randomLength = max(3, 6 - strlen($prefix));
        $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
        $charLength = strlen($characters);

        $suffix = '';
        for ($i = 0; $i < $randomLength; $i++) {
            $suffix .= $characters[random_int(0, $charLength - 1)];
        }

        return $prefix.$suffix;
    }

    protected $casts = [
        'skills' => 'array',
        'expected_salary' => 'decimal:2',
    ];

    public function job(): BelongsTo
    {
        return $this->belongsTo(IndustryJobPost::class, 'job_id');
    }

    public function applicant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'applicant_id');
    }

    public function getResumeUrlAttribute(): ?string
    {
        return $this->resume_path ? asset('storage/'.ltrim($this->resume_path, '/')) : null;
    }
}
