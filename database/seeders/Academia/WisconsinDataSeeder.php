<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class WisconsinDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'wisconsin'],
            ['name' => 'Wisconsin', 'code' => 'WI']
        );

        $universities = [
            ['name' => 'Alverno College', 'psych' => ['BS', 'BS+MS', 'MS', 'EdS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bellin College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Beloit College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Carroll University', 'psych' => ['BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Carthage College', 'psych' => ['BA'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'Concordia University Wisconsin', 'psych' => ['BA', 'BA (OL)', 'MS', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Edgewood College', 'psych' => ['BS', 'MS', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Herzing University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Lakeland University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lawrence University', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Maranatha Baptist University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Marian University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Medical College of Wisconsin', 'psych' => ['MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Milwaukee Institute of Art & Design', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Marquette University', 'psych' => ['BA', 'MS', 'MS (OL)', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => true],
            ['name' => 'Milwaukee School of Engineering', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Mount Mary University', 'psych' => ['BS', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Ripon College', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'St. Norbert College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Wisconsin – Eau Claire', 'psych' => ['BS', 'BA', 'EdS'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'University of Wisconsin – Green Bay', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Wisconsin – La Crosse', 'psych' => ['BS', 'BA', 'MS', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of Wisconsin – Madison', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'University of Wisconsin – Milwaukee', 'psych' => ['BS', 'BA', 'BA (OL)', 'MS', 'EdS', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => true],
            ['name' => 'University of Wisconsin – Oshkosh', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Wisconsin – Parkside', 'psych' => ['BS', 'BS (OL)', 'MS'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'University of Wisconsin – Platteville', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Wisconsin – River Falls', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'University of Wisconsin – Stevens Point', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Wisconsin – Stout', 'psych' => ['BS', 'BS (OL)', 'MS', 'MS (OL)', 'MSEd', 'EdS'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of Wisconsin – Superior', 'psych' => ['BS', 'BA', 'MSE (OL)'], 'neuro' => ['BS', 'BA'], 'ol' => true],
            ['name' => 'University of Wisconsin – Whitewater', 'psych' => ['BS', 'BA', 'MSE', 'EdS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Viterbo University', 'psych' => ['BS', 'MS', 'EdD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Wisconsin Lutheran College', 'psych' => ['BS', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'WI School of Professional Psychology', 'psych' => ['MS', 'PsyD'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'University of Wisconsin Hospitals and Clinics Psychiatry Residency Program', 'loc' => 'Madison', 'deg' => ['MD-DO']],
            ['name' => 'Medical Coverage of Wisconsin Affiliated Hospitals (Milwaukee)', 'loc' => 'Milwaukee', 'deg' => ['MD-DO']],
            ['name' => 'Medical College of Wisconsin Affiliated Hospitals (Northeastern Wisconsin)', 'loc' => 'Green Bay', 'deg' => ['MD-DO']],
            ['name' => 'WiNC (Wisconsin Northern & Central) GME Consortium/MCW Psychiatry Residency', 'loc' => 'Wausau', 'deg' => ['MD-DO']],
            ['name' => 'Medical College of Wisconsin', 'loc' => 'Milwaukee', 'deg' => ['MD-PhD']],
            ['name' => 'University of Wisconsin Medical School', 'loc' => 'Madison', 'deg' => ['MD-PhD']],
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
            ['name' => 'Aurora Psychiatric Hospital', 'loc' => 'Wauwatosa', 'type' => 'state_institution'],
            ['name' => 'Bellin Psychiatric Center', 'loc' => 'Green Bay', 'type' => 'state_institution'],
            ['name' => 'Rogers Memorial Hospital', 'loc' => 'Milwaukee', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Clement J. Zablocki Veterans\' Administration Medical Center', 'loc' => 'Milwaukee', 'type' => 'va_facility'],
            ['name' => 'Tomah VA Medical Center', 'loc' => 'Tomah', 'type' => 'va_facility'],
            ['name' => 'William S. Middleton Memorial Veterans\' Hospital', 'loc' => 'Madison', 'type' => 'va_facility'],
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
