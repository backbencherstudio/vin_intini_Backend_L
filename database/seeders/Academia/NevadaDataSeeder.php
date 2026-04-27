<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class NevadaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'nevada'],
            ['name' => 'Nevada', 'code' => 'NV']
        );

        $universities = [
            ['name' => 'College of Southern Nevada', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Great Basin College', 'psych' => ['BAS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Nevada State University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Roseman University of Health Sciences', 'psych' => [], 'neuro' => [], 'ol' => false],
            [
                'name' => 'University of Nevada, Las Vegas',
                'psych' => ['BS', 'BS (OL)', 'BA', 'BA (OL)', 'MS', 'PhD'],
                'neuro' => ['BS', 'PhD'],
                'ol' => true
            ],
            [
                'name' => 'University of Nevada, Reno',
                'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'],
                'neuro' => ['BS', 'MS', 'PhD'],
                'ol' => false
            ],
            ['name' => 'Western Nevada College', 'psych' => [], 'neuro' => [], 'ol' => false],
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
                'name' => 'Kirk Kerkorian School of Medicine at UNLV Psychiatry Residency Program',
                'loc' => 'Las Vegas',
                'deg' => ['MD-DO']
            ],
            [
                'name' => 'University of Nevada Reno School of Medicine',
                'loc' => 'Reno',
                'deg' => ['MD-DO']
            ],
            [
                'name' => 'HCA Healthcare Sunrise Health GME/Southern Hills Psychiatry Residency',
                'loc' => 'Las Vegas',
                'deg' => ['MD-DO']
            ],
            [
                'name' => 'Valley Health Psychiatry Residency Program',
                'loc' => 'Las Vegas',
                'deg' => ['MD-DO']
            ],
            [
                'name' => 'University of Nevada School of Medicine',
                'loc' => 'Reno',
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
            ['name' => 'Desert Willow Treatment Center', 'loc' => 'Las Vegas', 'type' => 'state_institution'],
            ['name' => 'Montevista Hospital', 'loc' => 'Las Vegas', 'type' => 'state_institution'],
            ['name' => 'Seven Hills Behavioral Health Hospital', 'loc' => 'Henderson', 'type' => 'state_institution'],
            ['name' => 'Spring Mountain Treatment Center', 'loc' => 'Las Vegas', 'type' => 'state_institution'],
            ['name' => 'West Hills Hospital', 'loc' => 'Reno', 'type' => 'state_institution'],
            ['name' => 'Willow Springs RTC', 'loc' => 'Reno', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facility
            ['name' => 'North Las Vegas VA Medical Center', 'loc' => 'North Las Vegas', 'type' => 'va_facility'],
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
