<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class TennesseeDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'tennessee'],
            ['name' => 'Tennessee', 'code' => 'TN']
        );

        $universities = [
            ['name' => 'American Baptist College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Aquinas College, Tennessee', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Baptist Health Sciences University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Austin Peay State University', 'psych' => ['BS', 'MS', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Belmont University', 'psych' => ['BS', 'BA', 'MA', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Bethel University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bryan College', 'psych' => ['BS', 'BA+MA', 'BS+MA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Carson-Newman University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Christian Brothers University', 'psych' => ['BA', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Cumberland University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'East Tennessee State University', 'psych' => ['BS', 'BA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Fisk University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Freed-Hardeman University', 'psych' => ['BS', 'MS', 'DBH'], 'neuro' => [], 'ol' => false],
            ['name' => 'King University', 'psych' => ['BS', 'BS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Lane College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Lee University', 'psych' => ['BA', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'LeMoyne-Owen College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Lincoln Memorial University', 'psych' => ['BS', 'MS', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lipscomb University', 'psych' => ['BS', 'BS (OL)', 'BA', 'MS', 'EdS', 'MEd'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'Maryville College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Meharry Medical College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Middle Tennessee State University', 'psych' => ['BS', 'MA', 'EdS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Milligan College', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Richmont Graduate University', 'psych' => ['MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Rhodes College', 'psych' => ['BS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Sewanee University of the South', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'South College', 'psych' => ['BS (OL)', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Southern Adventist University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Southern College of Optometry', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Tennessee State University', 'psych' => ['BS', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Tennessee Technological University', 'psych' => ['BS', 'MA', 'EdS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Tennessee Wesleyan University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Trevecca Nazarene University', 'psych' => ['BS', 'BA (OL)', 'MA', 'MMFC', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Tusculum University', 'psych' => ['BS', 'BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],

            ['name' => 'Union University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Memphis', 'psych' => ['BS', 'BA', 'MS', 'MA', 'EdS', 'PhD'], 'neuro' => ['BS', 'BA', 'PhD'], 'ol' => false],
            ['name' => 'University of Tennessee – Chattanooga', 'psych' => ['BS', 'MS', 'MEd', 'EdS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Tennessee – Knoxville', 'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'University of Tennessee – Martin', 'psych' => ['BS', 'BA', 'MSEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Tennessee – Southern', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Univ of Tennessee Health Science Center', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Vanderbilt University', 'psych' => ['BS', 'BA', 'MS', 'MEd', 'MGC', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'Welch College', 'psych' => ['BS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Williamson College', 'psych' => [], 'neuro' => [], 'ol' => false],
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
            ['name' => 'Meharry Medical College', 'loc' => 'Nashville', 'deg' => ['MD-DO']],
            ['name' => 'Vanderbilt University Medical Center', 'loc' => 'Nashville', 'deg' => ['MD-DO']],
            ['name' => 'University of Tennessee Psychiatry Residency Program', 'loc' => 'Memphis', 'deg' => ['MD-DO']],
            ['name' => 'East Tennessee State University/Quillen College of Medicine', 'loc' => 'Johnson City', 'deg' => ['MD-DO']],
            ['name' => 'HCA Healthcare/TriStar Nashville/Centennial Med Center Psychiatry Residency', 'loc' => 'Nashville', 'deg' => ['MD-DO']],
            ['name' => 'East Tennessee State University James H. Quillen College of Medicine', 'loc' => 'Johnson City', 'deg' => ['MD-PhD']],
            ['name' => 'Meharry Medical College School of Medicine', 'loc' => 'Nashville', 'deg' => ['MD-PhD']],
            ['name' => 'University of Tennessee, Memphis College of Medicine', 'loc' => 'Memphis', 'deg' => ['MD-PhD']],
            ['name' => 'Vanderbilt University School of Medicine', 'loc' => 'Nashville', 'deg' => ['MD-PhD']],
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
            ['name' => 'Delta Med Center', 'loc' => 'Memphis', 'type' => 'state_institution'],
            ['name' => 'Indian Path Pavilion', 'loc' => 'Kingsport', 'type' => 'state_institution'],
            ['name' => 'Lakeside Behavioral Health System', 'loc' => 'Memphis', 'type' => 'state_institution'],
            ['name' => 'Parkridge Valley Hospital', 'loc' => 'Chattanooga', 'type' => 'state_institution'],
            ['name' => 'Parthenon Pavilion - Psychiatric hospital', 'loc' => 'Nashville', 'type' => 'state_institution'],
            ['name' => 'Peninsula Behavioral Health', 'loc' => 'Knoxville', 'type' => 'state_institution'],
            ['name' => 'Peninsula Hospital', 'loc' => 'Louisville', 'type' => 'state_institution'],
            ['name' => 'Peninsula Village', 'loc' => 'Louisville', 'type' => 'state_institution'],
            ['name' => 'Ridgeview Psychiatric Hospital & Center', 'loc' => 'Oak Ridge', 'type' => 'state_institution'],
            ['name' => 'Lakeshore Mental Health Institute', 'loc' => 'Knoxville', 'type' => 'state_institution'],
            ['name' => 'Memphis Mental Health Institute', 'loc' => 'Memphis', 'type' => 'state_institution'],
            ['name' => 'Middle Tennessee Mental Health Institute', 'loc' => 'Nashville', 'type' => 'state_institution'],
            ['name' => 'Moccasin Bend Mental Health Institute', 'loc' => 'Chattanooga', 'type' => 'state_institution'],
            ['name' => 'Trustpoint Hospital', 'loc' => 'Murfreesboro', 'type' => 'state_institution'],
            ['name' => 'Valley Hospital', 'loc' => 'Chattanooga', 'type' => 'state_institution'],
            ['name' => 'Western Mental Health Institute', 'loc' => 'Bolivar', 'type' => 'state_institution'],
            ['name' => 'Woodridge Hospital', 'loc' => 'Johnson City', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Alvin C. York Veterans\' Administration Medical Center', 'loc' => 'Murfreesboro', 'type' => 'va_facility'],
            ['name' => 'James H. Quillen Department of Veterans Affairs Medical Center', 'loc' => 'Mountain Home', 'type' => 'va_facility'],
            ['name' => 'Lt. Col. Luke Weathers, Jr. VA Medical Center', 'loc' => 'Memphis', 'type' => 'va_facility'],
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
