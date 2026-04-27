<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class MaineDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'maine'],
            ['name' => 'Maine', 'code' => 'ME']
        );

        $universities = [
            ['name' => 'Bates College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Bowdoin College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Colby College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'College of the Atlantic', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Husson University', 'psych' => ['BS', 'BS (OL)', 'MS'], 'neuro' => [], 'ol' => true],
            ['name' => 'Maine College of Art', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Maine Maritime Academy', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Saint Joseph\'s College of Maine', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Thomas College', 'psych' => ['BA', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of Maine- Augusta', 'psych' => ['BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of Maine- Farmington', 'psych' => ['BA', 'BA+MA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Maine- Fort Kent', 'psych' => ['BS', 'BS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of Maine- Machias', 'psych' => ['BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of Maine- Orono', 'psych' => ['BA', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Maine- Presque Isle', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of New England', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'University of Southern Maine', 'psych' => ['BA', 'BA (OL)', 'MS', 'MS (OL)', 'PsyD'], 'neuro' => [], 'ol' => true],
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
            ['name' => 'MaineHealth Psychiatry Residency Program', 'loc' => 'Portland', 'deg' => ['MD-DO']],
            ['name' => 'MaineHealth A', 'loc' => 'Portland', 'deg' => ['MD-DO']],
            ['name' => 'Eastern Maine Medical Center', 'loc' => 'Bangor', 'deg' => ['MD-DO']],
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
            ['name' => 'Dorothea Dix Psychiatric Center', 'loc' => 'Bangor', 'type' => 'state_institution'],
            ['name' => 'Riverview Psychiatric Center', 'loc' => 'Augusta', 'type' => 'state_institution'],
            ['name' => 'Spring Harbor Hospital', 'loc' => 'South Portland', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
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
