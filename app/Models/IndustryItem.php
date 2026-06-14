<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IndustryItem extends Model
{
    protected $fillable = [
        'category_id', 'title', 'tag', 'sub_title', 'indication',
        'moa', 'pub_date', 'extra_tag', 'image', 'description', 'link'
    ];

    public function IndustryCategory() {
        return $this->belongsTo(IndustryCategory::class, 'category_id');
    }
}
