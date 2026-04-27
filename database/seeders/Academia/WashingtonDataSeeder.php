<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class WashingtonDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'washington'],
            ['name' => 'Washington', 'code' => 'WA']
        );

        $universities = [
            ['name' => 'Antioch University Seattle', 'psych' => ['BA', 'MA', 'MA (OL)', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Bellevue College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Bastyr University', 'psych' => ['MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Centralia College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Central Washington University', 'psych' => ['BS', 'BA', 'MS', 'EdS'], 'neuro' => [], 'ol' => false],
            ['name' => 'City University of Seattle', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Columbia Basin College', 'psych' => ['BAS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Cornish College of the Arts', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Eastern Washington University', 'psych' => ['BS', 'BA', 'MS', 'MA', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Evergreen State College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Gonzaga University', 'psych' => ['BA', 'MA', 'EdS', 'PsyD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Heritage University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lake Washington Institute of Technology', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Northwest University', 'psych' => ['BA', 'BA (OL)', 'MA', 'MA (OL)', 'PsyD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Pacific Lutheran University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Seattle Central College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Seattle Pacific University', 'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Seattle University', 'psych' => ['BS', 'BA', 'MA', 'MAEd', 'EdS'], 'neuro' => [], 'ol' => false],
            ['name' => 'St. Martin\'s University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Puget Sound', 'psych' => ['BA', 'MEd'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'University of Washington', 'psych' => ['BS', 'BA', 'MA', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'Walla Walla University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Washington State University', 'psych' => ['BS', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'Western Washington University', 'psych' => ['BS', 'MS', 'MEd'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Whitman College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Whitworth University', 'psych' => ['BA', 'MA', 'MEd'], 'neuro' => ['BS'], 'ol' => false],
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
            ['name' => 'University of Washington Psychiatry Residency Program', 'loc' => 'Seattle', 'deg' => ['MD-DO']],
            ['name' => 'Providence Sacred Heart Medical Center', 'loc' => 'Spokane', 'deg' => ['MD-DO']],
            ['name' => 'University of Washington School of Medicine', 'loc' => 'Seattle', 'deg' => ['MD-PhD']],
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
            ['name' => 'Cascade Behavioral Health Hospital', 'loc' => 'Tukwila', 'type' => 'state_institution'],
            ['name' => 'Fairfax Hospital', 'loc' => 'Seattle', 'type' => 'state_institution'],
            ['name' => 'West Seattle Psychiatric Hospital', 'loc' => 'Seattle', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'American Lake VA Medical Center', 'loc' => 'Tacoma', 'type' => 'va_facility'],
            ['name' => 'Jonathan M. Wainwright Memorial VA Medical Center', 'loc' => 'Walla Walla', 'type' => 'va_facility'],
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
