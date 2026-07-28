<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class IndustryItem extends Model
{
    protected $fillable = [
        'category_id',
        'title',
        'tag',
        'sub_title',
        'indication',
        'moa',
        'pub_date',
        'extra_tag',
        'image',
        'description',
        'link',
    ];

    public function IndustryCategory()
    {
        return $this->belongsTo(IndustryCategory::class, 'category_id');
    }

    protected $appends = ['image_url'];

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image ? asset('storage/'.$this->image) : null,
        );
    }

    protected $hidden = ['created_at', 'updated_at'];
}
