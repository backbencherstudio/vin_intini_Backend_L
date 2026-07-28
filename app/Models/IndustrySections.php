<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustrySections extends Model
{
    protected $fillable = ['network_type', 'industry_type', 'name'];

    public function IndustryCategory()
    {
        return $this->hasMany(IndustryCategory::class, 'section_id');
    }
}
