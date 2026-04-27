<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class MarylandDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'maryland'],
            ['name' => 'Maryland', 'code' => 'MD']
        );

        $universities = [
            ['name' => 'Bowie State University', 'psych' => ['BS', 'BA', 'MA', 'MEd', 'PhD'], 'neuro' => []],
            ['name' => 'Capitol Technology University', 'psych' => ['Mres', 'PhD'], 'neuro' => []],
            ['name' => 'Coppin State University', 'psych' => ['BS', 'MS'], 'neuro' => []],
            ['name' => 'Frostburg State University', 'psych' => ['BS', 'MS', 'MEd'], 'neuro' => []],
            ['name' => 'Goucher College', 'psych' => ['BA'], 'neuro' => ['BS']],
            ['name' => 'Hood College', 'psych' => ['BA', 'BA+MS', 'MS', 'MA', 'MEd', 'PsyD'], 'neuro' => []],
            ['name' => 'Johns Hopkins University', 'psych' => ['BA', 'MS', 'MA', 'MHS', 'PhD'], 'neuro' => ['BS', 'BS+MS', 'PhD']],
            ['name' => 'Loyola University Maryland', 'psych' => ['BA', 'BA+MS', 'MS', 'MA', 'MEd', 'PsyD'], 'neuro' => []],
            ['name' => 'Maryland Institute College of Art', 'psych' => [], 'neuro' => []],
            ['name' => 'McDaniel College', 'psych' => ['BA', 'BA+MS', 'MS'], 'neuro' => []],
            ['name' => 'Morgan State University', 'psych' => ['BS', 'MS', 'PhD'], 'neuro' => ['MS']],
            ['name' => 'Mount St. Mary\'s University', 'psych' => ['BS', 'BS+MS', 'MS'], 'neuro' => ['BS']],
            ['name' => 'Notre Dame of Maryland University', 'psych' => ['BA'], 'neuro' => ['BS']],
            ['name' => 'Salisbury University', 'psych' => ['BA'], 'neuro' => []],
            ['name' => 'St. John\'s College', 'psych' => [], 'neuro' => []],
            ['name' => 'St. Mary\'s College of Maryland', 'psych' => ['BA'], 'neuro' => ['BS']],
            ['name' => 'Stevenson University', 'psych' => ['BS', 'BA', 'BS+MS', 'PsyD'], 'neuro' => []],
            ['name' => 'Towson University', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => []],
            ['name' => 'Uniformed Services Univ of Health Sci', 'psych' => [], 'neuro' => []],
            ['name' => 'United States Naval Academy', 'psych' => [], 'neuro' => []],
            ['name' => 'University of Baltimore', 'psych' => ['BA', 'MS'], 'neuro' => []],
            ['name' => 'Univ of Maryland – Baltimore County', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => ['PhD']],
            ['name' => 'Univ of Maryland – College Park', 'psych' => ['BS', 'BA', 'MA', 'MPPS', 'MPIO', 'PhD'], 'neuro' => ['BS', 'PhD']],
            ['name' => 'Univ of Maryland – Eastern Shore', 'psych' => ['BS', 'MS', 'MEd'], 'neuro' => []],
            ['name' => 'Washington Adventist University', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => []],
            ['name' => 'Washington College', 'psych' => ['BS', 'BA'], 'neuro' => ['BS']],
        ];

        foreach ($universities as $uni) {
            AcademiaUniversity::create([
                'state_id' => $state->id,
                'name' => $uni['name'],
                'psychology_degrees' => $uni['psych'],
                'neuroscience_degrees' => $uni['neuro'],
                'has_online_options' => false,
            ]);
        }

        $residencies = [
            ['name' => 'Johns Hopkins University', 'loc' => 'Baltimore', 'deg' => ['MD-DO']],
            ['name' => 'University of Maryland/Sheppard Pratt', 'loc' => 'Baltimore', 'deg' => ['MD-DO']],
            ['name' => 'National Capital Consortium Psychiatry Residency Program', 'loc' => 'Bethesda', 'deg' => ['MD-DO']],
            ['name' => 'National Institute of Health Clinical Center', 'loc' => 'Bethesda', 'deg' => ['MD-DO']],
            ['name' => 'MedStar Health', 'loc' => 'Baltimore', 'deg' => ['MD-DO']],
            ['name' => 'Meritus Medical Center', 'loc' => 'Hagerstown', 'deg' => ['MD-DO']],
            ['name' => 'TidalHealth Psychiatry Residency Program', 'loc' => 'Salisbury', 'deg' => ['MD-DO']],
            ['name' => 'Johns Hopkins University School of Medicine', 'loc' => 'Baltimore', 'deg' => ['MD-PhD']],
            ['name' => 'National Institutes of Health Intramural MD-PhD Partnership', 'loc' => 'Bethesda', 'deg' => ['MD-PhD']],
            ['name' => 'Uniformed Services University of the Health Sciences', 'loc' => 'Bethesda', 'deg' => ['MD-PhD']],
            ['name' => 'University of Maryland at Baltimore School of Medicine', 'loc' => 'Baltimore', 'deg' => ['MD-PhD']],
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
            ['name' => 'Brook Lane Health Services', 'loc' => 'Hagerstown', 'type' => 'state_institution'],
            ['name' => 'Perry Point VA Medical Center', 'loc' => 'Perry Point', 'type' => 'state_institution'],
            ['name' => 'Potomac Ridge Behavioral Health', 'loc' => 'Rockville', 'type' => 'state_institution'],
            ['name' => 'Sheppard and Enoch Pratt Hospital', 'loc' => 'Baltimore', 'type' => 'state_institution'],
            ['name' => 'Sheppard Pratt Health System', 'loc' => 'Baltimore', 'type' => 'state_institution'],

            // University Hospitals (স্লাইডের তথ্য অনুযায়ী)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // Veterans Affairs (VA) Residential Treatment Facilities
            ['name' => 'Perry Point VA Medical Center', 'loc' => 'Perry Point', 'type' => 'va_facility'],
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
