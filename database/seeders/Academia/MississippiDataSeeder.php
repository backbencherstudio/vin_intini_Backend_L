<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class MississippiDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'mississippi'],
            ['name' => 'Mississippi', 'code' => 'MS']
        );

        $universities = [
            ['name' => 'Alcorn State University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Belhaven University', 'psych' => ['BS', 'BA', 'BA (OL)', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Blue Mountain Christian University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Delta State University', 'psych' => ['BA', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Jackson State University', 'psych' => ['BS', 'MS', 'EdS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Millsaps College', 'psych' => ['BS', 'BA'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'Mississippi College', 'psych' => ['BS', 'BA', 'MS', 'EdS (OL)'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'Mississippi State University', 'psych' => ['BS', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Mississippi University for Women', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Mississippi Valley State University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Rust College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Southeastern Baptist College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Tougaloo College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Mississippi', 'psych' => ['BS', 'BA', 'MEd', 'EdS (OL)', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of Mississippi Medical Center', 'psych' => [], 'neuro' => ['PhD'], 'ol' => false],
            ['name' => 'University of Southern Mississippi', 'psych' => ['BS', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'William Carey University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'University of Mississippi Medical Center Psychiatry Residency Program', 'loc' => 'Jackson', 'deg' => ['MD-DO']],
            ['name' => 'Mississippi State Hospital', 'loc' => 'Whitfield', 'deg' => ['MD-DO']],
            ['name' => 'University of Mississippi School of Medicine', 'loc' => 'Jackson', 'deg' => ['MD-PhD']],
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
            ['name' => 'East Mississippi State Hospital', 'loc' => 'Meridian', 'type' => 'state_institution'],
            ['name' => 'Mississippi State Hospital', 'loc' => 'Whitfield', 'type' => 'state_institution'],
            ['name' => 'North Mississippi State Hospital', 'loc' => 'Tupelo', 'type' => 'state_institution'],
            ['name' => 'Parkwood Behavioral Health System', 'loc' => 'Olive Branch', 'type' => 'state_institution'],
            ['name' => 'South Mississippi State Hospital', 'loc' => 'Purvis', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Biloxi VA Medical Center', 'loc' => 'Biloxi', 'type' => 'va_facility'],
            ['name' => 'G.V. (Sonny) Montgomery Department of Veterans Affairs Medical Center', 'loc' => 'Jackson', 'type' => 'va_facility'],
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
