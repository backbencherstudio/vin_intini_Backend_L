<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademiaFacility extends Model
{
    protected $table = 'academia_facilities';
    protected $fillable = ['state_id', 'name', 'location', 'type', 'university_id'];

    public function university() {
        return $this->belongsTo(AcademiaUniversity::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }
}
