<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class UtahDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'utah'],
            ['name' => 'Utah', 'code' => 'UT']
        );

        $universities = [
            ['name' => 'Brigham Young University', 'psych' => ['BS', 'EdS', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'Southern Utah University', 'psych' => ['BS', 'BA', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Utah', 'psych' => ['BS', 'BA', 'MEd', 'PhD'], 'neuro' => ['PhD'], 'ol' => false],
            ['name' => 'Utah State University', 'psych' => ['BS', 'BA', 'MEd', 'PhD'], 'neuro' => ['PhD'], 'ol' => false],
            ['name' => 'Utah Tech University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Utah Valley University', 'psych' => ['BS', 'BAMS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Weber State University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Westminster College', 'psych' => ['BS', 'MS'], 'neuro' => ['BS'], 'ol' => false],
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
                'name' => 'University of Utah Health Psychiatry Residency Program',
                'loc' => 'Salt Lake City',
                'deg' => ['MD-DO']
            ],
            [
                'name' => 'University of Utah School of Medicine',
                'loc' => 'Salt Lake City',
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
            ['name' => 'Benchmark Behavioral Health System', 'loc' => 'Woods Cross', 'type' => 'state_institution'],
            ['name' => 'Highland Ridge Hospital', 'loc' => 'Midvale', 'type' => 'state_institution'],
            ['name' => 'University Neuropsychiatric Institute', 'loc' => 'Salt Lake City', 'type' => 'state_institution'],
            ['name' => 'Utah State Hospital', 'loc' => 'Provo', 'type' => 'state_institution'],
            ['name' => 'Wasatch Mental Health', 'loc' => 'Provo', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facility
            ['name' => 'George E. Wahlen Department of Veterans Affairs Medical Center', 'loc' => 'Salt Lake City', 'type' => 'va_facility'],
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
