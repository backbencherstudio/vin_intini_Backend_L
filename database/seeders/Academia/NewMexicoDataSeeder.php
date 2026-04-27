<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class NewMexicoDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'new-mexico'],
            ['name' => 'New Mexico', 'code' => 'NM']
        );

        $universities = [
            ['name' => 'Eastern New Mexico University', 'psych' => ['BS', 'BA', 'MA', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'New Mexico Highlands University', 'psych' => ['BS', 'BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'New Mexico Institute of Mining and Tech', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'New Mexico State University', 'psych' => ['BA', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northern New Mexico College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Southwestern College, Santa Fe', 'psych' => ['MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'St. John\'s College at Santa Fe', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'University of New Mexico', 'psych' => ['BS', 'BA', 'PhD'], 'neuro' => ['PhD'], 'ol' => false],
            ['name' => 'University of the Southwest', 'psych' => ['BS', 'MS', 'EdD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Western New Mexico University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'University of New Mexico School of Medicine', 'loc' => 'Albuquerque', 'deg' => ['MD-DO']],
            ['name' => 'University of New Mexico School of Medicine', 'loc' => 'Albuquerque', 'deg' => ['MD-PhD']],
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
            ['name' => 'Desert Hills Hospital', 'loc' => 'Albuquerque', 'type' => 'state_institution'],
            ['name' => 'Memorial Hospital', 'loc' => 'Albuquerque', 'type' => 'state_institution'],
            ['name' => 'Mesilla Valley Hospital', 'loc' => 'Las Cruces', 'type' => 'state_institution'],
            ['name' => 'Peak Psychiatric Hospital', 'loc' => 'Santa Teresa', 'type' => 'state_institution'],
            ['name' => 'Turquoise Lodge', 'loc' => 'Albuquerque', 'type' => 'state_institution'],
            ['name' => 'UNM Psychiatric Center', 'loc' => 'Albuquerque', 'type' => 'state_institution'],

            // University Hospital (Placeholder from client data)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facility
            ['name' => 'Raymond G. Murphy Department of Veterans Affairs Medical Center', 'loc' => 'Albuquerque', 'type' => 'va_facility'],
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
