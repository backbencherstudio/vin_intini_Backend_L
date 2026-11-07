<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class State extends Model
{
    protected $fillable = ['name', 'code', 'slug'];

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }

    public function industryJobPosts(): HasMany
    {
        return $this->hasMany(IndustryJobPost::class);
    }

    public function universities(): HasMany
    {
        return $this->hasMany(AcademiaUniversity::class);
    }

    public function residencies(): HasMany
    {
        return $this->hasMany(AcademiaMedicalResidency::class);
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(AcademiaFacility::class);
    }

    public function jobs(): HasMany
    {
        return $this->hasMany(AcademiaJob::class);
    }
}
