<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AcademiaFacility extends Model
{
    protected $table = 'academia_facilities';
    protected $fillable = ['state_id', 'name', 'location', 'type', 'university_id'];

    public function university() {
        return $this->belongsTo(AcademiaUniversity::class);
    }
}
