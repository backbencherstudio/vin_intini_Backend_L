<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class NewJerseyDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'new-jersey'],
            ['name' => 'New Jersey', 'code' => 'NJ']
        );

        $universities = [
            ['name' => 'Bloomfield College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Caldwell University', 'psych' => ['BS', 'BS (OL)', 'BA+MA', 'MA'], 'neuro' => [], 'ol' => true],
            ['name' => 'Centenary University', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'College of New Jersey', 'psych' => ['BA', 'BA+MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Drew University', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Fairleigh Dickinson University', 'psych' => ['BA', 'BA+MA', 'MS', 'MA', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Felician University', 'psych' => ['BS', 'BA', 'BA+MA', 'MA', 'MA (OL)', 'PsyD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Georgian Court University', 'psych' => ['BS', 'BA', 'BA+MA', 'MA', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Kean University', 'psych' => ['BS', 'BA', 'BA (OL)', 'MS', 'MA', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Monmouth University', 'psych' => ['BA', 'MS', 'MSEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Montclair State University', 'psych' => ['BA', 'BA (OL)', 'BA+MA', 'MA', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'New Jersey City University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'New Jersey Institute of Technology', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Pillar College', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Princeton University', 'psych' => ['A.B.', 'PhD', 'MD-PhD'], 'neuro' => ['A.B.', 'PhD', 'MD-PhD'], 'ol' => false],
            ['name' => 'Ramapo College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Rider University', 'psych' => ['BA', 'BA (OL)', 'MA', 'EdS'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'Rowan University', 'psych' => ['BS', 'BA', 'MA', 'EdS', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD', 'DO-PhD'], 'ol' => false],
            ['name' => 'Rutgers University - Camden', 'psych' => ['BA', 'BA+MA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Rutgers University - Newark', 'psych' => ['BA', 'MA', 'PhD'], 'neuro' => ['BA', 'PhD'], 'ol' => false],
            ['name' => 'Rutgers University - New Brunswick', 'psych' => ['BA', 'MS', 'PhD'], 'neuro' => ['PhD'], 'ol' => false],
            ['name' => 'Saint Elizabeth University', 'psych' => ['BA', 'BA+MA', 'MA', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Seton Hall University', 'psych' => ['BS', 'BA', 'MA', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'St. Peter\'s University', 'psych' => ['BS', 'MS (OL)', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Stevens Institute of Technology', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Stockton University', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Thomas Edison State University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'William Paterson University', 'psych' => ['BA', 'BA (OL)', 'MA', 'MEd', 'PsyD'], 'neuro' => [], 'ol' => true],
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
            ['name' => 'Cooper Med School of Rowan Univ/Cooper Univ Hospital Psychiatry Residency', 'loc' => 'Camden', 'deg' => ['MD-DO']],
            ['name' => 'New Bridge Medical Center', 'loc' => 'Paramus', 'deg' => ['MD-DO']],
            ['name' => 'Rutgers Health/New Jersey Medical School Psychiatry Residency Program', 'loc' => 'Newark', 'deg' => ['MD-DO']],
            ['name' => 'Rutgers Health/Robert Wood Johnson School', 'loc' => 'Piscataway', 'deg' => ['MD-DO']],
            ['name' => 'Rutgers Health/Trinitas Regional Medical Center Psychiatry Residency Program', 'loc' => 'Elizabeth', 'deg' => ['MD-DO']],
            ['name' => 'Virtua Psychiatry Residency Program', 'loc' => 'Mount Laurel', 'deg' => ['MD-DO']],
            ['name' => 'Ocean University Medical Center', 'loc' => 'Brick', 'deg' => ['MD-DO']],
            ['name' => 'Jersey Shore University Medical Center', 'loc' => 'Neptune', 'deg' => ['MD-DO']],
            ['name' => 'AtlantiCare Regional Medical Center', 'loc' => 'Atlantic City', 'deg' => ['MD-DO']],
            ['name' => 'New York Medical College/St. Mary and St. Clare', 'loc' => 'Denville', 'deg' => ['MD-DO']],
            ['name' => 'Inspira Health Network/Inspira Medical Center Vineland', 'loc' => 'Vineland', 'deg' => ['MD-DO']],
            ['name' => 'Hackensack University Medical Center', 'loc' => 'Hackensack', 'deg' => ['MD-DO']],
            ['name' => 'Capital Health Regional Medical Center', 'loc' => 'Trenton', 'deg' => ['MD-DO']],
            ['name' => 'Rutgers University New Jersey Medical School', 'loc' => 'Newark', 'deg' => ['MD-PhD']],
            ['name' => 'Rutgers University Robert Wood Johnson Medical School', 'loc' => 'Piscataway', 'deg' => ['MD-PhD']],
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
            ['name' => 'Ancora Psychiatric Hospital', 'loc' => 'Hammonton', 'type' => 'state_institution'],
            ['name' => 'Ann Klein Forensic Psychiatric Hospital', 'loc' => 'West Trenton', 'type' => 'state_institution'],
            ['name' => 'Arthur Brisbane Child Treatment Center', 'loc' => 'Farmingdale', 'type' => 'state_institution'],
            ['name' => 'Carrier Clinic', 'loc' => 'Belle Mead', 'type' => 'state_institution'],
            ['name' => 'Greystone Park Psychiatric Hospital', 'loc' => 'Greystone Park', 'type' => 'state_institution'],
            ['name' => 'Hampton Behavioral Health Center', 'loc' => 'Westampton', 'type' => 'state_institution'],
            ['name' => 'Senator Garrett W. Hagedorn Psychiatric Hospital', 'loc' => 'Glen Gardner', 'type' => 'state_institution'],
            ['name' => 'Trenton Psychiatric Hospital', 'loc' => 'West Trenton', 'type' => 'state_institution'],
            ['name' => 'Princeton House Behavioral Health', 'loc' => 'Princeton', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Lyons VA Medical Center', 'loc' => 'Lyons', 'type' => 'va_facility'],
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
