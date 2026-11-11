<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AcademiaPostBaccalaureateCategory extends Model
{
    protected $table = 'academia_post_baccalaureate_categories';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'sort_order',
        'is_active',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * @return BelongsToMany<AcademiaPostBaccalaureateProgram, $this>
     */
    public function programs(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademiaPostBaccalaureateProgram::class,
            'academia_post_baccalaureate_program_category',
            'category_id',
            'program_id'
        );
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
