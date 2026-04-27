<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class WyomingDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'wyoming'],
            ['name' => 'Wyoming', 'code' => 'WY']
        );

        $universities = [
            [
                'name' => 'University of Wyoming',
                'psych' => ['BS', 'BS (OL)', 'MS', 'MS (OL)', 'PhD'],
                'neuro' => ['PhD'],
                'ol' => true
            ],
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


        $facilities = [
            // State Institutions
            ['name' => 'Wyoming Behavioral Institute', 'loc' => 'Casper', 'type' => 'state_institution'],
            ['name' => 'Wyoming State Hospital', 'loc' => 'Evanston', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Cheyenne VA Medical Center', 'loc' => 'Cheyenne', 'type' => 'va_facility'],
            ['name' => 'Sheridan VA Medical Center', 'loc' => 'Sheridan', 'type' => 'va_facility'],
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
