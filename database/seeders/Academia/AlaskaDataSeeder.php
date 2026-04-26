<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class AlaskaDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. State Create
        $state = State::updateOrCreate(
            ['slug' => 'alaska'],
            ['name' => 'Alaska', 'code' => 'AK']
        );

        // 2. Universities
        $universities = [
            [
                'name' => 'Alaska Bible College',
                'psych' => [],
                'neuro' => [],
                'ol' => false,
            ],
            [
                'name' => 'Alaska Pacific University',
                'psych' => ['BA', 'MA', 'PsyD'],
                'neuro' => [],
                'ol' => false,
            ],
            [
                'name' => 'University of Alaska Anchorage',
                'psych' => ['BS', 'BA', 'MS', 'PhD'],
                'neuro' => [],
                'ol' => false,
            ],
            [
                'name' => 'University of Alaska Fairbanks',
                'psych' => ['BS', 'BA', 'M.Ed'],
                'neuro' => ['PhD'],
                'ol' => false,
            ],
            [
                'name' => 'University of Alaska Southeast',
                'psych' => ['BA'],
                'neuro' => [],
                'ol' => false,
            ],
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

        // 3. Alaska তে বর্তমানে MD / DO / MD-PhD Residency নেই
        // তাই এখানে residency insert করা হয়নি

        // 4. State Institutions
        $stateInstitutions = [
            ['name' => 'Alaska Psychiatric Institute', 'loc' => 'Anchorage'],
            ['name' => 'North Star Behavioral Health System', 'loc' => 'Anchorage'],
        ];

        foreach ($stateInstitutions as $inst) {
            AcademiaFacility::updateOrCreate(
                [
                    'state_id' => $state->id,
                    'name' => $inst['name'],
                ],
                [
                    'location' => $inst['loc'],
                    'type' => 'state_institution',
                ]
            );
        }

        // 5. University Hospital
        AcademiaFacility::updateOrCreate(
            [
                'state_id' => $state->id,
                'name' => 'Alaska Regional Hospital Behavioral Health Services',
            ],
            [
                'location' => 'Anchorage',
                'type' => 'university_hospital',
            ]
        );

        // 6. VA Facility
        AcademiaFacility::updateOrCreate(
            [
                'state_id' => $state->id,
                'name' => 'Colonel Mary Louise Rasmuson Campus of the Alaska VA Healthcare System',
            ],
            [
                'location' => 'Anchorage',
                'type' => 'va_facility',
            ]
        );
    }
}
