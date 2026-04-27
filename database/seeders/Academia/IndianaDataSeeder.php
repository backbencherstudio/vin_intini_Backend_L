<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class IndianaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'indiana'],
            ['name' => 'Indiana', 'code' => 'IN']
        );

        $universities = [
            ['name' => 'Anderson University', 'psych' => ['BS', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Ball State University', 'psych' => ['BS', 'BA', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bethel University', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Butler University', 'psych' => ['BA', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Calumet College of St. Joseph', 'psych' => ['BS', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'DePauw University', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Earlham College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Franklin College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Goshen College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Grace College & Seminary', 'psych' => ['BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Hanover College', 'psych' => ['BA'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'Holy Cross College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Huntington University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Indiana Institute of Technology', 'psych' => ['BS', 'BS (OL)', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Indiana State University', 'psych' => ['BS', 'BS (OL)', 'MS', 'MA', 'MEd', 'PsyD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Indiana University – Bloomington', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => ['BS', 'BA', 'PhD'], 'ol' => false],
            ['name' => 'Indiana University – Columbus', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Indiana University – East', 'psych' => ['BS', 'BS (OL)', 'MA'], 'neuro' => [], 'ol' => true],
            ['name' => 'Indiana University – Fort Wayne', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Indiana University – Indianapolis', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'Indiana University – Kokomo', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Indiana University – Northwest', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'Indiana University – South Bend', 'psych' => ['BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Indiana University – Southeast', 'psych' => ['BS', 'BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Indiana Wesleyan University', 'psych' => ['BS', 'BS (OL)', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Manchester University', 'psych' => ['BS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Marian University', 'psych' => ['BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Martin University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Oakland City University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Purdue University – Fort Wayne', 'psych' => ['BS', 'BA', 'MSEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Purdue University – Northwest', 'psych' => ['BS', 'BA', 'MS', 'MSEd', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Purdue University – West Lafayette', 'psych' => ['BS', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'Rose-Hulman Institute of Technology', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Saint Mary-of-the-Woods College', 'psych' => ['BS', 'BS (OL)', 'BA', 'MS', 'MA'], 'neuro' => [], 'ol' => true],

            ['name' => "Saint Mary's College", 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Taylor University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Trine University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Evansville', 'psych' => ['BS', 'BA', 'PsyD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'University of Indianapolis', 'psych' => ['BS', 'BA+MS', 'MS', 'MA', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Notre Dame', 'psych' => ['BS', 'BA', 'PhD'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'University of Saint Francis – Fort Wayne', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Southern Indiana', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Valparaiso University', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Vincennes University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Wabash College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'Indiana Univ School of Medicine Psychiatry Residency Program', 'loc' => 'Indianapolis', 'deg' => ['MD-DO']],
            ['name' => 'Community Health Network, Inc', 'loc' => 'Indianapolis', 'deg' => ['MD-DO']],
            ['name' => 'Indiana University School of Medicine', 'loc' => 'Vincennes', 'deg' => ['MD-DO']],
            ['name' => 'Indiana University School of Medicine', 'loc' => 'Merrillville', 'deg' => ['MD-DO']],
            ['name' => 'Parkview Health', 'loc' => 'Fort Wayne', 'deg' => ['MD-DO']],
            ['name' => 'Indiana University School of Medicine', 'loc' => 'Indianapolis', 'deg' => ['MD-PhD']],
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
            ['name' => 'Columbus Behavioral Center', 'loc' => 'Columbus', 'type' => 'state_institution'],
            ['name' => 'Bowen Center', 'loc' => 'Warsaw', 'type' => 'state_institution'],
            ['name' => 'Columbus Hospital', 'loc' => 'Columbus', 'type' => 'state_institution'],
            ['name' => 'Deaconess Cross Pointe', 'loc' => 'Evansville', 'type' => 'state_institution'],
            ['name' => 'Dunn Mental Health Center', 'loc' => 'Richmond', 'type' => 'state_institution'],
            ['name' => 'Fairbanks', 'loc' => 'Indianapolis', 'type' => 'state_institution'],
            ['name' => 'Four County Counseling Center', 'loc' => 'Logansport', 'type' => 'state_institution'],
            ['name' => 'Madison Center & Hospital', 'loc' => 'South Bend', 'type' => 'state_institution'],
            ['name' => 'Madison State Hospital', 'loc' => 'Madison', 'type' => 'state_institution'],
            ['name' => 'Meadows Hospital', 'loc' => 'Bloomington', 'type' => 'state_institution'],
            ['name' => 'Midwest Center for Youth and Families', 'loc' => 'Kouts', 'type' => 'state_institution'],
            ['name' => 'Northern Indiana Hospital', 'loc' => 'Plymouth', 'type' => 'state_institution'],
            ['name' => 'Oaklawn Psychiatric Center', 'loc' => 'Goshen', 'type' => 'state_institution'],
            ['name' => 'Options Behavioral Health Hospital', 'loc' => 'Indianapolis', 'type' => 'state_institution'],
            ['name' => 'Parkview Behavioral Health', 'loc' => 'Fort Wayne', 'type' => 'state_institution'],
            ['name' => 'Richmond State Hospital', 'loc' => 'Richmond', 'type' => 'state_institution'],
            ['name' => 'Southern Hills Counseling Centers', 'loc' => 'Jasper', 'type' => 'state_institution'],
            ['name' => 'Valle Vista Hospital', 'loc' => 'Greenwood', 'type' => 'state_institution'],
            ['name' => 'Wabash Valley Hospital Mental Health Center', 'loc' => 'West Lafayette', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Marion VA Medical Center', 'loc' => 'Marion', 'type' => 'va_facility'],
            ['name' => 'Richard L. Roudebush Veterans\' Administration Medical Center', 'loc' => 'Indianapolis', 'type' => 'va_facility'],
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
