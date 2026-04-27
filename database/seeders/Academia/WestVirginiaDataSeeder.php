<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class WestVirginiaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'west-virginia'],
            ['name' => 'West Virginia', 'code' => 'WV']
        );

        $universities = [
            ['name' => 'Appalachian Bible College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Bethany College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bluefield State University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Concord University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Davis & Elkins College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Fairmont State University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Glenville State University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Marshall University', 'psych' => ['BS', 'BA', 'MA', 'MA (OL)', 'PsyD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Salem University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Shepherd University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Charleston', 'psych' => ['BS', 'BA', 'BA (OL)', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'West Liberty University', 'psych' => ['BS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'West Virginia School of Osteopathic Med', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'West Virginia State University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'West Virginia University- Beckley', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'West Virginia University- Morgantown', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD', 'MD-PhD'], 'ol' => false],
            ['name' => 'West Virginia University- Parkersburg', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'West Virginia Institute of Technology', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'West Virginia Wesleyan College', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Wheeling University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'Charleston Area Medical Center/CAMC Institute for Academic Medicine', 'loc' => 'Charleston', 'deg' => ['MD-DO']],
            ['name' => 'West Virginia University', 'loc' => 'Morgantown', 'deg' => ['MD-DO']],
            ['name' => 'Marshall University School of Medicine', 'loc' => 'Huntington', 'deg' => ['MD-DO']],
            ['name' => 'Marshall Community Health Consortium', 'loc' => 'Pt. Pleasant', 'deg' => ['MD-DO']],
            ['name' => 'Marshall University School of Medicine', 'loc' => 'Huntington', 'deg' => ['MD-PhD']],
            ['name' => 'West Virginia University School of Medicine', 'loc' => 'Morgantown', 'deg' => ['MD-PhD']],
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
            // State Institution
            ['name' => 'Chestnut Ridge Hospital', 'loc' => 'Morgantown', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Hershel "Woody" Williams VA Medical Center', 'loc' => 'Huntington', 'type' => 'va_facility'],
            ['name' => 'Louis A. Johnson Veterans\' Administration Medical Center', 'loc' => 'Clarksburg', 'type' => 'va_facility'],
            ['name' => 'Martinsburg VA Medical Center', 'loc' => 'Martinsburg', 'type' => 'va_facility'],
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
