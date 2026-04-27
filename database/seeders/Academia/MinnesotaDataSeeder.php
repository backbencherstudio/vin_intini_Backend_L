<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class MinnesotaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'minnesota'],
            ['name' => 'Minnesota', 'code' => 'MN']
        );

        $universities = [
            ['name' => 'Adler Graduate School', 'psych' => ['MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Augsburg University', 'psych' => ['BA', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bemidji State University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bethany Lutheran College', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bethel University', 'psych' => ['BS', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Carleton College', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => "College of St Benedict/St John's Univ", 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'College of St. Scholastica', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Concordia College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Concordia University at St. Paul', 'psych' => ['BS', 'BS (OL)', 'BA'], 'neuro' => [], 'ol' => true],
            ['name' => 'Crown College', 'psych' => ['BS (OL)', 'BA', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Dunwoody College of Technology', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Gustavus Adolphus College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Hamline University', 'psych' => ['BA', 'BA (OL)'], 'neuro' => ['BS', 'BA'], 'ol' => true],
            ['name' => 'Macalester College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Martin Luther College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Mayo Clinic College of Med & Sci', 'psych' => [], 'neuro' => ['PhD'], 'ol' => false],
            ['name' => 'Metropolitan State University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Minneapolis College of Art and Design', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Minnesota State University', 'psych' => ['BS', 'MS', 'MA', 'PsyD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Mitchell Hamline School of Law', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'North Central University', 'psych' => ['BS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northwestern Health Sciences University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Oak Hills Christian College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Rasmussen University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => "Saint Mary's University of Minnesota", 'psych' => ['BA', 'BA (OL)', 'MS', 'MS (OL)', 'PsyD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Southwest Minnesota State University', 'psych' => ['BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'St. Catherine University', 'psych' => ['BA', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'St. Cloud State University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'St. Olaf College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Minnesota – Crookston', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Minnesota – Duluth', 'psych' => ['BASc', 'BASc (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of Minnesota – Morris', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Minnesota – Rochester', 'psych' => [], 'neuro' => [], 'ol' => false],

            ['name' => 'University of Minnesota – Twin Cities', 'psych' => ['BS', 'BA', 'MA', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'University of Northwestern at St. Paul', 'psych' => ['BS', 'BS (OL)', 'BA'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of St. Thomas', 'psych' => ['BA', 'MA', 'PsyD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Winona State University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'University of Minnesota', 'loc' => 'Minneapolis', 'deg' => ['MD-DO']],
            ['name' => 'Mayo Clinic College of Medicine and Science (Rochester)', 'loc' => 'Rochester', 'deg' => ['MD-DO']],
            ['name' => 'Hennepin Healthcare/Regions Hospital', 'loc' => 'Minneapolis', 'deg' => ['MD-DO']],
            ['name' => 'Mayo Medical School', 'loc' => 'Rochester', 'deg' => ['MD-PhD']],
            ['name' => 'University of Minnesota Medical School', 'loc' => 'Minneapolis', 'deg' => ['MD-PhD']],
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
            ['name' => 'Central Minnesota Mental Health Center', 'loc' => 'Buffalo', 'type' => 'state_institution'],
            ['name' => 'Central Minnesota Mental Health Center', 'loc' => 'Elk River', 'type' => 'state_institution'],
            ['name' => 'Central Minnesota Mental Health Center', 'loc' => 'Monticello', 'type' => 'state_institution'],
            ['name' => 'Central Minnesota Mental Health Center', 'loc' => 'St. Cloud', 'type' => 'state_institution'],
            ['name' => 'Mayo Clinic Psychiatry & Psychology Treatment Center', 'loc' => 'Rochester', 'type' => 'state_institution'],
            ['name' => 'Minnesota Public Mental Health Services', 'loc' => 'various', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'St Cloud VA Medical Center', 'loc' => 'St. Cloud', 'type' => 'va_facility'],
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
