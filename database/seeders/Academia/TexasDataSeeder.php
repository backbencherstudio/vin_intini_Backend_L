<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class TexasDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'texas'],
            ['name' => 'Texas', 'code' => 'TX']
        );

        $universities = [
            ['name' => 'Abilene Christian University', 'psych' => ['BS', 'BS (OL)', 'MS'], 'neuro' => [], 'ol' => true],
            ['name' => 'Amberton University', 'psych' => ['MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Angelo State University', 'psych' => ['BS', 'BA', 'MS', 'MA', 'MEd', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Arlington Baptist University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Austin College', 'psych' => ['BS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Baptist University of the Americas', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Baylor College of Medicine', 'psych' => ['PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Baylor University', 'psych' => ['BS', 'BA', 'MSEd', 'EdS', 'PsyD', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Brazosport College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'College of Biblical Studies', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Concordia University Texas', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Criswell College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Dallas Baptist University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Dallas Christian College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'East Texas A&M University', 'psych' => ['BS', 'MS', 'MEd', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'East Texas Baptist University', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Hardin-Simmons University', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Houston Christian University', 'psych' => ['BA', 'BA (OL)', 'BA+MA', 'MA', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Howard Payne University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Huston-Tillotson University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Jarvis Christian University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'The King\'s University', 'psych' => ['BS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lamar University', 'psych' => ['BS', 'BA', 'MS', 'MEd (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'LeTourneau University', 'psych' => ['BS', 'BS (OL)', 'BA', 'MA'], 'neuro' => [], 'ol' => true],
            ['name' => 'Lubbock Christian University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'McMurry University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Midland College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Midwestern State University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Nelson University', 'psych' => ['BS', 'MS', 'MA', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'North American University', 'psych' => ['MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Our Lady of the Lake University', 'psych' => ['BA', 'MS', 'MA (OL)', 'PsyD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Parker University', 'psych' => ['BS (OL)', 'MS', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Paul Quinn College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Prairie View A&M University', 'psych' => ['BS', 'MS', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Rice University', 'psych' => ['BA', 'MA', 'PhD'], 'neuro' => ['BA', 'PhD'], 'ol' => false],
            ['name' => 'Sam Houston State University', 'psych' => ['BS', 'BA', 'MA', 'ME', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Schreiner University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Southern Methodist University', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'South Texas College of Law', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'South Texas College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Southwestern Adventist University', 'psych' => ['BS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Southwestern University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'St. Edward\'s University', 'psych' => ['BA', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'St. Mary\'s University', 'psych' => ['BA', 'MS', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Stephen F. Austin State University', 'psych' => ['BS', 'BS (OL)', 'BA', 'MA', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Sul Ross State University', 'psych' => ['BA', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Tarleton State University', 'psych' => ['BS', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Texas A&M International University', 'psych' => ['BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Texas A&M University', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'Texas A&M University-Corpus Christi', 'psych' => ['BA', 'BA (OL)', 'MS', 'MA'], 'neuro' => [], 'ol' => true],
            ['name' => 'Texas A&M University at Galveston', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Texas A&M University-Kingsville', 'psych' => ['BA', 'MS', 'MS (OL)', 'MA'], 'neuro' => [], 'ol' => true],
            ['name' => 'Texas A&M University-San Antonio', 'psych' => ['BS', 'BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Texas A&M University-Texarkana', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Texas Christian University', 'psych' => ['BS', 'BS', 'MS', 'MA', 'MEd', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Texas College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Texas Lutheran University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Texas Southern University', 'psych' => ['BA', 'MA', 'MEd', 'EdD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Texas Tech University', 'psych' => ['BS', 'BS (OL)', 'BA', 'BA (OL)', 'MEd', 'PhD'], 'neuro' => ['PhD'], 'ol' => true],
            ['name' => 'Texas Tech Univ Health Sciences Center', 'psych' => ['MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Texas Wesleyan University', 'psych' => ['BS', 'MS (OL)', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Texas Woman\'s University', 'psych' => ['BS', 'MS', 'MA', 'MEd', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Trinity University', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'University of Dallas', 'psych' => ['BA', 'BA+MPsy', 'MPsy'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Houston', 'psych' => ['BS', 'BA', 'MEd', 'PhD'], 'neuro' => ['PhD'], 'ol' => false],
            ['name' => 'University of Houston-Clear Lake', 'psych' => ['BS', 'MS', 'MA', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Houston-Downtown', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Houston-Victoria', 'psych' => ['BS', 'BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],

            ['name' => 'University of the Incarnate Word', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Mary Hardin-Baylor', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'University of North Texas', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of St. Thomas', 'psych' => ['BS', 'BA', 'BA+MA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Texas at Arlington', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Texas Austin', 'psych' => ['BS', 'BA', 'MA', 'MEd', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'University of Texas Dallas', 'psych' => ['BS', 'MS', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'University of Texas El Paso', 'psych' => ['BS', 'BA', 'MS', 'MEd', 'MCRC', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'University of Texas Permian Basin', 'psych' => ['BS', 'BA', 'BA (OL)', 'MA'], 'neuro' => [], 'ol' => true],
            ['name' => 'University of Texas Rio Grande Valley', 'psych' => ['BS', 'MS', 'MA', 'MEd', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Texas at San Antonio', 'psych' => ['BS', 'BA', 'MS', 'MA', 'MEd', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'University of Texas at Tyler', 'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Wayland Baptist University', 'psych' => ['BS', 'BA', 'BS+MA', 'BA+MA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'West Texas A&M University', 'psych' => ['BS', 'BA', 'MS', 'MA', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Wiley University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Wade College', 'psych' => [], 'neuro' => [], 'ol' => false],
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
            ['name' => 'Texas A&M College of Medicine-Scott and White Medical Center', 'loc' => 'Temple', 'deg' => ['MD-DO']],
            ['name' => 'Univ of Texas Health Science Center San Antonio Joe and Teresa Lozano School of Medicine', 'loc' => 'San Antonio', 'deg' => ['MD-DO']],
            ['name' => 'Texas Tech University Health Sciences Center at Lubbock', 'loc' => 'Lubbock', 'deg' => ['MD-DO']],
            ['name' => 'University of Texas Southwestern Medical Center', 'loc' => 'Dallas', 'deg' => ['MD-DO']],
            ['name' => 'University of Texas Medical Branch Hospitals', 'loc' => 'Galveston', 'deg' => ['MD-DO']],
            ['name' => 'Texas Tech University HSC El Paso', 'loc' => 'El Paso', 'deg' => ['MD-DO']],
            ['name' => 'University of Texas at Austin Dell Medical School', 'loc' => 'Austin', 'deg' => ['MD-DO']],
            ['name' => 'Baylor College of Medicine', 'loc' => 'Houston', 'deg' => ['MD-DO']],
            ['name' => 'John Peter Smith Hospital (Tarrant County Hospital District)', 'loc' => 'Fort Worth', 'deg' => ['MD-DO']],
            ['name' => 'University of Texas Health Science Center at Houston', 'loc' => 'Houston', 'deg' => ['MD-DO']],
            ['name' => 'University of Texas RGV', 'loc' => 'Harlingen', 'deg' => ['MD-DO']],
            ['name' => 'Texas Tech Univ Health Sciences Center (Permian Basin)', 'loc' => 'Odessa', 'deg' => ['MD-DO']],
            ['name' => 'University of Texas Health Science Center at Tyler', 'loc' => 'Tyler', 'deg' => ['MD-DO']],
            ['name' => 'TX Inst for Grad Med Education and Research (TIGMER)', 'loc' => 'San Antonio', 'deg' => ['MD-DO']],
            ['name' => 'Darnall Army Medical Center', 'loc' => 'Fort Hood', 'deg' => ['MD-DO']],
            ['name' => 'University of Texas Health Science Center at Tyler/UT Health Pittsburgh', 'loc' => 'Tyler', 'deg' => ['MD-DO']],
            ['name' => 'Texas Tech University HSC El Paso/Hospitals of Providence Transmountain Campus', 'loc' => 'El Paso', 'deg' => ['MD-DO']],
            ['name' => 'Texas A&M University School of Medicine', 'loc' => 'Bryan', 'deg' => ['MD-DO']],
            ['name' => 'Project Vida Health Center', 'loc' => 'El Paso', 'deg' => ['MD-DO']],
            ['name' => 'Baptist Hospitals of Southeast Texas Psychiatry Residency Program', 'loc' => 'Beaumont', 'deg' => ['MD-DO']],
            ['name' => 'Rio Grande Valley Medical Education Consortium Psychiatry Residency Program', 'loc' => 'Edinburg', 'deg' => ['MD-DO']],
            ['name' => 'Baylor College of Medicine', 'loc' => 'Houston', 'deg' => ['MD-PhD']],
            ['name' => 'McGovern Medical School at UTHealth/MD Anderson Cancer Center/Univ of Puerto Rico Tri-Institutional Program', 'loc' => 'Houston', 'deg' => ['MD-PhD']],
            ['name' => 'Texas A&M University Health Sciences Center College of Medicine', 'loc' => 'College Station', 'deg' => ['MD-PhD']],
            ['name' => 'Texas Tech University School of Medicine', 'loc' => 'Lubbock', 'deg' => ['MD-PhD']],
            ['name' => 'University of Texas Medical Branch at Galveston', 'loc' => 'Galveston', 'deg' => ['MD-PhD']],
            ['name' => 'University of Texas Health San Antonio, Long School of Medicine', 'loc' => 'San Antonio', 'deg' => ['MD-PhD']],
            ['name' => 'University of Texas, Southwestern Med Center', 'loc' => 'Dallas', 'deg' => ['MD-PhD']],
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
            ['name' => 'Acadia Healthcare', 'loc' => 'various', 'type' => 'state_institution'],
            ['name' => 'Austin Lakes Hospital', 'loc' => 'Austin', 'type' => 'state_institution'],
            ['name' => 'Austin State Hospital', 'loc' => 'Austin', 'type' => 'state_institution'],
            ['name' => 'Big Spring State Hospital', 'loc' => 'Big Spring', 'type' => 'state_institution'],
            ['name' => 'Cedar Crest Hospital & Residential Treatment Center', 'loc' => 'Belton', 'type' => 'state_institution'],
            ['name' => 'Hickory Trail Hospital', 'loc' => 'Desoto', 'type' => 'state_institution'],
            ['name' => 'Compass Hospital', 'loc' => 'San Antonio', 'type' => 'state_institution'],
            ['name' => 'Cypress Creek Hospital', 'loc' => 'Houston', 'type' => 'state_institution'],
            ['name' => 'Devereux Texas', 'loc' => 'League City', 'type' => 'state_institution'],
            ['name' => 'El Paso Psychiatric Center', 'loc' => 'El Paso', 'type' => 'state_institution'],
            ['name' => 'Green Oaks Behavioral Healthcare Service', 'loc' => 'Dallas', 'type' => 'state_institution'],
            ['name' => 'Kerrville State Hospital', 'loc' => 'Kerrville', 'type' => 'state_institution'],
            ['name' => 'Laurel Ridge Treatment Center', 'loc' => 'San Antonio', 'type' => 'state_institution'],
            ['name' => 'Memorial Hermann Mental Health', 'loc' => 'Houston', 'type' => 'state_institution'],
            ['name' => 'Menninger Clinic', 'loc' => 'Houston', 'type' => 'state_institution'],
            ['name' => 'Meridell Achievement Center', 'loc' => 'Liberty Hill', 'type' => 'state_institution'],
            ['name' => 'Advent Health', 'loc' => 'Killeen', 'type' => 'state_institution'],
            ['name' => 'Millwood Hospital', 'loc' => 'Arlington', 'type' => 'state_institution'],
            ['name' => 'North Texas State Hospital', 'loc' => 'Wichita Falls', 'type' => 'state_institution'],
            ['name' => 'Red River Hospital', 'loc' => 'Wichita Falls', 'type' => 'state_institution'],
            ['name' => 'Rio Grande State Center', 'loc' => 'Harlingen', 'type' => 'state_institution'],
            ['name' => 'River Crest Hospital', 'loc' => 'San Angelo', 'type' => 'state_institution'],
            ['name' => 'Rusk State Hospital', 'loc' => 'Rusk', 'type' => 'state_institution'],
            ['name' => 'San Antonio State Hospital', 'loc' => 'San Antonio', 'type' => 'state_institution'],
            ['name' => 'San Marcos Treatment Center', 'loc' => 'San Marcos', 'type' => 'state_institution'],
            ['name' => 'Ascension Seton Shoal Creek Hospital', 'loc' => 'Austin', 'type' => 'state_institution'],
            ['name' => 'Terrell State Hospital', 'loc' => 'Terrell', 'type' => 'state_institution'],
            ['name' => 'Texas NeuroRehab Center', 'loc' => 'Austin', 'type' => 'state_institution'],
            ['name' => 'West Oaks Hospital', 'loc' => 'Houston', 'type' => 'state_institution'],
            ['name' => 'Waco Center for Youth', 'loc' => 'Waco', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder from template)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities (ইমেজ ৪)
            ['name' => 'Audie L. Murphy Memorial Veterans\' Hospital', 'loc' => 'San Antonio', 'type' => 'va_facility'],
            ['name' => 'Dallas VA Medical Center', 'loc' => 'Dallas', 'type' => 'va_facility'],
            ['name' => 'Doris Miller Department of Veterans Affairs Medical Center', 'loc' => 'Waco', 'type' => 'va_facility'],
            ['name' => 'George H. O\'Brien, Jr., Department of Veterans Affairs Medical Center', 'loc' => 'Big Spring', 'type' => 'va_facility'],
            ['name' => 'Olin E. Teague Veterans\' Center', 'loc' => 'Temple', 'type' => 'va_facility'],
            ['name' => 'Sam Rayburn Memorial Veterans Center', 'loc' => 'Bonham', 'type' => 'va_facility'],
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
