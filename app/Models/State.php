<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class State extends Model
{
    protected $fillable = ['name', 'code', 'slug'];

    public function universities() {
        return $this->hasMany(AcademiaUniversity::class);
    }

    public function residencies() {
        return $this->hasMany(AcademiaMedicalResidency::class);
    }

    public function facilities() {
        return $this->hasMany(AcademiaFacility::class);
    }

    public function jobs() {
        return $this->hasMany(AcademiaJob::class);
    }
}
