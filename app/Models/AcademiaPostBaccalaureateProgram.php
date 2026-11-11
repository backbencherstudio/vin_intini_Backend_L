<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class AcademiaPostBaccalaureateProgram extends Model
{
    protected $table = 'academia_post_baccalaureate_programs';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'state_id',
        'program_name',
        'location',
        'phone',
        'website',
        'latitude',
        'longitude',
    ];

    /**
     * @return BelongsTo<State, $this>
     */
    public function state(): BelongsTo
    {
        return $this->belongsTo(State::class);
    }

    /**
     * @return BelongsToMany<AcademiaPostBaccalaureateCategory, $this>
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(
            AcademiaPostBaccalaureateCategory::class,
            'academia_post_baccalaureate_program_category',
            'program_id',
            'category_id'
        )->orderBy('academia_post_baccalaureate_categories.sort_order')
            ->orderBy('academia_post_baccalaureate_categories.id');
    }
}
