<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademiaJob extends Model
{
    protected $table = 'academia_jobs';

    protected $fillable = [
        'state_id',
        'title',
        'company_name',
        'location',
        'salary_min',
        'salary_max',
        'category',
        'employment_type',
        'work_mode',
        'latitude',
        'longitude',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
