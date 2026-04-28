<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class VirginiaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'virginia'],
            ['name' => 'Virginia', 'code' => 'VA']
        );

        $universities = [
            ['name' => 'Appalachian College of Pharmacy', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Averett University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bluefield College', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bridgewater College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Christopher Newport University', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'College of William and Mary', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Eastern Mennonite University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Eastern Virginia Medical School', 'psych' => ['BS', 'MS', 'MSEd', 'EdS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'ECPI University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Emory & Henry University', 'psych' => ['BS', 'BA', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Ferrum College', 'psych' => ['BA', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'George Mason University', 'psych' => ['BS', 'BS (OL)', 'BA', 'BA (OL)', 'MA', 'MPS', 'PhD'], 'neuro' => ['BS', 'MA', 'PhD'], 'ol' => true],
            ['name' => 'Hampden-Sydney College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Hampton University', 'psych' => ['BA', 'MA', 'EdS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Hollins University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'James Madison University', 'psych' => ['BS', 'BA', 'MA', 'MEd', 'EdS', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Liberty University', 'psych' => ['BS', 'BS (OL)', 'MS', 'MA', 'MA (OL)', 'PsyD', 'PhD (OL)', 'EdD (OL)'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'Longwood University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Mary Baldwin University', 'psych' => ['BS', 'BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Marymount University', 'psych' => ['BA', 'BA+MBA', 'MA', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Norfolk State University', 'psych' => ['BA', 'MS', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Old Dominion University', 'psych' => ['BS', 'BS (OL)', 'MS', 'MSEd', 'EdS', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Radford University', 'psych' => ['BS', 'BA', 'MS', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Randolph College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Randolph-Macon College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Regent University', 'psych' => ['BS', 'BS (OL)', 'MS', 'MA', 'PsyD', 'EdD', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Roanoke College', 'psych' => ['BS'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Shenandoah University- Loudoun', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Shenandoah University- Winchester', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Southern Virginia University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Sweet Briar College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Lynchburg', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Mary Washington', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of the Potomac', 'psych' => [], 'neuro' => [], 'ol' => false],

            ['name' => 'University of Richmond', 'psych' => ['BS', 'BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'University of Virginia', 'psych' => ['BA', 'MEd', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'UVA College at Wise', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Virginia Commonwealth University', 'psych' => ['BS', 'MS', 'MEd', 'PhD'], 'neuro' => ['PhD', 'MD-PhD'], 'ol' => false],
            ['name' => 'Virginia Military Institute', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Virginia Tech University', 'psych' => ['BS', 'MS', 'MAEd', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'Virginia State University', 'psych' => ['BS', 'MS', 'MEd', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Virginia Union University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Virginia Wesleyan College', 'psych' => ['BS', 'BA', 'BA (OL)', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Washington and Lee University', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
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
            ['name' => 'University of Virginia Medical Center', 'loc' => 'Charlottesville', 'deg' => ['MD-DO']],
            ['name' => 'Eastern Virginia Medical School Psychiatry Residency Program', 'loc' => 'Norfolk', 'deg' => ['MD-DO']],
            ['name' => 'Virginia Commonwealth University Health System', 'loc' => 'Richmond', 'deg' => ['MD-DO']],
            ['name' => 'Carillion Clinic- Virginia Tech Carillion School of Medicine', 'loc' => 'Roanoke', 'deg' => ['MD-DO']],
            ['name' => 'Naval Medical Center', 'loc' => 'Portsmouth', 'deg' => ['MD-DO']],
            ['name' => 'HCA Healthcare LewisGale Medical Center', 'loc' => 'Salem', 'deg' => ['MD-DO']],
            ['name' => 'Inova Fairfax Medical Campus', 'loc' => 'Falls Church', 'deg' => ['MD-DO']],
            ['name' => 'Eastern Virginia Medical School', 'loc' => 'Norfolk', 'deg' => ['MD-PhD']],
            ['name' => 'Virginia Commonwealth University School of Medicine', 'loc' => 'Richmond', 'deg' => ['MD-PhD']],
            ['name' => 'University of Virginia School of Medicine', 'loc' => 'Charlottesville', 'deg' => ['MD-PhD']],
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
            ['name' => 'Cumberland Hospital for Children & Adolescents', 'loc' => 'New Kent', 'type' => 'state_institution'],
            ['name' => 'Poplar Springs Hospital', 'loc' => 'Petersburg', 'type' => 'state_institution'],
            ['name' => 'Riverside Behavioral Health Center', 'loc' => 'Hampton', 'type' => 'state_institution'],
            ['name' => 'Saint Albans Hospital', 'loc' => 'Radford', 'type' => 'state_institution'],
            ['name' => 'Tucker Pavilion', 'loc' => 'Richmond', 'type' => 'state_institution'],
            ['name' => 'Virginia Beach Psychiatric Center', 'loc' => 'Virginia Beach', 'type' => 'state_institution'],
            ['name' => 'Central State Hospital', 'loc' => 'Petersburg', 'type' => 'state_institution'],
            ['name' => 'Commonwealth Center for Children & Adolescents', 'loc' => 'Staunton', 'type' => 'state_institution'],
            ['name' => 'Northern Virginia Mental Health Institute', 'loc' => 'Falls Church', 'type' => 'state_institution'],
            ['name' => 'Piedmont Geriatric Hospital', 'loc' => 'Burkeville', 'type' => 'state_institution'],
            ['name' => 'Southwestern Virginia Mental Health Institute', 'loc' => 'Marion', 'type' => 'state_institution'],
            ['name' => 'Western State Hospital', 'loc' => 'Staunton', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Hampton VA Medical Center', 'loc' => 'Hampton', 'type' => 'va_facility'],
            ['name' => 'Richmond VA Medical Center', 'loc' => 'Richmond', 'type' => 'va_facility'],
            ['name' => 'Salem VA Medical Center', 'loc' => 'Salem', 'type' => 'va_facility'],
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
