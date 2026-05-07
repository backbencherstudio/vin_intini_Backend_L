<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

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
        'website'
    ];

    protected $casts = [
        'psychology_degrees' => 'array',
        'neuroscience_degrees' => 'array',
        'counseling_degrees' => 'array',
        'has_online_options' => 'boolean'
    ];

    public function state()
    {
        return $this->belongsTo(State::class);
    }
}
