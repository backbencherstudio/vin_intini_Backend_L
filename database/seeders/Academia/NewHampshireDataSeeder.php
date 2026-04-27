<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class NewHampshireDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'new-hampshire'],
            ['name' => 'New Hampshire', 'code' => 'NH']
        );

        $universities = [
            ['name' => 'Antioch University New England', 'psych' => ['MA', 'MA (OL)', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Colby-Sawyer College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Dartmouth College', 'psych' => ['A.B.', 'PhD'], 'neuro' => ['A.B.', 'PhD'], 'ol' => false],
            ['name' => 'Franklin Pierce University', 'psych' => ['BS', 'BA', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of New Hampshire- Durham', 'psych' => ['BA', 'MA', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'University of New Hampshire- Manchester', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Keene State College', 'psych' => ['BA'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'New England College', 'psych' => ['BA', 'BA (OL)', 'MS', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Plymouth State University', 'psych' => ['BS', 'BA', 'MS', 'MEd', 'EdS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Rivier University', 'psych' => ['BA', 'BA (OL)', 'MA', 'MEd', 'MEd (OL)', 'PsyD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Saint Anselm College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Southern New Hampshire University', 'psych' => ['BA', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Thomas More College of Liberal Arts', 'psych' => [], 'neuro' => [], 'ol' => false],
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
            ['name' => 'Dartmouth-Hitchcock/Mary Hitchcock Memorial Hospital', 'loc' => 'Lebanon', 'deg' => ['MD-DO']],
            ['name' => 'HCA Healthcare/Tufts Univ School of Med: Portsmouth Regional Hosp', 'loc' => 'Portsmouth', 'deg' => ['MD-DO']],
            ['name' => 'Geisel School of Medicine at Dartmouth', 'loc' => 'Hanover', 'deg' => ['MD-PhD']],
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
            ['name' => 'New Hampshire Hospital', 'loc' => 'Concord', 'type' => 'state_institution'],
            ['name' => 'Hampstead Hospital', 'loc' => 'Hampstead', 'type' => 'state_institution'],
            ['name' => 'West Central Behavioral Health', 'loc' => 'Lebanon', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities (Listed for NH in the slide)
            ['name' => 'Brockton VA Medical Center', 'loc' => 'Brockton, MA', 'type' => 'va_facility'],
            ['name' => 'Edith Nourse Rogers Memorial Veterans\' Hospital', 'loc' => 'Bedford, MA', 'type' => 'va_facility'],
            ['name' => 'Jamaica Plain VA Medical Center', 'loc' => 'Boston, MA', 'type' => 'va_facility'],
            ['name' => 'Northampton VA Medical Center', 'loc' => 'Leeds, MA', 'type' => 'va_facility'],
            ['name' => 'White River Junction VA Medical Center', 'loc' => 'White River Junction, VT', 'type' => 'va_facility'],
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
