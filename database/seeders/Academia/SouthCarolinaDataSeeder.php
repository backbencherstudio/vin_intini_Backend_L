<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class SouthCarolinaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'south-carolina'],
            ['name' => 'South Carolina', 'code' => 'SC']
        );

        $universities = [
            ['name' => 'Allen University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Anderson University', 'psych' => ['BS', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Benedict College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bob Jones University', 'psych' => ['BS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Charleston Southern University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'The Citadel', 'psych' => ['BA', 'MA', 'MEd', 'EdS'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Claflin University', 'psych' => ['BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Clemson University', 'psych' => ['BS', 'BA', 'MS', 'EdS', 'MEd', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Coastal Carolina University', 'psych' => ['BS', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Coker College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'College of Charleston', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Columbia College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Columbia International University', 'psych' => ['BS', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Converse University', 'psych' => ['BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Erskine College', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Francis Marion University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Furman University', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Lander University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Medical University of South Carolina', 'psych' => ['MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Morris College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Newberry College', 'psych' => ['BA', 'BA (OL)'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'North Greenville University', 'psych' => ['BS', 'BS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Presbyterian College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'South Carolina State University', 'psych' => ['BS', 'MA', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Southern Wesleyan University', 'psych' => ['BS', 'BS (OL)', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of South Carolina - Aiken', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of South Carolina - Beaufort', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of South Carolina- Columbia', 'psych' => ['BS', 'BA', 'MS', 'MA', 'MEd', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'University of South Carolina - Upstate', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Voorhees College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Winthrop University', 'psych' => ['BA', 'MS', 'MEd', 'MEd (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Wofford College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'Prisma Health/University of South Carolina SOM Columbia', 'loc' => 'Columbia', 'deg' => ['MD-DO']],
            ['name' => 'Medical University of South Carolina', 'loc' => 'Charleston', 'deg' => ['MD-DO']],
            ['name' => 'Prisma Health/University of South Carolina SOM Greenville', 'loc' => 'Greenville', 'deg' => ['MD-DO']],
            ['name' => 'Prisma Health/Univ of South Carolina SOM Greenville (Greer) Psychiatry Residency', 'loc' => 'Greer', 'deg' => ['MD-DO']],
            ['name' => 'Medical University of South Carolina College of Medicine', 'loc' => 'Orangeburg', 'deg' => ['MD-DO']],
            ['name' => 'Medical University of South Carolina', 'loc' => 'Charleston', 'deg' => ['MD-PhD']],
            ['name' => 'University of South Carolina School of Medicine', 'loc' => 'Columbia', 'deg' => ['MD-PhD']],
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
            ['name' => 'Anderson-Oconee-Pickens Mental Health Center', 'loc' => 'Anderson', 'type' => 'state_institution'],
            ['name' => 'Carolina Center for Behavioral Health', 'loc' => 'Greer', 'type' => 'state_institution'],
            ['name' => 'Charleston Dorchester Community Mental Health Center', 'loc' => 'Charleston', 'type' => 'state_institution'],
            ['name' => 'Earle E. Morris Jr. Alcohol & Drug Addiction Treatment Cntr', 'loc' => 'Columbia', 'type' => 'state_institution'],
            ['name' => 'Greenville Mental Health Center', 'loc' => 'Greenville', 'type' => 'state_institution'],
            ['name' => 'G. Werber Bryan Psychiatric Hospital', 'loc' => 'Columbia', 'type' => 'state_institution'],
            ['name' => 'Patrick B Harris Psychiatric Hospital', 'loc' => 'Anderson', 'type' => 'state_institution'],
            ['name' => 'Rebound Behavioral Health Hospital', 'loc' => 'Lancaster', 'type' => 'state_institution'],
            ['name' => 'SpringBrook Behavioral', 'loc' => 'Travelers Rest', 'type' => 'state_institution'],
            ['name' => 'Three Rivers Center for Behavioral Health', 'loc' => 'West Columbia', 'type' => 'state_institution'],
            ['name' => 'William S. Hall Psychiatric Institute', 'loc' => 'Columbia', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities (Includes out of state as listed in the slide)
            ['name' => 'Carl Vinson Veterans\' Administration Medical Center', 'loc' => 'Dublin, GA', 'type' => 'va_facility'],
            ['name' => 'Charlie Norwood Department of Veterans Affairs Medical Center', 'loc' => 'Augusta, GA', 'type' => 'va_facility'],
            ['name' => 'Joseph Maxwell Cleland Atlanta VA Medical Center', 'loc' => 'Decatur, GA', 'type' => 'va_facility'],
            ['name' => 'Asheville VA Medical Center', 'loc' => 'Asheville, NC', 'type' => 'va_facility'],
            ['name' => 'W.G. (Bill) Hefner Salisbury Department of Veterans Affairs Medical Center', 'loc' => 'Salisbury, NC', 'type' => 'va_facility'],
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
