<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class RhodeIslandDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'rhode-island'],
            ['name' => 'Rhode Island', 'code' => 'RI']
        );

        $universities = [
            ['name' => 'Brown University', 'psych' => ['Sc.B.', 'A.B.', 'PhD'], 'neuro' => ['Sc.B.', 'A.B.', 'PhD'], 'ol' => false],
            ['name' => 'Bryant University', 'psych' => ['BS', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Johnson & Wales University- Charlotte', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Johnson & Wales University- Providence', 'psych' => ['BS', 'MS', 'MS (OL)', 'MBA', 'MBA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'New England Institute of Technology', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Providence College', 'psych' => ['BA', 'MSEd'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Rhode Island College', 'psych' => ['BS', 'BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Rhode Island School of Design', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Roger Williams University', 'psych' => ['BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Salve Regina University', 'psych' => ['BA', 'MS', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Rhode Island', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
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
                'name' => 'Butler Hospital/Brown University Psychiatry Residency Program',
                'loc' => 'Providence',
                'deg' => ['MD-DO']
            ],
            [
                'name' => 'Brown University School of Medicine',
                'loc' => 'Providence',
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
            ['name' => 'Bradley Hospital', 'loc' => 'East Providence', 'type' => 'state_institution'],
            ['name' => 'Butler Hospital', 'loc' => 'Providence', 'type' => 'state_institution'],
            ['name' => 'Duncan Lodge', 'loc' => 'Providence', 'type' => 'state_institution'],
            ['name' => 'Eleanor Slater Hospital', 'loc' => 'Cranston', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'West Haven VA Medical Center', 'loc' => 'West Haven, CT', 'type' => 'va_facility'],
            ['name' => 'Brockton VA Medical Center', 'loc' => 'Brockton, MA', 'type' => 'va_facility'],
            ['name' => 'Jamaica Plain VA Medical Center', 'loc' => 'Boston, MA', 'type' => 'va_facility'],
            ['name' => 'Northport VA Medical Center', 'loc' => 'Northport, NY', 'type' => 'va_facility'],
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
