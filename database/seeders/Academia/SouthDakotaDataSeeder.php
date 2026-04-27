<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class SouthDakotaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'south-dakota'],
            ['name' => 'South Dakota', 'code' => 'SD']
        );

        $universities = [
            ['name' => 'Augustana University', 'psych' => ['BA', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Black Hills State University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Dakota State University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Dakota Wesleyan University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Mount Marty University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northern State University', 'psych' => ['BS', 'MSEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'South Dakota School of Mines & Tech', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'South Dakota State University', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Sioux Falls', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of South Dakota', 'psych' => ['BS', 'BA', 'MA', 'EdS', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
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
                'name' => 'University of South Dakota Psychiatry Residency Program',
                'loc' => 'Sioux Falls',
                'deg' => ['MD-DO']
            ],
            [
                'name' => 'University of South Dakota School of Medicine',
                'loc' => 'Vermillion',
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
            ['name' => 'Avera Behavioral Health Center', 'loc' => 'Sioux Falls', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Fort Meade VA Medical Center', 'loc' => 'Fort Meade', 'type' => 'va_facility'],
            ['name' => 'Hot Springs VA Medical Center', 'loc' => 'Hot Springs', 'type' => 'va_facility'],
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
