<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'logo',
        'type',
        'state',
        'country',
        'website',
    ];

    public function educations(): HasMany
    {
        return $this->hasMany(Education::class);
    }
}
