<?php

namespace Database\Seeders\Academia;

use App\Models\AcademiaFacility;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaUniversity;
use App\Models\State;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AlabamaDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $state = State::updateOrCreate(
            ['slug' => 'alabama'],
            ['name' => 'Alabama', 'code' => 'AL']
        );

        $universities = [
            ['name' => 'Alabama A&M University', 'psych' => ['BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Alabama State University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Auburn University', 'psych' => ['BA', 'MS', 'MA', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Auburn University- Montgomery', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Faulkner University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Huntingdon College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Jacksonville State University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Oakwood University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Samford University', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Spring Hill College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Stillman College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Talladega College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Troy University', 'psych' => ['BS', 'MS', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Tuskegee University', 'psych' => ['BS', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Alabama Birmingham', 'psych' => ['BS', 'MA', 'EdS (OL)', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => true],
            ['name' => 'University of Alabama Huntsville', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Alabama Tuscaloosa', 'psych' => ['BS', 'BA', 'MA', 'EdS', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'University of Mobile', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Montevallo', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of North Alabama', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of South Alabama', 'psych' => ['BA', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of West Alabama', 'psych' => ['BS (OL)', 'BA (OL)', 'MS (OL)'], 'neuro' => [], 'ol' => true],
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
            ['name' => 'University of Alabama Medical Center', 'loc' => 'Birmingham', 'deg' => ['MD-DO']],
            ['name' => 'USA Health Psychiatry Residency Program', 'loc' => 'Mobile', 'deg' => []],
            ['name' => 'North Alabama Medical Center', 'loc' => 'Muscle Shoals', 'deg' => ['MD-DO']],
            ['name' => 'East Alabama Medical Center', 'loc' => 'Opelika', 'deg' => ['MD-DO']],
            ['name' => 'University of Alabama Hospital', 'loc' => 'Huntsville', 'deg' => ['MD-DO']],
            ['name' => 'Univ of Alabama Hospital Psychiatry Residency Program', 'loc' => 'Montgomery', 'deg' => ['MD-DO']],
            ['name' => 'University of Alabama Tuscaloosa', 'loc' => 'Tuscaloosa', 'deg' => ['MD-DO']],
            ['name' => 'Cahaba Medical Care Psychiatry Residency Program', 'loc' => 'Centerville', 'deg' => ['MD-DO']],
            ['name' => 'University of Alabama School of Medicine', 'loc' => 'Birmingham', 'deg' => ['MD-PhD']],
            ['name' => 'University of South Alabama College of Medicine', 'loc' => 'Mobile', 'deg' => ['MD-PhD']],
        ];

        foreach ($residencies as $res) {
            AcademiaMedicalResidency::create([
                'state_id' => $state->id,
                'program_name' => $res['name'],
                'location' => $res['loc'],
                'degree_types' => $res['deg'],
            ]);
        }

        $stateInstitutions = [
            ['name' => 'Bryce Hospital', 'loc' => 'Tuscaloosa'],
            ['name' => 'Mary Starke Harper Geriatric Psychiatry Center', 'loc' => 'Tuscaloosa'],
            ['name' => 'Crossbridge Behavioral Health Services', 'loc' => 'Montgomery'],
            ['name' => 'Hill Crest Behavioral Health Services', 'loc' => 'Birmingham'],
            ['name' => 'Laurel Oaks Behavioral Health Center', 'loc' => 'Dothan'],
            ['name' => 'East Pointe Hospitals', 'loc' => 'Mobile'],
            ['name' => 'Mountain View Hospital', 'loc' => 'Gadsden'],
            ['name' => 'Unity Psychiatric', 'loc' => 'Huntsville'],
        ];

        foreach ($stateInstitutions as $inst) {
            AcademiaFacility::create([
                'state_id' => $state->id,
                'name' => $inst['name'],
                'location' => $inst['loc'],
                'type' => 'state_institution',
            ]);
        }

        AcademiaFacility::create([
            'state_id' => $state->id,
            'name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine',
            'location' => 'Birmingham',
            'type' => 'university_hospital',
        ]);

        $vaFacilities = [
            ['name' => 'Central Alabama VA Medical Center', 'loc' => 'Tuskegee'],
            ['name' => 'Tuscaloosa VA Medical Center', 'loc' => 'Tuscaloosa'],
        ];

        foreach ($vaFacilities as $va) {
            AcademiaFacility::create([
                'state_id' => $state->id,
                'name' => $va['name'],
                'location' => $va['loc'],
                'type' => 'va_facility',
            ]);
        }
    }
}
