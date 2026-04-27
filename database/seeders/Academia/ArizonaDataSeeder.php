<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class ArizonaDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. State Create
        $state = State::updateOrCreate(
            ['slug' => 'arizona'],
            ['name' => 'Arizona', 'code' => 'AZ']
        );

        // 2. Universities
        $universities = [
            ['name' => 'Arizona Christian University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Arizona State University- Phoenix', 'psych' => ['MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Arizona State University- Polytechnic', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Arizona State University- Tempe', 'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'Arizona State University- West Valley', 'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'Embry-Riddle Aeronautical University', 'psych' => [], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Grand Canyon University', 'psych' => ['BS', 'MS(OL)', 'PhD', 'PhD(OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'International Baptist College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Midwestern University', 'psych' => ['PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northern Arizona University', 'psych' => ['BS', 'MA', 'EdS', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Prescott College', 'psych' => ['BA', 'MS', 'M.Ed'], 'neuro' => [], 'ol' => false],
            ['name' => 'The School of Architecture', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Advanced Technology', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Arizona', 'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
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
            ['name' => 'University of Arizona College of Medicine', 'loc' => 'Phoenix', 'deg' => ['MD-DO']],
            ['name' => 'University of Arizona College of Medicine', 'loc' => 'Tucson', 'deg' => ['MD-DO']],
            ['name' => 'Creighton Univ School of Medicine Psychiatry Residency Program', 'loc' => 'Mesa', 'deg' => ['MD-DO']],
            ['name' => 'El Rio Health Psychiatry Residency Program', 'loc' => 'Tucson', 'deg' => ['MD-DO']],
            ['name' => 'MHC Healthcare', 'loc' => 'Marana', 'deg' => ['MD-DO']],
            ['name' => 'Yuma Regional Medical Center', 'loc' => 'Yuma', 'deg' => ['MD-DO']],
            ['name' => 'University of Arizona College of Medicine', 'loc' => 'Tucson', 'deg' => ['MD-PhD']],
            ['name' => 'University of Arizona College of Medicine', 'loc' => 'Phoenix', 'deg' => ['MD-PhD']],
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
            ['name' => 'Oasis Behavioral Health Hospital', 'loc' => 'Chandler'],
            ['name' => 'Banner Alzheimer\'s Institute', 'loc' => 'Phoenix'],
            ['name' => 'Banner Behavioral Health Hospital', 'loc' => 'Scottsdale'],
            ['name' => 'Banner Behavioral Health Institute', 'loc' => 'Mesa'],
            ['name' => 'Banner Desert Behavioral Health Center', 'loc' => 'Mesa'],
            ['name' => 'Calvary Center', 'loc' => 'Phoenix'],
            ['name' => 'Saint Luke\'s Behavioral Health Center', 'loc' => 'Phoenix'],
            ['name' => 'Sierra Tucson', 'loc' => 'Tucson'],
            ['name' => 'Sonora Behavioral Health Hospital', 'loc' => 'Tucson'],
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
                'name' => 'Banner University Medical Center Psychiatry Services',
            ],
            [
                'location' => 'Phoenix',
                'type' => 'university_hospital',
            ]
        );

        // 6. VA Facilities
        $vaFacilities = [
            ['name' => 'Bob Stump Department of Veterans Affairs Medical Center', 'loc' => 'Prescott'],
            ['name' => 'Carl T. Hayden Veterans Administration Medical Center', 'loc' => 'Phoenix'],
            ['name' => 'Tucson VA Medical Center', 'loc' => 'Tucson'],
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
