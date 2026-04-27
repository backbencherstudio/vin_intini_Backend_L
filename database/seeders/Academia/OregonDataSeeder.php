<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class OregonDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'oregon'],
            ['name' => 'Oregon', 'code' => 'OR']
        );

        $universities = [
            ['name' => 'Bushnell University', 'psych' => ['BS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Corban University', 'psych' => ['BS (OL)', 'BA', 'MA'], 'neuro' => [], 'ol' => true],
            ['name' => 'Eastern Oregon University', 'psych' => ['BS', 'BS (OL)', 'BA', 'BA (OL)', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'George Fox University', 'psych' => ['BS', 'BA', 'MA', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lewis & Clark College', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Linfield University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Multnomah University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'New Hope Christian College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Oregon Health & Science University', 'psych' => ['PhD'], 'neuro' => ['PhD', 'MD-PhD'], 'ol' => false],
            ['name' => 'Oregon Institute of Technology', 'psych' => ['BS', 'BS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Oregon State University', 'psych' => ['BS', 'BA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Pacific University', 'psych' => ['BS', 'BA', 'MA', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Portland State University', 'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Reed College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Southern Oregon University', 'psych' => ['BS', 'BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Oregon', 'psych' => ['BS', 'BA', 'MS', 'MS (OL)', 'EdS', 'PhD'], 'neuro' => ['BS', 'BA'], 'ol' => true],
            ['name' => 'University of Portland', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Western States', 'psych' => ['MS (OL)', 'EdD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Warner Pacific University', 'psych' => ['BS', 'BS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Western Oregon University', 'psych' => ['BS', 'BAS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Willamette University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'Oregon Health & Science University (OHSU Health)', 'loc' => 'Portland', 'deg' => ['MD-DO']],
            ['name' => 'Samaritan Health Services-Corvallis', 'loc' => 'Corvallis', 'deg' => ['MD-DO']],
            ['name' => 'Oregon Health & Science University (OHSU Health)/St. Charles Health System', 'loc' => 'Bend', 'deg' => ['MD-DO']],
            ['name' => 'Oregon Health Sciences University School of Medicine', 'loc' => 'Portland', 'deg' => ['MD-PhD']],
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
            ['name' => 'Lane County Psychiatric Hospital', 'loc' => 'Eugene', 'type' => 'state_institution'],
            ['name' => 'Oregon State Hospital', 'loc' => 'Portland', 'type' => 'state_institution'],
            ['name' => 'Oregon State Hospital', 'loc' => 'Salem', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Portland VA Medical Center', 'loc' => 'Portland', 'type' => 'va_facility'],
            ['name' => 'White City VA Medical Center', 'loc' => 'White City', 'type' => 'va_facility'],
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
