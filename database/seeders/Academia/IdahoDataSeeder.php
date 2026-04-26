<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class IdahoDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'idaho'],
            ['name' => 'Idaho', 'code' => 'ID']
        );

        $universities = [
            ['name' => 'Boise Bible College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Boise State University', 'psych' => ['BS', 'MS', 'MA', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Brigham Young University – Idaho', 'psych' => ['BS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'College of Idaho', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Idaho State University', 'psych' => ['BS', 'BA', 'MS', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lewis-Clark State College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northwest Nazarene University', 'psych' => ['BA', 'MS', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Idaho', 'psych' => ['BS', 'BA', 'MS', 'PsyD', 'PhD'], 'neuro' => ['MS', 'PhD'], 'ol' => false],
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
                'name' => 'University of Washington (Boise)',
                'loc' => 'Boise',
                'deg' => ['MD-DO']
            ],
            [
                'name' => 'HCA/Healthcare/Eastern Idaho Regional Medical Center',
                'loc' => 'Idaho Falls',
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
            ['name' => 'Beacon Hospital', 'loc' => 'Pocatello', 'type' => 'state_institution'],
            ['name' => 'Intermountain Hospital', 'loc' => 'Boise', 'type' => 'state_institution'],

            // University Hospital
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facility
            ['name' => 'Boise VA Medical Center', 'loc' => 'Boise', 'type' => 'va_facility'],
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
