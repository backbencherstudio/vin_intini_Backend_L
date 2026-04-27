<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class NorthCarolinaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'north-carolina'],
            ['name' => 'North Carolina', 'code' => 'NC']
        );

        $universities = [
            ['name' => 'Appalachian State University', 'psych' => ['BS', 'BA', 'MA', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Barton College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Belmont Abbey College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bennett College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Brevard College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Campbell University', 'psych' => ['BS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Cabarrus College of Health Sciences', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Carolina University', 'psych' => ['BS', 'BA'], 'neuro' => ['MA'], 'ol' => false],
            ['name' => 'Catawba College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Chowan University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Davidson College', 'psych' => ['BS'], 'neuro' => ['BS', 'A.B.', 'PhD'], 'ol' => false],
            ['name' => 'Duke University', 'psych' => ['BS', 'A.B.', 'PhD'], 'neuro' => ['BS', 'A.B.', 'PhD'], 'ol' => false],
            ['name' => 'East Carolina University', 'psych' => ['BA', 'MS', 'MA', 'PhD'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'Elizabeth City State University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Elon University', 'psych' => ['BS', 'A.B.'], 'neuro' => [], 'ol' => false],
            ['name' => 'Fayetteville State University', 'psych' => ['BS', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Gardner-Webb University', 'psych' => ['BS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Greensboro College', 'psych' => ['BS', 'BS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Guilford College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'High Point University', 'psych' => ['BS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Johnson C. Smith University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lees-McRae College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lenoir-Rhyne University', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Livingstone College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Mars Hill University', 'psych' => ['BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Meredith College', 'psych' => ['BA', 'BA+MA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Methodist University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Mid-Atlantic Christian University', 'psych' => ['BS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Montreat College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'North Carolina Central University', 'psych' => ['BS', 'BS+MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'North Carolina State University', 'psych' => ['BA', 'MS', 'MED', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'North Carolina A&T State University', 'psych' => ['BA', 'MS', 'PhD'], 'neuro' => ['PhD'], 'ol' => false],
            ['name' => 'North Carolina Wesleyan University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Queens University of Charlotte', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],

            ['name' => 'Salem College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Shaw University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'St. Andrew\'s University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'St. Augustine\'s University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Mount Olive', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of North Carolina – Asheville', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of North Carolina – Chapel Hill', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'University of North Carolina – Charlotte', 'psych' => ['BS', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of North Carolina – Greensboro', 'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of North Carolina – Pembroke', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of North Carolina – Wilmington', 'psych' => ['BA', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Pfeiffer University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Wake Forest University', 'psych' => ['BA', 'MS', 'MA'], 'neuro' => ['BA+MS', 'PhD'], 'ol' => false],
            ['name' => 'Warren Wilson College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Western Carolina University', 'psych' => ['BS', 'MS', 'MA', 'MAEd', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'William Peace University', 'psych' => ['BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Wingate University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Winston-Salem State University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'University of North Carolina Hospitals', 'loc' => 'Chapel Hill', 'deg' => ['MD-DO']],
            ['name' => 'Duke University Hospital', 'loc' => 'Durham', 'deg' => ['MD-DO']],
            ['name' => 'ECU Health Medical Center/East Carolina University', 'loc' => 'Greenville', 'deg' => ['MD-DO']],
            ['name' => 'Wake Forest Univ Baptist Medical Center Psychiatry Residency Program', 'loc' => 'Winston-Salem', 'deg' => ['MD-DO']],
            ['name' => 'Mountain Area Health Education Center', 'loc' => 'Asheville', 'deg' => ['MD-DO']],
            ['name' => 'Carolinas Medical Center Psychiatry Residency Program', 'loc' => 'Charlotte', 'deg' => ['MD-DO']],
            ['name' => 'Cape Fear Valley Health', 'loc' => 'Fayetteville', 'deg' => ['MD-DO']],
            ['name' => 'Cone Health', 'loc' => 'Greensboro', 'deg' => ['MD-DO']],
            ['name' => 'Mountain Area Health Education Center (Linville)', 'loc' => 'Asheville', 'deg' => ['MD-DO']],
            ['name' => 'Novant Health New Hanover Regional Medical Center', 'loc' => 'Wilmington', 'deg' => ['MD-DO']],
            ['name' => 'Wake Forest School of Medicine', 'loc' => 'Winston-Salem', 'deg' => ['MD-PhD']],
            ['name' => 'Brody School of Medicine at East Carolina University', 'loc' => 'Greenville', 'deg' => ['MD-PhD']],
            ['name' => 'Duke University School of Medicine', 'loc' => 'Durham', 'deg' => ['MD-PhD']],
            ['name' => 'University of North Carolina at Chapel Hill School of Medicine', 'loc' => 'Chapel Hill', 'deg' => ['MD-PhD']],
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
            ['name' => 'Behavioral Health Care of Cape Fear Valley', 'loc' => 'Fayetteville', 'type' => 'state_institution'],
            ['name' => 'Broughton Hospital', 'loc' => 'Morganton', 'type' => 'state_institution'],
            ['name' => 'Brynn Marr Behavioral Healthcare System', 'loc' => 'Jacksonville', 'type' => 'state_institution'],
            ['name' => 'Butner Alcohol and Drug Abuse Treatment Center', 'loc' => 'Butner', 'type' => 'state_institution'],
            ['name' => 'Central Regional Hospital', 'loc' => 'Butner', 'type' => 'state_institution'],
            ['name' => 'Cherry Hospital', 'loc' => 'Goldsboro', 'type' => 'state_institution'],
            ['name' => 'Dorothea Dix Hospital', 'loc' => 'Raleigh', 'type' => 'state_institution'],
            ['name' => 'John Umstead Hospital', 'loc' => 'Butner', 'type' => 'state_institution'],
            ['name' => 'JF Keith Alcohol & Drug Abuse Treatment Center', 'loc' => 'Black Mountain', 'type' => 'state_institution'],
            ['name' => 'Holly Hill Hospital', 'loc' => 'Raleigh', 'type' => 'state_institution'],
            ['name' => 'Kings Mountain Hospital', 'loc' => 'Kings Mountain', 'type' => 'state_institution'],
            ['name' => 'The Oaks Behavioral Health Hospital', 'loc' => 'Wilmington', 'type' => 'state_institution'],
            ['name' => 'R. J. Blackley Alcohol & Drug Abuse Treatment Center', 'loc' => 'Butner', 'type' => 'state_institution'],
            ['name' => 'WB Jones Alcohol and Drug Abuse Treatment Center', 'loc' => 'Greensboro', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Asheville VA Medical Center', 'loc' => 'Asheville', 'type' => 'va_facility'],
            ['name' => 'W.G. (Bill) Hefner Salisbury Department of Veterans Affairs Medical Center', 'loc' => 'Salisbury', 'type' => 'va_facility'],
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
