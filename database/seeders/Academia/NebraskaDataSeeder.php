<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class NebraskaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'nebraska'],
            ['name' => 'Nebraska', 'code' => 'NE']
        );

        $universities = [
            ['name' => 'Bellevue University', 'psych' => ['BS', 'BS (OL)', 'BA', 'MS (OL)', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Bryan College of Health Sciences', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Chadron State College', 'psych' => ['BA', 'MA', 'MSEd', 'EdS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Clarkson College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'College of Saint Mary', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Concordia University', 'psych' => ['BS', 'BA', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Creighton University', 'psych' => ['BS', 'MS', 'MA (OL)'], 'neuro' => ['BS', 'BS+MS', 'MS', 'PhD'], 'ol' => true],
            ['name' => 'Doane University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Hastings College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Midland University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Nebraska Wesleyan University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Peru State College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Union College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Nebraska Lincoln', 'psych' => ['BS', 'BA', 'PhD'], 'neuro' => ['BS', 'BA', 'PhD'], 'ol' => false],
            ['name' => 'University of Nebraska at Kearney', 'psych' => ['BS', 'BA', 'MSEd', 'EdS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Nebraska Medical Center', 'psych' => ['MS'], 'neuro' => ['PhD'], 'ol' => false],
            ['name' => 'University of Nebraska at Omaha', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => ['BS', 'MA', 'PhD'], 'ol' => false],
            ['name' => 'Wayne State College', 'psych' => ['BS', 'BA', 'MSE'], 'neuro' => [], 'ol' => false],
            ['name' => 'York University', 'psych' => ['BS', 'BS (OL)'], 'neuro' => [], 'ol' => true],
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
            ['name' => 'Creighton University School of Medicine (Omaha)', 'loc' => 'Omaha', 'deg' => ['MD-DO']],
            ['name' => 'Univ of Nebraska Medical Center College of Medicine Psychiatry Residency Program', 'loc' => 'Omaha', 'deg' => ['MD-DO']],
            ['name' => 'Creighton University School of Medicine', 'loc' => 'Omaha', 'deg' => ['MD-PhD']],
            ['name' => 'University of Nebraska College of Medicine', 'loc' => 'Omaha', 'deg' => ['MD-PhD']],
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
            ['name' => 'Alegent Mental Health Services', 'loc' => 'Omaha', 'type' => 'state_institution'],
            ['name' => 'Blue Valley Behavioral Health', 'loc' => 'Southeast Nebraska', 'type' => 'state_institution'],
            ['name' => 'Children\'s Hospital Behavioral Health', 'loc' => 'Omaha', 'type' => 'state_institution'],
            ['name' => 'Good Samaritan Behavioral Health', 'loc' => 'Kearney', 'type' => 'state_institution'],
            ['name' => 'Panhandle Mental Health Centers', 'loc' => 'Scottsbluff', 'type' => 'state_institution'],
            ['name' => 'University of Nebraska Medical Center Behavioral Health Clinics', 'loc' => null, 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Grand Island VA Medical Center', 'loc' => 'Grand Island', 'type' => 'va_facility'],
            ['name' => 'Omaha VA Medical Center', 'loc' => 'Omaha', 'type' => 'va_facility'],
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
