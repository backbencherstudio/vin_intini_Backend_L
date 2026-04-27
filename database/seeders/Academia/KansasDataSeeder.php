<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class KansasDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'kansas'],
            ['name' => 'Kansas', 'code' => 'KS']
        );

        $universities = [
            ['name' => 'Baker University', 'psych' => ['BS', 'BS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Barclay College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Benedictine College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bethany College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bethel College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Central Christian College of Kansas', 'psych' => ['BS', 'BS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Cleveland University-Kansas City', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Donnelly College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Fort Hays State University', 'psych' => ['BS', 'BS (OL)', 'BA', 'BA (OL)', 'MS', 'MS (OL)', 'EdS'], 'neuro' => [], 'ol' => true],
            ['name' => 'Friends University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Kansas State University', 'psych' => ['BS', 'BS (OL)', 'BA', 'BA (OL)', 'MS', 'MS (OL)', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => true],
            ['name' => 'Kansas Wesleyan University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Manhattan Christian College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'McPherson College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'MidAmerica Nazarene University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Newman University', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Ottawa University', 'psych' => ['BS', 'BA', 'BA (OL)', 'MS', 'MA', 'MAE'], 'neuro' => [], 'ol' => true],
            ['name' => 'Pittsburg State University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Southwestern College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Sterling College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Tabor College', 'psych' => ['BA', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Kansas', 'psych' => ['BA', 'MS', 'MA', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'University of Saint Mary', 'psych' => ['BA', 'BA (OL)', 'MS', 'MA'], 'neuro' => [], 'ol' => true],
            ['name' => 'Washburn University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Wichita State University', 'psych' => ['BA', 'MEd', 'EdS', 'PhD'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'University of Kansas', 'loc' => 'Wichita', 'deg' => ['MD-DO']],
            ['name' => 'University of Kansas School of Medicine Psychiatry Residency Program', 'loc' => 'Kansas City', 'deg' => ['MD-DO']],
            ['name' => 'University of Kansas School of Medicine', 'loc' => 'Kansas City', 'deg' => ['MD-PhD']],
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
            ['name' => 'Kansas Neurological Institute', 'loc' => 'Topeka', 'type' => 'state_institution'],
            ['name' => 'Larned State Hospital', 'loc' => 'Larned', 'type' => 'state_institution'],
            ['name' => 'Osawatomie State Hospital', 'loc' => 'Osawatomie', 'type' => 'state_institution'],
            ['name' => 'Parsons State Hospital & Training Center', 'loc' => 'Parsons', 'type' => 'state_institution'],
            ['name' => 'Rainbow Mental Health Facility', 'loc' => 'Kansas City', 'type' => 'state_institution'],
            ['name' => 'Prairie View', 'loc' => 'Newton', 'type' => 'state_institution'],

            // University Hospital (Placeholder from client)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Colmery-O\'Neil Veterans\' Administration Medical Center', 'loc' => 'Topeka', 'type' => 'va_facility'],
            ['name' => 'Dwight D. Eisenhower Department of Veterans Affairs Medical Center', 'loc' => 'Leavenworth', 'type' => 'va_facility'],
            ['name' => 'Robert J. Dole Department of Veterans Affairs Medical Center and Regional Office Center', 'loc' => 'Wichita', 'type' => 'va_facility'],
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
