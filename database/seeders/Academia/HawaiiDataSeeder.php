<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class HawaiiDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'hawaii'],
            ['name' => "Hawai'i", 'code' => 'HI']
        );

        $universities = [
            [
                'name' => 'Brigham Young University – Hawaii',
                'psych' => ['BS'],
                'neuro' => [],
                'ol' => false
            ],
            [
                'name' => 'Chaminade University',
                'psych' => ['BS', 'MS', 'PsyD', 'EdD', 'DMFT'],
                'neuro' => [],
                'ol' => false
            ],
            [
                'name' => 'Hawaii Pacific University',
                'psych' => ['BA', 'MA (OL)', 'PsyD'],
                'neuro' => [],
                'ol' => true
            ],
            [
                'name' => 'University of Hawaii at Hilo',
                'psych' => ['BA', 'MA'],
                'neuro' => [],
                'ol' => false
            ],
            [
                'name' => 'University of Hawaii at Manoa',
                'psych' => ['BS', 'BA', 'PhD'],
                'neuro' => [],
                'ol' => false
            ],
            [
                'name' => 'University of Hawaii at West Oahu',
                'psych' => [],
                'neuro' => [],
                'ol' => false
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

        $residencies = [
            [
                'name' => 'University of Hawai\'i Psychiatry Residency Program',
                'loc' => 'Honolulu',
                'deg' => ['MD-DO']
            ],
            [
                'name' => 'Tripler Army Medical Center',
                'loc' => 'Tripler AMC',
                'deg' => ['MD-DO']
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
            ['name' => 'Hawaii State Hospital', 'loc' => 'Kaneohe', 'type' => 'state_institution'],
            ['name' => 'Kahi Mohala Behavioral Health', 'loc' => 'Ewa Beach, Oahu', 'type' => 'state_institution'],

            // University Hospital
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facility
            ['name' => 'Spark M. Matsunaga Department of Veterans Affairs Medical Center', 'loc' => 'Honolulu', 'type' => 'va_facility'],
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
