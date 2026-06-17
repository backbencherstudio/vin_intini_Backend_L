<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustryCategory extends Model
{
    protected $fillable = ['section_id', 'category_name'];
    
    public function IndustrySection()
    {
        return $this->belongsTo(IndustrySections::class, 'section_id');
    }

    public function IndustryItem()
    {
        return $this->hasMany(IndustryItem::class, 'category_id');
    }

    protected $hidden = ['created_at', 'updated_at'];
}
