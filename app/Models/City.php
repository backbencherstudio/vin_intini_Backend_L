<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = ['state_id', 'name'];

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function industryJobPosts(): HasMany
    {
        return $this->hasMany(IndustryJobPost::class);
    }

    public function userJobPreferences(): HasMany
    {
        return $this->hasMany(UserJobPreference::class);
    }
}
