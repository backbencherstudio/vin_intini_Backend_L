<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class ArkansasDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. State Create
        $state = State::updateOrCreate(
            ['slug' => 'arkansas'],
            ['name' => 'Arkansas', 'code' => 'AR']
        );

        // 2. Universities
        $universities = [
            ['name' => 'Arkansas Baptist College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Arkansas State University', 'psych' => ['BS', 'BA(OL)', 'MSE', 'EdS'], 'neuro' => [], 'ol' => true],
            ['name' => 'Arkansas Tech University', 'psych' => ['BA', 'MS', 'M.Ed'], 'neuro' => [], 'ol' => false],
            ['name' => 'Central Baptist College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Crowley\'s Ridge College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Ecclesia College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Harding University', 'psych' => ['BS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Henderson State University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Hendrix College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'John Brown University', 'psych' => ['BS', 'MS'], 'neuro' => ['BS (OL)'], 'ol' => true],
            ['name' => 'Lyon College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Ouachita Baptist University', 'psych' => ['BS', 'BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Philander Smith College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Southern Arkansas University', 'psych' => ['BS', 'MS', 'M.Ed'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Arkansas', 'psych' => ['BA', 'MS', 'MA', 'PhD'], 'neuro' => ['MS', 'PhD'], 'ol' => false],
            ['name' => 'University of Central Arkansas', 'psych' => ['BS', 'MS', 'EdS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of the Ozarks', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Williams Baptist University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'Univ of Arkansas for Med Sciences (UAMS) College of Medicine', 'loc' => 'Little Rock', 'deg' => ['MD-DO']],
            ['name' => 'Unity Health-White County Medical Center', 'loc' => 'Searcy', 'deg' => ['MD-DO']],
            ['name' => 'Baptist Health-UAMS', 'loc' => 'North Little Rock', 'deg' => ['MD-DO']],
            ['name' => 'University of Arkansas College of Medicine', 'loc' => 'Little Rock', 'deg' => ['MD-PhD']],
        ];

        foreach ($residencies as $res) {
            AcademiaMedicalResidency::updateOrCreate(
                [
                    'state_id' => $state->id,
                    'program_name' => $res['name'],
                ],
                [
                    'location' => $res['loc'],
                    'degree_types' => $res['deg'],
                ]
            );
        }

        // 4. State Institutions
        $stateInstitutions = [
            ['name' => 'Valley Behavioral Health System', 'loc' => 'Barling'],
            ['name' => 'Rivendell Behavioral Health', 'loc' => 'Benton'],
            ['name' => 'Conway Behavioral Health Hospital', 'loc' => 'Conway'],
            ['name' => 'Vantage Point Behavioral Health Hospital', 'loc' => 'Fayetteville'],
            ['name' => 'Vista Health Services', 'loc' => 'Fayetteville'],
            ['name' => 'Millcreek Behavioral Health', 'loc' => 'Fordyce'],
            ['name' => 'Vista Health Services', 'loc' => 'Fort Smith'],
            ['name' => 'Arkansas State Hospital', 'loc' => 'Little Rock'],
            ['name' => 'The BridgeWay Hospital', 'loc' => 'North Little Rock'],
            ['name' => 'Pinnacle Point Hospital', 'loc' => 'Little Rock'],
            ['name' => 'Riverview Behavioral Health Hospital', 'loc' => 'Texarkana'],
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
                'name' => 'UAMS Psychiatric Research Institute',
            ],
            [
                'location' => 'Little Rock',
                'type' => 'university_hospital',
            ]
        );

        // 6. VA Facilities
        $vaFacilities = [
            ['name' => 'Fayetteville VA Medical Center', 'loc' => 'Fayetteville'],
            ['name' => 'John L. McClellan Memorial Veterans Hospital', 'loc' => 'Little Rock'],
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
