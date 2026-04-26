<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class ConnecticutDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. State Create
        $state = State::updateOrCreate(
            ['slug' => 'connecticut'],
            ['name' => 'Connecticut', 'code' => 'CT']
        );

        // 2. Universities
        $universities = [
            ['name' => 'Albertus Magnus College', 'psych' => ['BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Central Connecticut State University', 'psych' => ['BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Charter Oak State College', 'psych' => ['BS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Connecticut College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Eastern Connecticut State University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Goodwin University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Holy Apostles College and Seminary', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Fairfield University', 'psych' => ['BS', 'MS', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Mitchell College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Post University', 'psych' => ['BA', 'BA (OL)', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Quinnipiac University', 'psych' => ['BS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Sacred Heart University', 'psych' => ['BS', 'MS', 'MA', 'BS+MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Southern Connecticut State University', 'psych' => ['BS', 'BA', 'MS', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Trinity College', 'psych' => ['BS'], 'neuro' => ['BS', 'BA', 'BS+MA'], 'ol' => false],
            ['name' => 'United States Coast Guard Academy', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Bridgeport', 'psych' => ['BS', 'BS (OL)', 'MS'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of Connecticut', 'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'University of Hartford', 'psych' => ['BA', 'BA+MS', 'MS', 'PsyD'], 'neuro' => ['BS', 'BA', 'MS'], 'ol' => false],
            ['name' => 'University of New Haven', 'psych' => ['BA', 'MA', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of Saint Joseph', 'psych' => ['BA', 'BA+MA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Wesleyan University', 'psych' => ['BA', 'MA'], 'neuro' => ['BA', 'BA+MA', 'MA'], 'ol' => false],
            ['name' => 'Western Connecticut State University', 'psych' => ['BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Yale University', 'psych' => ['BS', 'BA', 'PhD'], 'neuro' => ['BS', 'BA', 'MRes', 'PhD'], 'ol' => false],
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
            ['name' => 'Yale-New Haven Medical Center', 'loc' => 'New Haven', 'deg' => ['MD-DO']],
            ['name' => 'University of Connecticut', 'loc' => 'Farmington', 'deg' => ['MD-DO']],
            ['name' => 'Inst of Living/Hartford Hospital Psychiatry Residency Program', 'loc' => 'Hartford', 'deg' => ['MD-DO']],
            ['name' => 'Quinnipiac University Frank H. Netter MD School of Medicine', 'loc' => 'Torrington', 'deg' => ['MD-DO']],
            ['name' => 'CT Institute For Communities Health Psychiatry Residency Program', 'loc' => 'Danbury', 'deg' => ['MD-DO']],
            ['name' => 'Eastern Connecticut Health Network', 'loc' => 'Manchester', 'deg' => ['MD-DO']],
            ['name' => 'University of Connecticut School of Medicine', 'loc' => 'Farmington', 'deg' => ['MD-PhD']],
            ['name' => 'Yale University School of Medicine', 'loc' => 'New Haven', 'deg' => ['MD-PhD']],
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
            ['name' => 'Connecticut Mental Health Center', 'loc' => 'New Haven'],
            ['name' => 'Hall-Brooke Behavioral Health Services', 'loc' => 'Westport'],
            ['name' => 'Natchaug Hospital', 'loc' => 'Mansfield Center'],
            ['name' => 'Silver Hill Hospital', 'loc' => 'New Canaan'],
            ['name' => 'Stonington Institute', 'loc' => 'North Stonington'],
            ['name' => 'Yale-New Haven Psychiatric Hospital', 'loc' => null],
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
                'name' => 'Yale New Haven Hospital Psychiatry Services',
            ],
            [
                'location' => 'New Haven',
                'type' => 'university_hospital',
            ]
        );

        // 6. VA Facility
        AcademiaFacility::updateOrCreate(
            [
                'state_id' => $state->id,
                'name' => 'West Haven VA Medical Center',
            ],
            [
                'location' => 'West Haven',
                'type' => 'va_facility',
            ]
        );
    }
}
