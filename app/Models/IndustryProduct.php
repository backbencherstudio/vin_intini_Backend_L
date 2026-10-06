<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IndustryProduct extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'creator_id',
        'industry_id',
        'network_type',
        'industry_type',
        'section_id',
        'category_id',
        'product_name',
        'slug',
        'description',
        'product_url',
        'image',
        'tags',
        'poc_name',
        'poc_email',
        'poc_phone',
        'information_confirmed',
        'status',
        'views_count',
        'likes_count',
    ];

    protected static function booted(): void
    {
        static::creating(function (IndustryProduct $product) {
            if (empty($product->product_id)) {
                do {
                    $productId = (string) random_int(100000, 999999);
                } while (static::where('product_id', $productId)->exists());

                $product->product_id = $productId;
            }
        });
    }

    protected $casts = [
        'tags' => 'array',
        'information_confirmed' => 'boolean',
        'views_count' => 'integer',
        'likes_count' => 'integer',
    ];

    protected $appends = [
        'image_url',
    ];

    protected function imageUrl(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->image ? asset('storage/'.$this->image) : null,
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function industry(): BelongsTo
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(IndustrySections::class, 'section_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(IndustryCategory::class, 'category_id');
    }

    public function views(): HasMany
    {
        return $this->hasMany(IndustryProductView::class, 'industry_product_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(IndustryProductLike::class, 'industry_product_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
