<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class ColoradoDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. State Create
        $state = State::updateOrCreate(
            ['slug' => 'colorado'],
            ['name' => 'Colorado', 'code' => 'CO']
        );

        // 2. Universities
        $universities = [
            ['name' => 'Adams State University', 'psych' => ['BA', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Colorado Christian University', 'psych' => ['BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Colorado College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Colorado Mesa University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Colorado School of Mines', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Colorado State University', 'psych' => ['BS', 'MS', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'Colorado State University Pueblo', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Colorado Technical University', 'psych' => ['BS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Fort Lewis College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Metropolitan State University', 'psych' => ['BS', 'BS (OL)', 'MS'], 'neuro' => [], 'ol' => true],
            ['name' => 'Napora University', 'psych' => ['BA', 'BA (OL)', 'MA'], 'neuro' => [], 'ol' => true],
            ['name' => 'Nazarene Bible College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Regis University', 'psych' => ['BS', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Rocky Mountain College of Art + Design', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Rocky Vista University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'United States Air Force Academy', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Colorado Boulder', 'psych' => ['BA', 'PhD'], 'neuro' => ['BA', 'PhD'], 'ol' => false],
            ['name' => 'University of Colorado Colorado Springs', 'psych' => ['BA', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Colorado Denver', 'psych' => ['BS', 'BA', 'MS', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Colorado Anschutz', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => ['PhD'], 'ol' => false],
            ['name' => 'University of Denver', 'psych' => ['BS', 'BA', 'MA', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Northern Colorado', 'psych' => ['BA', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Western State Colorado University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'University of Colorado Psychiatry Residency Program', 'loc' => 'Aurora', 'deg' => ['MD-DO']],
            ['name' => 'HCA HealthONE/The Med Cntr of Aurora Psychiatry Residency Program', 'loc' => 'Aurora', 'deg' => ['MD-DO']],
            ['name' => 'University of Colorado Health Sciences Center', 'loc' => 'Denver', 'deg' => ['MD-PhD']],
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
            ['name' => 'Cedar Springs Behavioral Health System', 'loc' => 'Colorado Springs'],
            ['name' => 'Centennial Peaks Hospital', 'loc' => 'Louisville'],
            ['name' => 'Center for Dependency, Addiction & Rehabilitation (CeDAR)', 'loc' => 'Aurora'],
            ['name' => 'Colorado Mental Health Institute at Fort Logan', 'loc' => 'Denver'],
            ['name' => 'Colorado Mental Health Institute at Pueblo', 'loc' => 'Pueblo'],
            ['name' => 'Colorado West Regional Mental Health Center', 'loc' => null],
            ['name' => 'Crossroads Managed Care Systems, Inc. (CMCS)', 'loc' => 'Pueblo'],
            ['name' => 'Devereux Cleo Wallace Center', 'loc' => 'Denver'],
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
                'name' => 'University of Colorado Hospital Behavioral Health',
            ],
            [
                'location' => 'Aurora',
                'type' => 'university_hospital',
            ]
        );

        // 6. VA Facilities
        $vaFacilities = [
            ['name' => 'Grand Junction VA Medical Center', 'loc' => 'Grand Junction'],
            ['name' => 'Rocky Mountain Regional VA Medical Center', 'loc' => 'Aurora'],
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
