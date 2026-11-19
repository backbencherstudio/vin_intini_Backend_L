<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IndustrySections extends Model
{
    protected $fillable = [
        'network_type',
        'industry_type',
        'name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order', 'asc')->orderBy('id', 'asc');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function IndustryCategory(): HasMany
    {
        return $this->hasMany(IndustryCategory::class, 'section_id')->ordered();
    }

    public function products(): HasMany
    {
        return $this->hasMany(IndustryProduct::class, 'section_id');
    }
}
