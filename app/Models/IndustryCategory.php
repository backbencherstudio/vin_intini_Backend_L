<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustryCategory extends Model
{
    protected $fillable = ['network_type', 'industry_type', 'section_name', 'category_name'];

    public function IndustryItem() {
        return $this->hasMany(IndustryItem::class, 'category_id');
    }
}
