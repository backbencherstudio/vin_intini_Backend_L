<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class MontanaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'montana'],
            ['name' => 'Montana', 'code' => 'MT']
        );

        $universities = [
            ['name' => 'Carroll College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Montana State University - Billings', 'psych' => ['BS', 'BS+MS', 'MS', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Montana State University - Bozeman', 'psych' => ['BS', 'MS', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Montana State University - Northern', 'psych' => ['BA (OL)', 'MEd'], 'neuro' => [], 'ol' => true],
            ['name' => 'Montana Technological University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Rocky Mountain College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Montana', 'psych' => ['BA', 'PhD'], 'neuro' => ['BS', 'BS+MS', 'PhD'], 'ol' => false],
            ['name' => 'University of Montana Western', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Providence', 'psych' => ['BA', 'BA (OL)', 'MS (OL)'], 'neuro' => [], 'ol' => true],
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
            ['name' => 'Billings Clinic', 'loc' => 'Billings', 'deg' => ['MD-DO']],
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
            // State Institution
            ['name' => 'Montana State Hospital', 'loc' => 'Warm Springs', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facility
            ['name' => 'Fort Harrison VA Medical Center', 'loc' => 'Fort Harrison', 'type' => 'va_facility'],
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
