<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class IowaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'iowa'],
            ['name' => 'Iowa', 'code' => 'IA']
        );

        $universities = [
            ['name' => 'Allen College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Briar Cliff University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Buena Vista University', 'psych' => ['BA', 'BA (OL)', 'MSE'], 'neuro' => [], 'ol' => true],
            ['name' => 'Central College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Clarke University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Coe College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Cornell College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Des Moines University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Dordt College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Drake University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Emmaus University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Faith Baptist Bible Coll & Theological Sem', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Graceland University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Grand View University', 'psych' => ['BA', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Grinnell College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Iowa State University', 'psych' => ['BS', 'BA', 'PhD'], 'neuro' => ['MS', 'PhD'], 'ol' => false],
            ['name' => 'Loras College', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Luther College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Maharishi International University', 'psych' => ['BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Mercy College of Health Sciences', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Morningside University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Mount Mercy University', 'psych' => ['BS', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northwestern College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Simpson College', 'psych' => ['BA', 'BA (OL)'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'St. Ambrose University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Dubuque', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Iowa', 'psych' => ['BS', 'BA', 'MA', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'University of Northern Iowa', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Upper Iowa University', 'psych' => ['BS', 'BS (OL)', 'MS', 'MS (OL)', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Waldorf University', 'psych' => ['BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Wartburg College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'William Penn University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'University of Iowa Hospitals and Clinics', 'loc' => 'Iowa City', 'deg' => ['MD-DO']],
            ['name' => 'MercyOne Des Moines/PHC Consortium', 'loc' => 'Des Moines', 'deg' => ['MD-DO']],
            ['name' => 'UnityPoint Broadlawns Psychiatry Education Foundation', 'loc' => 'Des Moines', 'deg' => ['MD-DO']],
            ['name' => 'University of Iowa College of Medicine', 'loc' => 'Iowa City', 'deg' => ['MD-PhD']],
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
            ['name' => 'University of Iowa Behavioral Health', 'loc' => 'Iowa City', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facility
            ['name' => 'Des Moines VA Medical Center', 'loc' => 'Des Moines', 'type' => 'va_facility'],
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
