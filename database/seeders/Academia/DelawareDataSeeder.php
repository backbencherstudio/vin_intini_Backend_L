<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class DelawareDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. State Create
        $state = State::updateOrCreate(
            ['slug' => 'delaware'],
            ['name' => 'Delaware', 'code' => 'DE']
        );

        // 2. Universities
        $universities = [
            ['name' => 'Delaware State University', 'psych' => ['BS', 'MS', 'PhD'], 'neuro' => ['MS', 'PhD'], 'ol' => false],
            ['name' => 'Goldey-Beacom College', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Delaware', 'psych' => ['BA', 'MS', 'MA', 'PhD'], 'neuro' => ['BS', 'BS+MS', 'PhD'], 'ol' => false],
            ['name' => 'Wilmington University', 'psych' => ['BS', 'MS', 'MEd'], 'neuro' => [], 'ol' => false],
        ];

        foreach ($universities as $uni) {
            AcademiaUniversity::updateOrCreate(
                [
                    'state_id' => $state->id,
                    'name' => $uni['name'],
                ],
                [
                    'psychology_degrees' => $uni['psych'],
                    'neuroscience_degrees' => $uni['neuro'],
                    'has_online_options' => $uni['ol'],
                ]
            );
        }

        // 3. Medical Residencies
        $residencies = [
            ['name' => 'Delaware Division of Substance Abuse and Mental Health', 'loc' => 'New Castle', 'deg' => ['MD-DO']],
            ['name' => 'Christiana Care Health Services Inc', 'loc' => 'Wilmington', 'deg' => ['MD-DO']],
        ];

        foreach ($residencies as $res) {
            AcademiaMedicalResidency::updateOrCreate(
                [
                    'state_id' => $state->id,
                    'program_name' => $res['name'],
                    'location' => $res['loc'],
                ],
                [
                    'degree_types' => $res['deg'],
                ]
            );
        }

        // 4. State Institutions
        $stateInstitutions = [
            ['name' => 'Rockford Center', 'loc' => 'Newark'],
            ['name' => 'MeadowWood Behavioral Health Hospital', 'loc' => 'New Castle'],
        ];

        foreach ($stateInstitutions as $inst) {
            AcademiaFacility::updateOrCreate(
                [
                    'state_id' => $state->id,
                    'name' => $inst['name'],
                    'location' => $inst['loc'],
                ],
                [
                    'type' => 'state_institution',
                ]
            );
        }

        // 5. University Hospital
        AcademiaFacility::updateOrCreate(
            [
                'state_id' => $state->id,
                'name' => 'ChristianaCare Psychiatric Services',
            ],
            [
                'location' => 'Wilmington',
                'type' => 'university_hospital',
            ]
        );

        // 6. VA Facilities
        $vaFacilities = [
            ['name' => 'Perry Point VA Medical Center', 'loc' => 'Perry Point, MD'],
            ['name' => 'Lyons VA Medical Center', 'loc' => 'Lyons'],
            ['name' => 'Butler VA Medical Center', 'loc' => 'Butler, PA'],
            ['name' => 'Coatesville VA Medical Center', 'loc' => 'Coatesville, PA'],
            ['name' => 'CPL Michael J. Crescenz VA Medical Center', 'loc' => 'Philadelphia, PA'],
            ['name' => 'Erie VA Medical Center', 'loc' => 'Erie, PA'],
            ['name' => 'H. John Heinz III VA Medical Center', 'loc' => 'Pittsburgh, PA'],
            ['name' => 'Lebanon VA Medical Center', 'loc' => 'Lebanon, PA'],
            ['name' => 'Wilkes-Barre VA Medical Center', 'loc' => 'Wilkes-Barre, PA'],
        ];

        foreach ($vaFacilities as $va) {
            AcademiaFacility::updateOrCreate(
                [
                    'state_id' => $state->id,
                    'name' => $va['name'],
                ],
                [
                    'location' => $va['loc'],
                    'type' => 'va_facility',
                ]
            );
        }
    }
}
