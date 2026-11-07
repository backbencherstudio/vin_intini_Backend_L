<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndustrySections extends Model
{
    protected $fillable = ['network_type', 'industry_type', 'name'];

    public function IndustryCategory(): HasMany
    {
        return $this->hasMany(IndustryCategory::class, 'section_id');
    }
}
