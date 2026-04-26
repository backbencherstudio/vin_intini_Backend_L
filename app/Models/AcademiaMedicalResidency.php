<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademiaMedicalResidency extends Model
{
    protected $table = 'academia_medical_residencies';
    protected $fillable = ['state_id', 'program_name', 'location', 'degree_types'];

    protected $casts = [
        'degree_types' => 'array'
    ];
}
