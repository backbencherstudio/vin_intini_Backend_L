<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Industry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'industry_category_id',
        'website',
        'address',
        'company_size',
        'logo',
        'cover_image',
        'tagline',
        'description',
        'authorization_confirmed',
        'authorization_confirmed_at',
        'created_by',
    ];

    protected $casts = [
        'authorization_confirmed' => 'boolean',
        'authorization_confirmed_at' => 'datetime',
    ];

    public function category()
    {
        return $this->belongsTo(
            IndustryCategory::class,
            'industry_category_id'
        );
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function recruiterPosts()
    {
        return $this->hasMany(RecruiterPost::class);
    }
}
