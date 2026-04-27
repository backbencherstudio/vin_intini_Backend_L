<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class NorthDakotaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'north-dakota'],
            ['name' => 'North Dakota', 'code' => 'ND']
        );

        $universities = [
            ['name' => 'Bismarck State College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Dickinson State University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Mayville State University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Minot State University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'North Dakota State University', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Trinity Bible College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Jamestown', 'psych' => ['BS', 'BA', 'BA (OL)', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of Mary', 'psych' => ['BS', 'BS (OL)', 'BA', 'BA (OL)', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of North Dakota', 'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'], 'neuro' => ['PhD'], 'ol' => false],
            ['name' => 'Valley City State University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
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
                'name' => 'University of North Dakota Psychiatry Residency Program',
                'loc' => 'Fargo',
                'deg' => ['MD-DO']
            ],
            [
                'name' => 'University of North Dakota School of Medicine',
                'loc' => 'Grand Forks',
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
            ['name' => 'North Dakota State Hospital', 'loc' => 'Jamestown', 'type' => 'state_institution'],
            ['name' => 'Prairie Saint John\'s Psychiatric Center', 'loc' => 'Fargo', 'type' => 'state_institution'],
            ['name' => 'The Stadter Center', 'loc' => 'Grand Forks', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities (As listed in the ND slide)
            ['name' => 'St Cloud VA Medical Center', 'loc' => 'St. Cloud', 'type' => 'va_facility'],
            ['name' => 'Fort Harrison VA Medical Center', 'loc' => 'Fort Harrison, MT', 'type' => 'va_facility'],
            ['name' => 'Fort Meade VA Medical Center', 'loc' => 'Fort Meade, SD', 'type' => 'va_facility'],
            ['name' => 'Hot Springs VA Medical Center', 'loc' => 'Hot Springs, SD', 'type' => 'va_facility'],
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
