<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademiaUniversity extends Model
{
    protected $table = 'academia_universities';

    protected $fillable = [
        'state_id',
        'name',
        'psychology_degrees',
        'counseling_degrees',
        'neuroscience_degrees',
        'has_online_options',
        'latitude',
        'longitude',
        'phone',
        'location',
        'website',
    ];

    protected $casts = [
        'psychology_degrees' => 'array',
        'neuroscience_degrees' => 'array',
        'counseling_degrees' => 'array',
        'has_online_options' => 'boolean',
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
