<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class LouisianaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'louisiana'],
            ['name' => 'Louisiana', 'code' => 'LA']
        );

        $universities = [
            ['name' => 'Centenary College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Dillard University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Franciscan Missionaries of Our Lady Univ', 'psych' => ['BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Grambling State University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Louisiana Christian University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Louisiana State University - Alexandria', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Louisiana State University - Baton Rouge', 'psych' => ['BS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Louisiana State University - Shreveport', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'LSU Health Sciences Center - New Orleans', 'psych' => ['MHS'], 'neuro' => ['PhD'], 'ol' => false],
            ['name' => 'LSU Health Sciences Center-Shreveport', 'psych' => [], 'neuro' => ['PhD'], 'ol' => false],
            ['name' => 'Louisiana Tech University', 'psych' => ['BA', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Loyola University New Orleans', 'psych' => ['BS', 'BA (OL)', 'MS'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'McNeese State University', 'psych' => ['BS', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Nicholls State University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northwestern State University', 'psych' => ['BS', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Southeastern Louisiana University', 'psych' => ['BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Southern University and A&M College', 'psych' => ['BS', 'BS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Southern University Law Center', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Southern University at New Orleans', 'psych' => ['BS', 'BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Tulane University', 'psych' => ['BS', 'MS', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'University of Holy Cross', 'psych' => ['BS', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Louisiana - Lafayette', 'psych' => ['BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of New Orleans', 'psych' => ['BS', 'MS', 'MEd', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Xavier University of Louisiana', 'psych' => ['BS', 'MS', 'MA'], 'neuro' => ['BS'], 'ol' => false],
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
            ['name' => 'LA State Univ School of Med/Ochsner Clinic Foundation Psychiatry Residency', 'loc' => 'New Orleans', 'deg' => ['MD-DO']],
            ['name' => 'Louisiana State University', 'loc' => 'Shreveport', 'deg' => ['MD-DO']],
            ['name' => 'Tulane University', 'loc' => 'New Orleans', 'deg' => ['MD-DO']],
            ['name' => 'Louisiana State University School of Medicine', 'loc' => 'Baton Rouge', 'deg' => ['MD-DO']],
            ['name' => 'Willis-Knighton Health System', 'loc' => 'Shreveport', 'deg' => ['MD-DO']],
            ['name' => 'Louisiana State University, New Orleans School of Medicine', 'loc' => 'New Orleans', 'deg' => ['MD-PhD']],
            ['name' => 'Louisiana State University, Shreveport School of Medicine', 'loc' => 'Shreveport', 'deg' => ['MD-PhD']],
            ['name' => 'Tulane University School of Medicine', 'loc' => 'New Orleans', 'deg' => ['MD-PhD']],
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
            ['name' => 'Acadia Vermilion Hospital', 'loc' => 'Lafayette', 'type' => 'state_institution'],
            ['name' => 'Acadiana Addiction Center', 'loc' => 'Lafayette', 'type' => 'state_institution'],
            ['name' => 'Allegiance Behavioral Health Center of Ruston', 'loc' => 'Ruston', 'type' => 'state_institution'],
            ['name' => 'Brentwood Hospital', 'loc' => 'Shreveport', 'type' => 'state_institution'],
            ['name' => 'Central Louisiana State Hospital', 'loc' => 'Pineville', 'type' => 'state_institution'],
            ['name' => 'Community Care Hospital', 'loc' => 'New Orleans', 'type' => 'state_institution'],
            ['name' => 'Behavioral Medicine Unit of Acadia Saint Landry Hospital', 'loc' => 'Church Point', 'type' => 'state_institution'],
            ['name' => 'Compass Behavioral Center of Crowley', 'loc' => 'Crowley', 'type' => 'state_institution'],
            ['name' => 'Compass Behavioral Center of Kaplan', 'loc' => 'Kaplan', 'type' => 'state_institution'],
            ['name' => 'Compass Health Senior Care Center', 'loc' => 'Natchitoches', 'type' => 'state_institution'],
            ['name' => 'Crossroads Regional Hospital', 'loc' => 'Alexandria', 'type' => 'state_institution'],
            ['name' => 'East Louisiana State Hospital', 'loc' => 'Jackson', 'type' => 'state_institution'],
            ['name' => 'Greenwell Springs Hospital', 'loc' => 'Greenwell Springs', 'type' => 'state_institution'],
            ['name' => 'Jennings Senior Care Hospital', 'loc' => 'Jennings', 'type' => 'state_institution'],
            ['name' => 'Liberty Healthcare Systems Adult Behavioral Hospital', 'loc' => 'Farmerville', 'type' => 'state_institution'],
            ['name' => 'Liberty Healthcare Child & Adolescent Behavioral Hospital', 'loc' => 'Bastrop', 'type' => 'state_institution'],
            ['name' => 'Longleaf Hospital', 'loc' => 'Alexandria', 'type' => 'state_institution'],
            ['name' => 'Magnolia Behavioral Healthcare', 'loc' => 'Bogalusa', 'type' => 'state_institution'],
            ['name' => 'New Orleans Adolescent Hospital', 'loc' => 'New Orleans', 'type' => 'state_institution'],
            ['name' => 'NorthShore Psychiatric Hospital', 'loc' => 'Slidell', 'type' => 'state_institution'],
            ['name' => 'Oceans Behavioral Hospital', 'loc' => 'Alexandria', 'type' => 'state_institution'],
            ['name' => 'Oceans Behavioral Hospital', 'loc' => 'Baton Rouge', 'type' => 'state_institution'],
            ['name' => 'Oceans Behavioral Hospital', 'loc' => 'DeRidder', 'type' => 'state_institution'],
            ['name' => 'Oceans Behavioral Hospital', 'loc' => 'Kenner', 'type' => 'state_institution'],
            ['name' => 'Oceans Behavioral Hospital', 'loc' => 'Kentwood', 'type' => 'state_institution'],
            ['name' => 'Oceans Behavioral Hospital', 'loc' => 'Lake Charles', 'type' => 'state_institution'],
            ['name' => 'Oceans Behavioral Hospital', 'loc' => 'Opelousas', 'type' => 'state_institution'],
            ['name' => 'Optima Specialty Hospital', 'loc' => 'Lafayette', 'type' => 'state_institution'],
            ['name' => 'Phoenix Behavioral Hospital', 'loc' => 'Eunice', 'type' => 'state_institution'],
            ['name' => 'River Oaks Hospital', 'loc' => 'New Orleans', 'type' => 'state_institution'],
            ['name' => 'Saint Patrick\'s Psychiatric Hospital', 'loc' => 'Monroe', 'type' => 'state_institution'],
            ['name' => 'Southeast Louisiana State Hospital', 'loc' => 'Mandeville', 'type' => 'state_institution'],
            ['name' => 'Stonewall Hospital for Behavioral Health', 'loc' => 'Stonewall', 'type' => 'state_institution'],
            ['name' => 'Vermilion Behavioral Health Systems', 'loc' => 'Lafayette', 'type' => 'state_institution'],

            // University Hospital (Placeholder from slide template)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Alexandria VA Medical Center', 'loc' => 'Pineville', 'type' => 'va_facility'],
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
