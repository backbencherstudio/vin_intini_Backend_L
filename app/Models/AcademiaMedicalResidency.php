<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademiaMedicalResidency extends Model
{
    protected $table = 'academia_medical_residencies';
    protected $fillable = ['state_id', 'program_name', 'location', 'degree_types'];

    protected $casts = [
        'degree_types' => 'array'
    ];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
