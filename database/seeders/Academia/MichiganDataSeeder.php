<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class MichiganDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'michigan'],
            ['name' => 'Michigan', 'code' => 'MI']
        );

        $universities = [
            ['name' => 'Adrian College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Albion College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Alma College', 'psych' => ['BS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Andrews University', 'psych' => ['BS', 'BA', 'MA', 'EdS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Aquinas College', 'psych' => ['BS', 'BS+MA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Baker College', 'psych' => ['BS', 'BS+MS', 'MS', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Calvin University', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Central Michigan University', 'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'Cleary University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'College for Creative Studies', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Concordia University – Ann Arbor', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Cornerstone University', 'psych' => ['BS', 'BA (OL)', 'MS (OL)', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Cranbrook Academy of Art', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Davenport University', 'psych' => ['MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Eastern Michigan University', 'psych' => ['BS', 'MS', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Ferris State University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Grace Christian University', 'psych' => ['BS', 'BS (OL)'], 'neuro' => ['BS', 'BA'], 'ol' => true],
            ['name' => 'Grand Valley State University', 'psych' => ['BS', 'BA', 'MS', 'PsyS', 'MEd'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'Great Lakes Christian College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Hillsdale College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Hope College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Kalamazoo College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Kettering University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Kuyper College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Lake Superior State University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lawrence Technological University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Madonna University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Michigan School of Psychology', 'psych' => ['MA', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Michigan State University', 'psych' => ['BS', 'BA', 'MS (OL)', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => true],
            ['name' => 'Michigan Technological University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northern Michigan University', 'psych' => ['BS', 'BS+MS', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Northwood University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Oakland University', 'psych' => ['BA', 'BA+MS', 'MS', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Olivet College', 'psych' => ['BA', 'BA (OL)', 'MA (OL)'], 'neuro' => [], 'ol' => true],

            ['name' => 'Rochester University', 'psych' => ['BS', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Saginaw Valley State University', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Spring Arbor University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Detroit Mercy', 'psych' => ['BS', 'BA', 'BA+MA', 'MA', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'University of Michigan – Ann Arbor', 'psych' => ['BS', 'A.B.', 'MS', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'University of Michigan – Dearborn', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Michigan – Flint', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Walsh College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Wayne State University', 'psych' => ['BS', 'BA', 'MA', 'MA (OL)', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => true],
            ['name' => 'Western Michigan University', 'psych' => ['BS', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'University of Michigan', 'loc' => 'Ann Arbor', 'deg' => ['MD-DO']],
            ['name' => 'Western Michigan Univ Homer Stryker MD School of Med Psychiatry Residency', 'loc' => 'Kalamazoo', 'deg' => ['MD-DO']],
            ['name' => 'Michigan State University', 'loc' => 'East Lansing', 'deg' => ['MD-DO']],
            ['name' => 'Henry Ford Health/Henry Ford Hospital', 'loc' => 'Detroit', 'deg' => ['MD-DO']],
            ['name' => 'Detroit Medical Center/Wayne State University Psychiatry Residency Program', 'loc' => 'Detroit', 'deg' => ['MD-DO']],
            ['name' => 'Pine Rest Christian Mental Health Services', 'loc' => 'Grand Rapids', 'deg' => ['MD-DO']],
            ['name' => 'Central Michigan Univ College of Medicine/CMU Medical Education Partners', 'loc' => 'Saginaw', 'deg' => ['MD-DO']],
            ['name' => 'Trinity Health Livonia Hospital/Wayne State Univ Psychiatry Residency Program', 'loc' => 'Livonia', 'deg' => ['MD-DO']],
            ['name' => 'Detroit Wayne County Health Authority (Authority Health) GME Consortium', 'loc' => 'Detroit', 'deg' => ['MD-DO']],
            ['name' => 'Henry Ford Health/Henry Ford Jackson Hospital', 'loc' => 'Jackson', 'deg' => ['MD-DO']],
            ['name' => 'Oakland Physicians Medical Center', 'loc' => 'Pontiac', 'deg' => ['MD-DO']],
            ['name' => 'Corewell Health (Dearborn)', 'loc' => 'Dearborn', 'deg' => ['MD-DO']],
            ['name' => 'Michigan State University College of Human Medicine', 'loc' => 'East Lansing', 'deg' => ['MD-PhD']],
            ['name' => 'University of Michigan Medical School', 'loc' => 'Ann Arbor', 'deg' => ['MD-PhD']],
            ['name' => 'Wayne State University School of Medicine', 'loc' => 'Detroit', 'deg' => ['MD-PhD']],
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
            ['name' => 'Detroit Behavioral Institute', 'loc' => 'Detroit', 'type' => 'state_institution'],
            ['name' => 'Brighton Hospital', 'loc' => 'Brighton', 'type' => 'state_institution'],
            ['name' => 'Forest View Hospital', 'loc' => 'Grand Rapids', 'type' => 'state_institution'],
            ['name' => 'Harbor Oaks Hospital', 'loc' => 'New Baltimore', 'type' => 'state_institution'],
            ['name' => 'Havenwyck Hospital', 'loc' => 'Auburn Hills', 'type' => 'state_institution'],
            ['name' => 'Kingswood Hospital', 'loc' => 'Ferndale', 'type' => 'state_institution'],
            ['name' => 'Pine Rest Christian Mental Health Services', 'loc' => 'Grand Rapids', 'type' => 'state_institution'],
            ['name' => 'Stonecrest Behavioral Health Hospital', 'loc' => 'Detroit', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Battle Creek VA Medical Center', 'loc' => 'Battle Creek', 'type' => 'va_facility'],
            ['name' => 'John D. Dingell Department of Veterans Affairs Medical Center', 'loc' => 'Detroit', 'type' => 'va_facility'],
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
