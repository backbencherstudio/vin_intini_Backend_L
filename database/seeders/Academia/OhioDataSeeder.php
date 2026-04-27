<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class OhioDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'ohio'],
            ['name' => 'Ohio', 'code' => 'OH']
        );

        $universities = [
            ['name' => 'Air Force Institute of Technology', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Art Academy of Cincinnati', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Ashland University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Baldwin Wallace University', 'psych' => ['BS', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Bluffton University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bowling Green State University', 'psych' => ['BS', 'BA', 'MA', 'MEd', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Capital University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Case Western Reserve University', 'psych' => ['BA', 'BA+MA', 'MS', 'MA', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'Cedarville University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Central State University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Cleveland Institute of Art', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Cleveland Institute of Music', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Cleveland State University', 'psych' => ['BA', 'BA+MA', 'MA', 'MEd', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'College of Wooster', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Columbus College of Art and Design', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Defiance College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Denison University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Franciscan University of Steubenville', 'psych' => ['BS', 'BA', 'BA (OL)', 'MA'], 'neuro' => [], 'ol' => true],
            ['name' => 'Franklin University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'God\'s Bible School and College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Good Samaritan Coll Nursing & Health Sci', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Heidelberg University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Hiram College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'John Carroll University', 'psych' => ['BS', 'MS', 'MA', 'MA (OL)', 'MEd'], 'neuro' => [], 'ol' => true],
            ['name' => 'Kent State University', 'psych' => ['BS', 'BA', 'MA', 'PhD'], 'neuro' => ['MS', 'PhD'], 'ol' => false],
            ['name' => 'Lake Erie College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lourdes University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Malone University', 'psych' => ['BA', 'BA+MA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Marietta College', 'psych' => ['BA', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Mercy College of Ohio', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Mount Carmel College of Nursing', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Miami University', 'psych' => ['BA', 'BA (OL)', 'MEd', 'PhD'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'Mount St. Joseph University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Mount Vernon Nazarene University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],

            ['name' => 'Muskingum University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northeast Ohio Medical University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Oberlin College', 'psych' => ['BS', 'BA'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'Ohio Christian University', 'psych' => ['BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Ohio Dominican University', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Ohio Northern University', 'psych' => ['BS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Ohio State University', 'psych' => ['BS', 'BA', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'Ohio University', 'psych' => ['BS', 'BS (OL)', 'BS+MS', 'MEd', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Ohio University- Chillicothe', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Ohio University- Southern', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Ohio Wesleyan University', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Otterbein University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Shawnee State University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Tiffin University', 'psych' => ['BA', 'BA (OL)', 'MS (OL)'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'Tri-State Bible College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Akron', 'psych' => ['BA', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Cincinnati', 'psych' => ['BS', 'BA', 'MA', 'EdS', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'University of Dayton', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Findlay', 'psych' => ['BS', 'EdD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Mount Union', 'psych' => ['BS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'University of Northwestern Ohio', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Rio Grande', 'psych' => ['BS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'University of Toledo', 'psych' => ['BA', 'MA', 'MA (OL)', 'EdS', 'PhD'], 'neuro' => ['PhD'], 'ol' => true],
            ['name' => 'Ursuline College', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Walsh University', 'psych' => ['BA', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Wilberforce University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Wilmington College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Wittenberg University', 'psych' => ['BS', 'BA'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'Wright State University', 'psych' => ['BS', 'BA', 'MS', 'PsyM', 'MEd', 'PhD'], 'neuro' => ['BS', 'BS+MS', 'MS'], 'ol' => false],
            ['name' => 'Xavier University', 'psych' => ['BS', 'PsyD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Youngstown State University', 'psych' => ['BA', 'BA (OL)', 'MSEd', 'EdS'], 'neuro' => [], 'ol' => true],
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
            ['name' => 'University of Cincinnati Medical Center/College of Medicine', 'loc' => 'Cincinnati', 'deg' => ['MD-DO']],
            ['name' => 'The Metrohealth System/Case Western Reserve Univ Psychiatry Residency', 'loc' => 'Cleveland', 'deg' => ['MD-DO']],
            ['name' => 'Wright State University', 'loc' => 'Fairborn', 'deg' => ['MD-DO']],
            ['name' => 'Case Western Reserve University/University Hospitals Cleveland Medical Center', 'loc' => 'Cleveland', 'deg' => ['MD-DO']],
            ['name' => 'University of Toledo Psychiatry Residency Program', 'loc' => 'Toledo', 'deg' => ['MD-DO']],
            ['name' => 'Ohio State University Hospital', 'loc' => 'Columbus', 'deg' => ['MD-DO']],
            ['name' => 'Cleveland Clinic Foundation', 'loc' => 'Cleveland', 'deg' => ['MD-DO']],
            ['name' => 'Summa Health System/NEOMED', 'loc' => 'Akron', 'deg' => ['MD-DO']],
            ['name' => 'OhioHealth/Riverside Methodist Hospital', 'loc' => 'Columbus', 'deg' => ['MD-DO']],
            ['name' => 'Adena Regional Medical Center Psychiatry Residency Program', 'loc' => 'Chillicothe', 'deg' => ['MD-DO']],
            ['name' => 'Akron General Medical Center', 'loc' => 'Akron', 'deg' => ['MD-DO']],
            ['name' => 'Case Western Reserve University School of Medicine', 'loc' => 'Cleveland', 'deg' => ['MD-PhD']],
            ['name' => 'Northeastern Ohio College of Medicine', 'loc' => 'Rootstown', 'deg' => ['MD-PhD']],
            ['name' => 'Ohio State University College of Medicine', 'loc' => 'Columbus', 'deg' => ['MD-PhD']],
            ['name' => 'University of Cincinnati College of Medicine', 'loc' => 'Cincinnati', 'deg' => ['MD-PhD']],
            ['name' => 'University of Toledo College of Medicine', 'loc' => 'Toledo', 'deg' => ['MD-PhD']],
            ['name' => 'Wright State University School of Medicine', 'loc' => 'Dayton', 'deg' => ['MD-PhD']],
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
            ['name' => 'Belmont Pines Hospital & RTC', 'loc' => 'Youngstown', 'type' => 'state_institution'],
            ['name' => 'Fox Run Hospital', 'loc' => 'Saint Clairsville', 'type' => 'state_institution'],
            ['name' => 'Glenbeigh Hospital and Outpatient Centers', 'loc' => 'Rock Creek', 'type' => 'state_institution'],
            ['name' => 'Laurelwood Hospital & Counseling Centers', 'loc' => 'Willoughby', 'type' => 'state_institution'],
            ['name' => 'Lindner Center of HOPE', 'loc' => 'Mason', 'type' => 'state_institution'],
            ['name' => 'Ohio Hospital for Psychiatry', 'loc' => 'Columbus', 'type' => 'state_institution'],
            ['name' => 'Ohio State University Harding Hospital', 'loc' => 'Columbus', 'type' => 'state_institution'],
            ['name' => 'Windsor Hospital', 'loc' => 'Chagrin Falls', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Chillicothe VA Medical Center', 'loc' => 'Chillicothe', 'type' => 'va_facility'],
            ['name' => 'Cincinnati VA Medical Center', 'loc' => 'Cincinnati', 'type' => 'va_facility'],
            ['name' => 'Dayton VA Medical Center', 'loc' => 'Dayton', 'type' => 'va_facility'],
            ['name' => 'Louis Stokes Cleveland Department of Veterans Affairs Medical Center', 'loc' => 'Cleveland', 'type' => 'va_facility'],
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
