<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AcademiaPostBaccalaureateCategory;
use Illuminate\Database\Seeder;

class PostBaccalaureateCategorySeeder extends Seeder
{
    /**
     * Default ordered Post-Baccalaureate degree/recognition filter categories.
     *
     * @var list<string>
     */
    public const DEFAULT_CATEGORIES = [
        'PostBacc PreMed Program',
        'MS',
        'MSHS',
        'MSBS/MBS',
        'MBMS',
        'MBS (Online)',
        'MEDR',
        'IMS',
        'IMHS',
        'MABS',
        'MA',
        'MATS',
        'PreMed Bridge',
        'PreMed Pathway',
        'Certificate',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::DEFAULT_CATEGORIES as $index => $name) {
            AcademiaPostBaccalaureateCategory::firstOrCreate(
                ['name' => $name],
                [
                    'sort_order' => $index + 1,
                    'is_active' => true,
                ]
            );
        }
    }
}
