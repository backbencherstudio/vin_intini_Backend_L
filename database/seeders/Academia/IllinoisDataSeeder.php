<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class IllinoisDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'illinois'],
            ['name' => 'Illinois', 'code' => 'IL']
        );

        $universities = [
            ['name' => 'Adler University', 'psych' => ['MA', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Augustana College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Aurora University', 'psych' => ['BA', 'BA (OL)'], 'neuro' => ['BA'], 'ol' => true],
            ['name' => 'Benedictine University', 'psych' => ['BA', 'BA (OL)', 'MS'], 'neuro' => [], 'ol' => true],
            ['name' => 'Blackburn College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Blessing-Rieman Coll of Nursing & H-Sci', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Bradley University', 'psych' => ['BS', 'MA', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Chamberlain University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Chicago State University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Columbia College Chicago', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Concordia University Chicago', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'DePaul University', 'psych' => ['BS', 'BA', 'BA+MS', 'MS', 'MA', 'MEd', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Dominican University', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Eastern Illinois University', 'psych' => ['BA', 'BA (OL)', 'MA'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'East-West University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Elmhurst College', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Eureka College', 'psych' => ['BS', 'BS+MS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Governors State University', 'psych' => ['BA', 'MA', 'MHS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Greenville University', 'psych' => ['BS', 'BS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Illinois College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Illinois Institute of Technology', 'psych' => ['BS', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Illinois State University', 'psych' => ['BS', 'BA', 'MA', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'Illinois Wesleyan University', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Judson University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Knox College', 'psych' => ['BA'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'Lake Forest College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Lewis University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Loyola University', 'psych' => ['BS', 'BS+MA', 'MA', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'McKendree University', 'psych' => ['BA', 'MA'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'Methodist College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Midwestern University', 'psych' => ['PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Millikin University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Monmouth College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Moody Bible Institute', 'psych' => ['BS (OL)', 'MA', 'MA (OL)'], 'neuro' => [], 'ol' => true],

            ['name' => 'National Louis Univ at Chicago', 'psych' => ['BA', 'MS', 'MA', 'EdS', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'National Univ of Health Sci', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'North Central College', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'North Park University', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northeastern Illinois University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northern Illinois University- DeKalb', 'psych' => ['BS', 'BA', 'MS', 'MA', 'MSEd', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northern Illinois University- Harper', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northern Illinois University- McHenry Co', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northwestern University', 'psych' => ['BS', 'BA', 'MS', 'MA', 'MA (OL)', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => true],
            ['name' => 'Olivet Nazarene University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Principia College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Quincy University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Rosalind Franklin Univ of Med & Science', 'psych' => ['MS', 'PhD'], 'neuro' => ['MS', 'PhD'], 'ol' => false],
            ['name' => 'Rockford University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Roosevelt University- Chicago', 'psych' => ['BA', 'MA', 'MA (OL)', 'PsyD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Roosevelt University- Schaumburg', 'psych' => ['BA', 'MA', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Saint Augustine College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Saint Francis Med Center Coll of Nursing', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Saint Xavier University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'School of the Art Inst Chicago', 'psych' => ['MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Southern Illinois University', 'psych' => ['BA', 'BA (OL)', 'MS', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Trinity Christian College', 'psych' => ['BS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Trinity International University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Chicago', 'psych' => ['BA', 'BA+MA', 'PhD'], 'neuro' => ['BS', 'BA', 'PhD'], 'ol' => false],
            ['name' => 'University of Illinois Chicago', 'psych' => ['BS', 'BA', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'University of Illinois Urbana-Champaign', 'psych' => ['BS', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Illinois Springfield', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of St. Francis', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Western Illinois University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Wheaton College', 'psych' => ['BA', 'MA', 'PsyD', 'PhD'], 'neuro' => ['BS', 'BA'], 'ol' => false],
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
            ['name' => 'McGaw Medical Center of Northwestern University', 'loc' => 'Chicago', 'deg' => ['MD-DO']],
            ['name' => 'Rush University Medical Center', 'loc' => 'Chicago', 'deg' => ['MD-DO']],
            ['name' => 'Univ of Illinois College of Medicine at Chicago Psychiatry Residency Program', 'loc' => 'Chicago', 'deg' => ['MD-DO']],
            ['name' => 'Loyola University Medical Center', 'loc' => 'Maywood', 'deg' => ['MD-DO']],
            ['name' => 'Advocate Health Care/Advocate Lutheran General Hospital', 'loc' => 'Park Ridge', 'deg' => ['MD-DO']],
            ['name' => 'Chicago Med School/Rosalind Franklin Univ of Med & Sci Psychiatry Residency Program', 'loc' => 'North Chicago', 'deg' => ['MD-DO']],
            ['name' => 'Southern Illinois University', 'loc' => 'Springfield', 'deg' => ['MD-DO']],
            ['name' => 'University of Chicago', 'loc' => 'Chicago', 'deg' => ['MD-DO']],
            ['name' => 'University of Illinois College of Medicine at Peoria Psychiatry Residency Progra', 'loc' => 'Peoria', 'deg' => ['MD-DO']],
            ['name' => 'Carle Foundation Hospital', 'loc' => 'Urbana', 'deg' => ['MD-DO']],
            ['name' => 'Riverside Medical Center', 'loc' => 'Kankakee', 'deg' => ['MD-DO']],
            ['name' => 'Loretto Hospital', 'loc' => 'Chicago', 'deg' => ['MD-DO']],
            ['name' => 'University of Chicago Medical Center', 'loc' => 'Evanston', 'deg' => ['MD-DO']],
            ['name' => 'Loyola University of Chicago - Stritch School of Medicine', 'loc' => 'Maywood', 'deg' => ['MD-PhD']],
            ['name' => 'Northwestern University Medical School', 'loc' => 'Chicago', 'deg' => ['MD-PhD']],
            ['name' => 'Rosalind Franklin Univ of Medicine and Science - Chicago Med School', 'loc' => 'North Chicago', 'deg' => ['MD-PhD']],
            ['name' => 'University of Chicago Pritzker School of Medicine', 'loc' => 'Chicago', 'deg' => ['MD-PhD', 'MTSP']],
            ['name' => 'University of Chicago Pritzker School of Medicine', 'loc' => 'Chicago', 'deg' => ['MD-PhD']],
            ['name' => 'University of Illinois at Chicago College of Medicine', 'loc' => 'Chicago', 'deg' => ['MD-PhD']],
            ['name' => 'University of Illinois at Urbana-Champaign Carle Illinois College of Medicine', 'loc' => 'Urbana', 'deg' => ['MD-PhD']],
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
            ['name' => 'Alexian Brothers Behavioral Health Hospital', 'loc' => 'Hoffman Estates', 'type' => 'state_institution'],
            ['name' => 'Chicago Lakeshore Hospital', 'loc' => 'Chicago', 'type' => 'state_institution'],
            ['name' => 'HartGrove Hospital', 'loc' => 'Chicago', 'type' => 'state_institution'],
            ['name' => 'Linden Oaks Hospital at Edward', 'loc' => 'Naperville', 'type' => 'state_institution'],
            ['name' => 'Pavilion Behavioral Health System, The', 'loc' => 'Champaign', 'type' => 'state_institution'],
            ['name' => 'Riveredge Hospital', 'loc' => 'Forest Park', 'type' => 'state_institution'],
            ['name' => 'Streamwood Hospital', 'loc' => 'Streamwood', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Captain James A. Lovell Federal Health Care Center', 'loc' => 'North Chicago', 'type' => 'va_facility'],
            ['name' => 'Danville VA Medical Center', 'loc' => 'Danville', 'type' => 'va_facility'],
            ['name' => 'Edward Hines Junior Hospital', 'loc' => 'Hines', 'type' => 'va_facility'],
            ['name' => 'Jesse Brown Department of Veterans Affairs Medical Center', 'loc' => 'Chicago', 'type' => 'va_facility'],
            ['name' => 'Marion VA Medical Center', 'loc' => 'Marion', 'type' => 'va_facility'],
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
