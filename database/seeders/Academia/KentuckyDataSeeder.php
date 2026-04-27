<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class KentuckyDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'kentucky'],
            ['name' => 'Kentucky', 'code' => 'KY']
        );

        $universities = [
            ['name' => 'Alice Lloyd College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Asbury University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bellarmine University', 'psych' => ['BA', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Berea College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Brescia University', 'psych' => ['BS', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Campbellsville University', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Centre College', 'psych' => ['BA'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'Clear Creek Baptist Bible College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Eastern Kentucky University', 'psych' => ['BS', 'MS', 'MS (OL)', 'MA', 'PsyD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Georgetown College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Kentucky Christian University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Kentucky State University', 'psych' => ['BS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Kentucky Wesleyan College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lindsey Wilson College', 'psych' => ['BA', 'MEd', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Midway University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Morehead State University', 'psych' => ['BS', 'BA', 'MS', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Murray State University', 'psych' => ['BS', 'BA', 'MS', 'MA', 'MAEd', 'EdS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northern Kentucky University', 'psych' => ['BS', 'BS (OL)', 'BA', 'BA (OL)', 'MS', 'MA'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'Spalding University', 'psych' => ['BA', 'MA', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Sullivan University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Thomas More University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Transylvania University', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Union College', 'psych' => ['BS', 'BA', 'MA', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of the Cumberlands', 'psych' => ['BS', 'MA (OL)', 'PhD (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of Kentucky', 'psych' => ['BS', 'BA', 'MS', 'EdS', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'University of Louisville', 'psych' => ['BS', 'MS', 'MEd', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'University of Pikeville', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Western Kentucky University', 'psych' => ['BS', 'BA', 'MA', 'MEd', 'MAE', 'PsyD'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'University of Kentucky College of Medicine', 'loc' => 'Lexington', 'deg' => ['MD-DO']],
            ['name' => 'University of Louisville School of Medicine', 'loc' => 'Louisville', 'deg' => ['MD-DO']],
            ['name' => 'University of Kentucky College of Medicine', 'loc' => 'Lexington', 'deg' => ['MD-PhD']],
            ['name' => 'University of Louisville School of Medicine', 'loc' => 'Louisville', 'deg' => ['MD-PhD']],
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
            ['name' => 'Central State Hospital', 'loc' => 'Louisville', 'type' => 'state_institution'],
            ['name' => 'Eastern State Hospital', 'loc' => 'Lexington', 'type' => 'state_institution'],
            ['name' => 'Lincoln Trail Behavioral Health System', 'loc' => 'Radcliff', 'type' => 'state_institution'],
            ['name' => 'Ridge Behavioral Health System', 'loc' => 'Lexington', 'type' => 'state_institution'],
            ['name' => 'Ten Broeck Hospital', 'loc' => 'Louisville', 'type' => 'state_institution'],
            ['name' => 'Western State Hospital', 'loc' => 'Hopkinsville', 'type' => 'state_institution'],

            // University Hospital (Placeholder from client data)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Robley Rex Department of Veterans Affairs Medical Center', 'loc' => 'Louisville', 'type' => 'va_facility'],
            ['name' => 'Troy Bowling Campus - Lexington', 'loc' => 'Lexington', 'type' => 'va_facility'],
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
