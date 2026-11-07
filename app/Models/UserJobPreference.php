<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserJobPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'job_title',
        'position',
        'network_type',
        'category',
        'sub_category',
        'employment_offering',
        'work_mode',
        'employment_type',
        'level',
        'experience',
        'salary_type',
        'salary_min',
        'salary_max',
        'state_id',
        'city_id',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'state_id' => 'integer',
        'city_id' => 'integer',
        'salary_min' => 'decimal:2',
        'salary_max' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    /**
     * Check if any preference field has been filled.
     */
    public function hasAnyPreference(): bool
    {
        $fields = [
            'job_title',
            'position',
            'network_type',
            'category',
            'sub_category',
            'employment_offering',
            'work_mode',
            'employment_type',
            'level',
            'experience',
            'salary_type',
            'salary_min',
            'salary_max',
            'state_id',
            'city_id',
        ];

        foreach ($fields as $field) {
            if (! is_null($this->{$field}) && $this->{$field} !== '') {
                return true;
            }
        }

        return false;
    }
}
