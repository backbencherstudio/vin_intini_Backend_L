<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustryCategory extends Model
{
    protected $fillable = [
        'section_id',
        'category_name',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeOrdered($query)
    {
        return $query->orderByRaw('COALESCE(sort_order, id) ASC');
    }

    public function IndustrySection()
    {
        return $this->belongsTo(IndustrySections::class, 'section_id');
    }

    public function IndustryItem()
    {
        return $this->hasMany(IndustryItem::class, 'category_id');
    }

    public function products()
    {
        return $this->hasMany(IndustryProduct::class, 'category_id');
    }

    protected $hidden = ['created_at', 'updated_at'];
}
