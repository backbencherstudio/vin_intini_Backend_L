<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class VermontDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'vermont'],
            ['name' => 'Vermont', 'code' => 'VT']
        );

        $universities = [
            ['name' => 'Bennington College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Champlain College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Landmark College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Middlebury College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Norwich University', 'psych' => ['BS', 'BA (OL)'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'Saint Michael\'s College', 'psych' => ['BA', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'SIT Graduate Institute', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Sterling College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Vermont', 'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'], 'neuro' => ['BS', 'BA', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'Vermont Law School', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Vermont State University', 'psych' => ['BS', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Vermont College of Fine Arts', 'psych' => [], 'neuro' => [], 'ol' => false],
        ];

        foreach ($universities as $uni) {
            AcademiaUniversity::create([
                'state_id' => $state->id,
                'name' => $uni['name'],
                'psychology_degrees' => $uni['psych'],
                'neuroscience_degrees' => $uni['neuro'],
                'has_online_options' => $uni['ol'],
            ]);
        }

        $residencies = [
            [
                'name' => 'University of Vermont Medical Center',
                'loc' => 'Burlington',
                'deg' => ['MD-DO']
            ],
            [
                'name' => 'University of Vermont College of Medicine',
                'loc' => 'Burlington',
                'deg' => ['MD-PhD']
            ],
        ];

        foreach ($residencies as $res) {
            AcademiaMedicalResidency::create([
                'state_id' => $state->id,
                'program_name' => $res['name'],
                'location' => $res['loc'],
                'degree_types' => $res['deg'],
            ]);
        }

        $facilities = [
            // State Institutions
            ['name' => 'Brattleboro Retreat', 'loc' => 'Brattleboro', 'type' => 'state_institution'],
            ['name' => 'Vermont State Hospital', 'loc' => 'Waterbury', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facility
            ['name' => 'White River Junction VA Medical Center', 'loc' => 'White River Junction', 'type' => 'va_facility'],
        ];

        foreach ($facilities as $fac) {
            AcademiaFacility::create([
                'state_id' => $state->id,
                'name' => $fac['name'],
                'location' => $fac['loc'],
                'type' => $fac['type'],
            ]);
        }
    }
}
