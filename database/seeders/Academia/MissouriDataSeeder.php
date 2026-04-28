<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class MissouriDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'missouri'],
            ['name' => 'Missouri', 'code' => 'MO']
        );

        $universities = [
            ['name' => 'A.T. Still University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Avila University', 'psych' => ['BA', 'BA (OL)', 'BA+MS', 'MS', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Calvary University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Central Christian College of the Bible', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Central Methodist University', 'psych' => ['BS', 'BS (OL)', 'BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'College of the Ozarks', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Columbia College', 'psych' => ['BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Cottey College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Cox College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Culver-Stockton College', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Drury University', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Evangel University', 'psych' => ['BS', 'BA', 'BA (OL)', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Goldfarb School of Nursing at Barnes', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Hannibal-LaGrange University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Harris-Stowe State University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Kansas City Art Institute', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Kansas City University', 'psych' => ['PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lincoln University', 'psych' => ['BS', 'BA', 'MEd', 'EdS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lindenwood University', 'psych' => ['BS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Logan University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Maryville University', 'psych' => ['BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Mission University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Missouri Baptist University', 'psych' => ['BS', 'BA', 'BA (OL)', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Missouri Southern State University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Missouri State University', 'psych' => ['BS', 'BA', 'MS', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Missouri Univ of Science and Technology', 'psych' => ['BS', 'BA', 'MS', 'MS (OL)', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Missouri Valley College', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Missouri Western State University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northwest Missouri State University', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Ozark Christian College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Park University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Research College of Nursing', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Rockhurst University', 'psych' => ['BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Saint Louis University', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => ['BS'], 'ol' => false],

            ['name' => 'Southeast Missouri State University', 'psych' => ['BS', 'BS (OL)', 'MA', 'MA (OL)', 'EdS'], 'neuro' => [], 'ol' => true],
            ['name' => 'Southwest Baptist University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Stephens College', 'psych' => ['BA', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Truman State University', 'psych' => ['BS', 'BA', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of Central Missouri', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Univ of Health Sci & Pharmacy in St. Louis', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'University of Missouri- Columbia', 'psych' => ['BS', 'BA', 'MA', 'MEd', 'EdS', 'PhD'], 'neuro' => ['MS', 'PhD'], 'ol' => false],
            ['name' => 'University of Missouri-Kansas City', 'psych' => ['BA', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Missouri-St. Louis', 'psych' => ['BA (OL)', 'MEd', 'EdS', 'PhD'], 'neuro' => ['MA'], 'ol' => true],
            ['name' => 'Washington University in St. Louis', 'psych' => ['BS', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'Webster University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Westminster College', 'psych' => ['BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'William Jewell College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'William Woods University', 'psych' => ['BA', 'BA (OL)', 'MA (OL)'], 'neuro' => [], 'ol' => true],
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
            ['name' => 'Washington University/B-JH/SLCH Consortium', 'loc' => 'St. Louis', 'deg' => ['MD-DO']],
            ['name' => 'University of Missouri-Columbia', 'loc' => 'Columbia', 'deg' => ['MD-DO']],
            ['name' => 'University of Missouri-Kansas City School of Medicine Psychiatry Residency', 'loc' => 'Kansas City', 'deg' => ['MD-DO']],
            ['name' => 'SSM Health/Saint Louis University School of Medicine', 'loc' => 'St. Louis', 'deg' => ['MD-DO']],
            ['name' => 'Kansas City University GME Consortium (Ozark Center)', 'loc' => 'Joplin', 'deg' => ['MD-DO']],
            ['name' => 'Saint Louis University School of Medicine', 'loc' => 'St. Louis', 'deg' => ['MD-PhD']],
            ['name' => 'University of Missouri - Columbia School of Medicine', 'loc' => 'Columbia', 'deg' => ['MD-PhD']],
            ['name' => 'University of Missouri - Kansas City School of Medicine', 'loc' => 'Kansas City', 'deg' => ['MD-PhD']],
            ['name' => 'Washington University School of Medicine', 'loc' => 'St. Louis', 'deg' => ['MD-PhD']],
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
            ['name' => 'CenterPointe Hospital', 'loc' => 'Saint Charles', 'type' => 'state_institution'],
            ['name' => 'Crittenton Children\'s Center', 'loc' => 'Kansas City', 'type' => 'state_institution'],
            ['name' => 'Freeman Ozark Center', 'loc' => 'Joplin', 'type' => 'state_institution'],
            ['name' => 'Lakeland Behavioral Health System', 'loc' => 'Springfield', 'type' => 'state_institution'],
            ['name' => 'Northwest Missouri Psychiatric Rehabilitation Center', 'loc' => 'Saint Joseph', 'type' => 'state_institution'],
            ['name' => 'Research Psychiatric Center', 'loc' => 'Kansas City', 'type' => 'state_institution'],
            ['name' => 'Royal Oaks Hospital', 'loc' => 'Windsor', 'type' => 'state_institution'],
            ['name' => 'Saint Louis Behavioral Medicine Institute', 'loc' => 'Saint Louis', 'type' => 'state_institution'],
            ['name' => 'Southeast Missouri Community Treatment Center', 'loc' => 'Farmington', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Harry S. Truman Memorial Veterans\' Hospital', 'loc' => 'Columbia', 'type' => 'va_facility'],
            ['name' => 'Kansas City VA Medical Center', 'loc' => 'Kansas City', 'type' => 'va_facility'],
            ['name' => 'St. Louis VA Medical Center', 'loc' => 'St. Louis', 'type' => 'va_facility'],
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
