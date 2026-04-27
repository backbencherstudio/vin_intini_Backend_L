<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class OklahomaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'oklahoma'],
            ['name' => 'Oklahoma', 'code' => 'OK']
        );

        $universities = [
            ['name' => 'Cameron University', 'psych' => ['BS', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'East Central University', 'psych' => ['BS', 'MA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Langston University- Langston', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Langston University- Oklahoma City', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Langston University- Tulsa', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Mid-America Christian University', 'psych' => ['BS', 'MS', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Northeastern State University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northwestern Oklahoma State University', 'psych' => ['BA', 'MS', 'MA', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Oklahoma Baptist University', 'psych' => ['BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Oklahoma Christian University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Oklahoma City University', 'psych' => ['BS', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Oklahoma Panhandle State University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Oklahoma State University', 'psych' => ['BS', 'BA', 'MS', 'EdS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Oklahoma Wesleyan University', 'psych' => ['BS', 'BS (OL)', 'MA'], 'neuro' => [], 'ol' => true],
            ['name' => 'Oral Roberts University', 'psych' => ['BS', 'BS (OL)', 'BA', 'BA (OL)', 'MA', 'MA (OL)', 'MEd'], 'neuro' => [], 'ol' => true],
            ['name' => 'Rogers State University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Southeastern Oklahoma State University', 'psych' => ['BA', 'MA', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Southern Nazarene University', 'psych' => ['BS', 'BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Southwestern Christian University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Southwestern Oklahoma State University', 'psych' => ['BS', 'MS', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Central Oklahoma', 'psych' => ['BS', 'MS', 'MA', 'EdS', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Oklahoma', 'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'], 'neuro' => ['MS', 'PhD'], 'ol' => false],
            ['name' => 'Univ of Oklahoma Health Sciences Center', 'psych' => [], 'neuro' => ['MS', 'PhD'], 'ol' => false],
            ['name' => 'University of Science & Arts of Oklahoma', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Tulsa', 'psych' => ['BS', 'BA', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'Univ of Oklahoma Health Sciences Center Psychiatry Residency Program', 'loc' => 'Oklahoma City', 'deg' => ['MD-DO']],
            ['name' => 'University of Oklahoma School of Community Medicine (Tulsa)', 'loc' => 'Tulsa', 'deg' => ['MD-DO']],
            ['name' => 'Osteopathic Med Education Consortium of OK, Inc (OMECO) Griffin Mem Hospital', 'loc' => 'Norman', 'deg' => ['MD-DO']],
            ['name' => 'Oklahoma State Univ Center for Health Sciences Psychiatry Residency Program', 'loc' => 'Tulsa', 'deg' => ['MD-DO']],
            ['name' => 'University of Oklahoma Health Sciences Center', 'loc' => 'Oklahoma City', 'deg' => ['MD-PhD']],
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
            ['name' => 'Brookhaven Hospital', 'loc' => 'Tulsa', 'type' => 'state_institution'],
            ['name' => 'Cedar Ridge', 'loc' => 'Oklahoma City', 'type' => 'state_institution'],
            ['name' => 'Grand Lake Mental Health Center', 'loc' => 'Nowata', 'type' => 'state_institution'],
            ['name' => 'Griffin Memorial Hospital', 'loc' => 'Norman', 'type' => 'state_institution'],
            ['name' => 'Integris Mental Health Center - Spencer', 'loc' => 'Oklahoma City', 'type' => 'state_institution'],
            ['name' => 'Laureate Psychiatric Clinic & Hospital', 'loc' => 'Tulsa', 'type' => 'state_institution'],
            ['name' => 'Parkside Psychiatric Hospital & Clinic', 'loc' => 'Tulsa', 'type' => 'state_institution'],
            ['name' => 'Rolling Hills Hospital', 'loc' => 'Ada', 'type' => 'state_institution'],
            ['name' => 'Shadow Mountain Behavioral Health System', 'loc' => 'Tulsa', 'type' => 'state_institution'],
            ['name' => 'Willow Crest Hospital & Moccasin Bend Ranch', 'loc' => 'Miami', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facility
            ['name' => 'Oklahoma City VA Medical Center', 'loc' => 'Oklahoma City', 'type' => 'va_facility'],
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
