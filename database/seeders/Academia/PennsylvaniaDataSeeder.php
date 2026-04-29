<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class PennsylvaniaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'pennsylvania'],
            ['name' => 'Pennsylvania', 'code' => 'PA']
        );

        $universities = [
            ['name' => 'Albright College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Allegheny College', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Alvernia University', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'American College of Financial Services', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Arcadia University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bloomsburg University of Penn', 'psych' => ['BS', 'MS', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Bryn Athyn College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Bryn Mawr College', 'psych' => ['A.B.'], 'neuro' => ['A.B.'], 'ol' => false],
            ['name' => 'Bucknell University', 'psych' => ['BA', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Cairn University', 'psych' => ['BA', 'BA (OL)', 'BA+MA', 'MS', 'MA'], 'neuro' => [], 'ol' => true],
            ['name' => 'Carlow University', 'psych' => ['BA', 'MS', 'MA', 'PsyD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Carnegie Mellon University', 'psych' => ['BA', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'Cedar Crest College', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Central Penn College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Chatham University', 'psych' => ['BS', 'BA', 'BA+MS', 'MA', 'PsyD'], 'neuro' => ['BS+MA'], 'ol' => false],
            ['name' => 'Chestnut Hill College', 'psych' => ['BA', 'MS', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Cheyney Univ of Pennsylvania', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Commonwealth University of Pennsylvania', 'psych' => ['BS', 'MS', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Curtis Institute of Music', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Delaware Valley University', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'DeSales University', 'psych' => ['BS', 'BS (OL)'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'Dickinson College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Drexel University', 'psych' => ['BS', 'BS+MS', 'MS', 'MA', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'Duquesne University', 'psych' => ['BA', 'MS', 'MSEd', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'East Stroudsburg University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Eastern University', 'psych' => ['BA', 'BA (OL)', 'MA', 'MA (OL)', 'MEd', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Elizabethtown College', 'psych' => ['BS', 'BA', 'BS+MA', 'BA+MA', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Franklin & Marshall College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Gannon University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Geisinger College of Health Sciences', 'psych' => ['MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Geneva College', 'psych' => ['BS', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Gettysburg College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Gratz College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Grove City College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Gwynedd Mercy University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Harrisburg Univ of S&T', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Haverford College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Holy Family University', 'psych' => ['BA', 'BA+MS', 'MS', 'PsyD'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Immaculata University', 'psych' => ['BA', 'MA', 'PsyD', 'PhD'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Indiana Univ of Pennsylvania', 'psych' => ['BA', 'MA', 'MEd', 'EdS', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Juniata College', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Keystone College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'King\'s College', 'psych' => ['BS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Kutztown Univ of Pennsylvania', 'psych' => ['BS', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lancaster Bible College', 'psych' => ['BS', 'BS+MA', 'BS+MEd', 'MA', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'La Roche College', 'psych' => ['BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'La Salle University', 'psych' => ['BA', 'MA', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lafayette College', 'psych' => ['BS', 'A.B.'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Lebanon Valley College', 'psych' => ['BS', 'BS+MS', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Lehigh University', 'psych' => ['BS', 'BA', 'BS+Med', 'BA+MEd', 'MS', 'MEd', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lincoln University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lock Haven University', 'psych' => ['BS', 'MS', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Lycoming College', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Mansfield Univ of Pennsylvania', 'psych' => ['BS', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Marywood University', 'psych' => ['BS', 'MS', 'MA', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Mercyhurst University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Messiah College', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Millersville University', 'psych' => ['BA', 'MS', 'MEd', 'EdS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Misericordia University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Moore College of Art and Design', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Moravian College', 'psych' => ['BA', 'MS', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Mount Aloysius College', 'psych' => ['BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Muhlenberg College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Neumann University', 'psych' => ['BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'PA College of Tech', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Penn State College of Medicine', 'psych' => [], 'neuro' => ['MS', 'PhD'], 'ol' => false],
            ['name' => 'Pennsylvania State University- Behrend', 'psych' => ['BS', 'BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Pennsylvania State University- Harrisburg', 'psych' => ['BS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Pennsylvania State University- Hershey', 'psych' => ['MA'], 'neuro' => ['MS', 'PhD'], 'ol' => false],
            ['name' => 'Pennsylvania State University- Great Valley', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Pennsylvania State University', 'psych' => ['BS', 'BA', 'MS', 'MEd', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'PennWest University- California', 'psych' => ['BS', 'MS', 'MS (OL)', 'MEd (OL)', 'EdS'], 'neuro' => [], 'ol' => true],
            ['name' => 'PennWest University- Clarion', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'PennWest University- Edinboro', 'psych' => ['BS', 'MEd', 'EdS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Point Park University', 'psych' => ['BS', 'BA, BA+MA, MA, PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Robert Morris University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Rosemont College', 'psych' => ['BA, BA+MA, MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Saint Francis University', 'psych' => ['BS, BS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Saint Joseph\'s University', 'psych' => ['BS, BA, MS'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Saint Vincent College', 'psych' => ['BS, MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Salus University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Seton Hill University', 'psych' => ['BA, BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Shippensburg University', 'psych' => ['BA, MS, EdD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Slippery Rock University', 'psych' => ['BS, BA, MA, MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Susquehanna University', 'psych' => ['BS, BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Swarthmore College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Temple University', 'psych' => ['BA, MS, MSEd, MEd, EdS, PhD'], 'neuro' => ['BS, PhD'], 'ol' => false],
            ['name' => 'Thiel College', 'psych' => ['BA'], 'neuro' => ['BS, BA'], 'ol' => false],
            ['name' => 'Thomas Jefferson University', 'psych' => ['BS, BS+MS, MS'], 'neuro' => ['BS, MS, PhD'], 'ol' => false],
            ['name' => 'University of Pennsylvania', 'psych' => ['BA, MS, MAPP, MSEd, PhD'], 'neuro' => ['BA, MS, PhD'], 'ol' => false],
            ['name' => 'University of Pittsburgh', 'psych' => ['BS, MS, MA, PhD'], 'neuro' => ['BS, MS, PhD'], 'ol' => false],
            ['name' => 'University of Scranton', 'psych' => ['BS, MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Valley Forge', 'psych' => ['BS, BS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Ursinus College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Villanova University', 'psych' => ['BS, BA, BA+MS, BS+MS, MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Washington & Jefferson College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Waynesburg University', 'psych' => ['BA, MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'West Chester University', 'psych' => ['BS, BS+MS, MS, PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Westminster College', 'psych' => ['BA, BA+MA, MEd'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Widener University', 'psych' => ['BA, BA (OL), PsyD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Wilkes University', 'psych' => ['BA, BA+MBA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Wilson College', 'psych' => ['BA, BA (OL)'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'WON Institute of Grad Studies', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'York College of Pennsylvania', 'psych' => ['BS, MEd'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'Albert Einstein Healthcare Network Psychiatry Residency Program', 'loc' => 'Philadelphia', 'deg' => ['MD-DO']],
            ['name' => 'Temple University Hospital', 'loc' => 'Philadelphia', 'deg' => ['MD-DO']],
            ['name' => 'Penn State Milton S Hershey Medical Center', 'loc' => 'Hershey', 'deg' => ['MD-DO']],
            ['name' => 'Sidney Kimmel Medical College at Thomas Jefferson University/TJUH', 'loc' => 'Philadelphia', 'deg' => ['MD-DO']],
            ['name' => 'University of Pennsylvania Health System', 'loc' => 'Philadelphia', 'deg' => ['MD-DO']],
            ['name' => 'Allegheny Health Network Med Ed Consortium (AGH) Psychiatry Residency', 'loc' => 'Pittsburgh', 'deg' => ['MD-DO']],
            ['name' => 'UPMC Medical Education Psychiatry Residency Program', 'loc' => 'Pittsburgh', 'deg' => ['MD-DO']],
            ['name' => 'Lake Erie College of Osteopathic Medicine', 'loc' => 'Erie', 'deg' => ['MD-DO']],
            ['name' => 'Lehigh Valley Health Network', 'loc' => 'Allentown', 'deg' => ['MD-DO']],
            ['name' => 'St. Luke\'s Hospital-Anderson Campus', 'loc' => 'Easton', 'deg' => ['MD-DO']],
            ['name' => 'Tower Health/Phoenixville Hospital', 'loc' => 'Phoenixville', 'deg' => ['MD-DO']],
            ['name' => 'Penn Highlands DuBois Psychiatry Residency Program', 'loc' => 'DuBois', 'deg' => ['MD-DO']],
            ['name' => 'St. Luke\'s University Hospital', 'loc' => 'Coaldale', 'deg' => ['MD-DO']],
            ['name' => 'Tower Health/Reading Hospital', 'loc' => 'West Reading', 'deg' => ['MD-DO']],
            ['name' => 'Crozer-Chester Medical Center', 'loc' => 'Upland', 'deg' => ['MD-DO']],
            ['name' => 'WellSpan Health/York Hospital Psychiatry Residency Program', 'loc' => 'York', 'deg' => ['MD-DO']],
            ['name' => 'Geisinger Health System Psychiatry Residency Program', 'loc' => 'Wilkes Barre', 'deg' => ['MD-DO']],
            ['name' => 'Drexel University College of Medicine', 'loc' => 'Philadelphia', 'deg' => ['MD-PhD']],
            ['name' => 'Sidney Kimmel Medical College at Thomas Jefferson University', 'loc' => 'Philadelphia', 'deg' => ['MD-PhD']],
            ['name' => 'Penn State University College of Medicine', 'loc' => 'Hershey', 'deg' => ['MD-PhD']],
            ['name' => 'University of Pennsylvania School of Medicine', 'loc' => 'Philadelphia', 'deg' => ['MD-PhD']],
            ['name' => 'University of Pittsburgh School of Medicine', 'loc' => 'Pittsburgh', 'deg' => ['MD-PhD']],
            ['name' => 'Temple University School of Medicine', 'loc' => 'Philadelphia', 'deg' => ['MD-PhD']],
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
            ['name' => 'Belmont Behavioral Health Hospital', 'loc' => 'Philadelphia', 'type' => 'state_institution'],
            ['name' => 'Allentown State Hospital', 'loc' => 'Allentown', 'type' => 'state_institution'],
            ['name' => 'Clarion Psychiatric Center', 'loc' => 'Clarion', 'type' => 'state_institution'],
            ['name' => 'Clarks Summit State Hospital', 'loc' => 'Clarks Summit', 'type' => 'state_institution'],
            ['name' => 'Cove Forge Behavioral Health Center', 'loc' => 'Williamsburg', 'type' => 'state_institution'],
            ['name' => 'Danville State Hospital', 'loc' => 'Danville', 'type' => 'state_institution'],
            ['name' => 'Eagleville Hospital', 'loc' => 'Eagleville', 'type' => 'state_institution'],
            ['name' => 'Eugenia Hospital', 'loc' => 'Lafayette Hill', 'type' => 'state_institution'],
            ['name' => 'Fairmount Behavioral Health System', 'loc' => 'Philadelphia', 'type' => 'state_institution'],
            ['name' => 'First Hospital of Wyoming Valley', 'loc' => 'Kingston', 'type' => 'state_institution'],
            ['name' => 'Foundations Behavioral Health', 'loc' => 'Doylestown', 'type' => 'state_institution'],
            ['name' => 'Friends Hospital', 'loc' => 'Philadelphia', 'type' => 'state_institution'],
            ['name' => 'Harrisburg State Hospital', 'loc' => 'Harrisburg', 'type' => 'state_institution'],
            ['name' => 'The Horsham Clinic', 'loc' => 'Ambler', 'type' => 'state_institution'],
            ['name' => 'KeyStone Center', 'loc' => 'Chester', 'type' => 'state_institution'],
            ['name' => 'Mayview State Hospital', 'loc' => 'Bridgeville', 'type' => 'state_institution'],
            ['name' => 'Meadows Psychiatric Center', 'loc' => 'Centre Hall', 'type' => 'state_institution'],
            ['name' => 'Mercy Behavioral Health', 'loc' => 'Pittsburgh', 'type' => 'state_institution'],
            ['name' => 'Norristown State Hospital', 'loc' => 'Norristown', 'type' => 'state_institution'],
            ['name' => 'Southwood Psychiatric Hospital', 'loc' => 'Pittsburgh', 'type' => 'state_institution'],
            ['name' => 'Torrance State Hospital', 'loc' => 'Torrance', 'type' => 'state_institution'],
            ['name' => 'Warren State Hospital', 'loc' => 'North Warren', 'type' => 'state_institution'],
            ['name' => 'Wernersville State Hospital', 'loc' => 'Wernersville', 'type' => 'state_institution'],
            ['name' => 'Roxbury Behavioral Healthcare', 'loc' => 'Shippensburg', 'type' => 'state_institution'],
            ['name' => 'Western Psychiatric Institute & Clinic', 'loc' => 'Pittsburgh', 'type' => 'state_institution'],
            ['name' => 'White Deer Run', 'loc' => 'Allenwood', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Butler VA Medical Center', 'loc' => 'Butler', 'type' => 'va_facility'],
            ['name' => 'Coatesville VA Medical Center', 'loc' => 'Coatesville', 'type' => 'va_facility'],
            ['name' => 'CPL Michael J. Crescenz Dept of Veterans Affairs Medical Center', 'loc' => 'Philadelphia', 'type' => 'va_facility'],
            ['name' => 'Erie VA Medical Center', 'loc' => 'Erie', 'type' => 'va_facility'],
            ['name' => 'H. John Heinz III Department of Veterans Affairs Medical Center', 'loc' => 'Pittsburgh', 'type' => 'va_facility'],
            ['name' => 'Lebanon VA Medical Center', 'loc' => 'Lebanon', 'type' => 'va_facility'],
            ['name' => 'Wilkes-Barre VA Medical Center', 'loc' => 'Wilkes-Barre', 'type' => 'va_facility'],
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
