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
        return $this->resume_path ? asset('storage/' . ltrim($this->resume_path, '/')) : null;
    }
}
