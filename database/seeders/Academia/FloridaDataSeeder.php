<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class FloridaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'florida'],
            ['name' => 'Florida', 'code' => 'FL']
        );

        $universities = [
            ['name' => 'AdventHealth University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Ave Maria University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Baptist University of Florida', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Barry University', 'psych' => ['BS', 'BS+MS', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Beacon College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Bethune-Cookman University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Broward College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Chipola College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'College of Central Florida', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Daytona State College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Eckerd College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Edward Waters University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Embry-Riddle Aeronautical University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Everglades University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Flagler College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Florida A&M University', 'psych' => ['BS', 'BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Florida Atlantic University', 'psych' => ['BS', 'BA', 'MA', 'M.Ed', 'M.Ed (OL)', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => true],
            ['name' => 'Florida College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Florida Gateway College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Florida Gulf Coast University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Florida Institute of Technology', 'psych' => ['BS', 'BA', 'BA (OL)', 'MS', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Florida International University', 'psych' => ['BA', 'MS', 'EdS', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'Florida Memorial University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Florida National University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Florida Polytechnic University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Florida Southern College', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Florida Southwestern State College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Florida State College at Jacksonville', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Florida State University', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'Indian River State College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Gulf Coast State College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Jacksonville University', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],

            ['name' => 'Keiser University', 'psych' => ['BA', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lynn University', 'psych' => ['BS', 'BS (OL)', 'MS'], 'neuro' => [], 'ol' => true],
            ['name' => 'Miami Dade College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'New College of Florida', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Nova Southeastern University', 'psych' => ['BS', 'MS', 'PsyD', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Northwest Florida State College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Palm Beach Atlantic University', 'psych' => ['BS', 'MS', 'MS (OL)', 'MA'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'Palm Beach State College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Pensacola State College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Polk State College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Ringling College of Art and Design', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Rollins College', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Saint Leo University', 'psych' => ['BA', 'BS (OL)', 'BA (OL)', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Seminole State College of Florida', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Southeastern University', 'psych' => ['BS', 'BS (OL)', 'MS'], 'neuro' => [], 'ol' => true],
            ['name' => 'State College of FL Manatee-Sarasota', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'St. Johns River State College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'St. Petersburg College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'St. Thomas University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Stetson University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Trinity College of Florida', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Central Florida', 'psych' => ['BS', 'MS', 'MA', 'MEd', 'PhD'], 'neuro' => ['BS', 'MS'], 'ol' => false],
            ['name' => 'University of Florida', 'psych' => ['BS', 'MS', 'MA', 'MEd', 'MAE', 'EdS', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'University of Miami', 'psych' => ['BS', 'BA', 'MS', 'MSEd', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'University of North Florida', 'psych' => ['BS', 'BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of South Florida', 'psych' => ['BA', 'MA', 'PhD'], 'neuro' => ['MS', 'PhD'], 'ol' => false],
            ['name' => 'University of Tampa', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of West Florida', 'psych' => ['BS', 'BA', 'BA (OL)', 'MA'], 'neuro' => [], 'ol' => true],
            ['name' => 'Valencia College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Warner University', 'psych' => ['BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Webber International University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'University of Florida Psychiatry Residency Program', 'loc' => 'Gainesville', 'deg' => ['MD-DO']],
            ['name' => 'University of Miami/Jackson Health System', 'loc' => 'Miami', 'deg' => ['MD-DO']],
            ['name' => 'University of South Florida Morsani', 'loc' => 'Tampa', 'deg' => ['MD-DO']],
            ['name' => 'University of Florida College of Medicine Jacksonville', 'loc' => 'Jacksonville', 'deg' => ['MD-DO']],
            ['name' => 'Larkin Community Hospital', 'loc' => 'South Miami', 'deg' => ['MD-DO']],
            ['name' => 'Citrus Health Network Inc', 'loc' => 'Hialeah', 'deg' => ['MD-DO']],
            ['name' => 'University of Central Florida/HCA Florida Healthcare (Gainesville)', 'loc' => 'Gainesville', 'deg' => ['MD-DO']],
            ['name' => 'Florida Atlantic University Charles E. Schmidt College of Medicine', 'loc' => 'Boca Raton', 'deg' => ['MD-DO']],
            ['name' => 'HCA Florida Healthcare/USF Morsani College of Medicine GME Largo Hospital', 'loc' => 'Largo', 'deg' => ['MD-DO']],
            ['name' => 'HCA Florida Healthcare/Woodmont Hospital Psychiatry Residency Program', 'loc' => 'West Palm Beach', 'deg' => ['MD-DO']],
            ['name' => 'HCA Florida Healthcare/Aventura Hospital Psychiatry Residency Program', 'loc' => 'Aventura', 'deg' => ['MD-DO']],
            ['name' => 'Memorial Healthcare System, Hollywood, Florida', 'loc' => 'Pembroke Pines', 'deg' => ['MD-DO']],
            ['name' => 'Community Health of South Florida, Inc (CHI)', 'loc' => 'Miami', 'deg' => ['MD-DO']],
            ['name' => 'Mount Sinai Medical Center of Florida, Inc Psychiatry Residency Program', 'loc' => 'Miami Beach', 'deg' => ['MD-DO']],
            ['name' => 'HCA Florida Orange Park Hospital', 'loc' => 'Orange Park', 'deg' => ['MD-DO']],
            ['name' => 'University of Central Florida/HCA Florida Healthcare (Greater Orlando/Osceola)', 'loc' => 'Orlando', 'deg' => ['MD-DO']],
            ['name' => 'Nova Southeastern University-College of Osteopathic Medicine (Orlando)', 'loc' => 'Orlando', 'deg' => ['MD-DO']],
            ['name' => 'Nova Southeastern University-College of Osteopathic Med (Bay Pines) Psychiatry Residency Program', 'loc' => 'St. Petersburg', 'deg' => ['MD-DO']],
            ['name' => 'BayCare Health System', 'loc' => 'New Port Richey', 'deg' => ['MD-DO']],
            ['name' => 'Broward Health', 'loc' => 'Fort Lauderdale', 'deg' => ['MD-DO']],
            ['name' => 'Centerstone', 'loc' => 'Bradenton', 'deg' => ['MD-DO']],
            ['name' => 'Lakeland Regional Health', 'loc' => 'Lakeland', 'deg' => ['MD-DO']],
            ['name' => 'University of Central Florida/HCA Florida Healthcare (Tallahassee) Psychiatry Residency Program', 'loc' => 'Tallahassee', 'deg' => ['MD-DO']],
            ['name' => 'Southern Winds Hospital', 'loc' => 'Hialeah', 'deg' => ['MD-DO']],
            ['name' => 'Florida State University College of Medicine', 'loc' => 'Tallahassee', 'deg' => ['MD-DO']],
            ['name' => 'University of Central Florida/HCA Florida Healthcare Psychiatry Residency Program', 'loc' => 'Pensacola', 'deg' => ['MD-DO']],
            ['name' => 'Banyan Community Health Center Inc. Psychiatry Residency Program', 'loc' => 'Miami', 'deg' => ['MD-DO']],
            ['name' => 'SMA Healthcare Psychiatry Residency Program', 'loc' => 'Daytona Beach', 'deg' => ['MD-DO']],
            ['name' => 'University of Florida College of Medicine', 'loc' => 'Gainesville', 'deg' => ['MD-PhD']],
            ['name' => 'University of Miami Miller School of Medicine', 'loc' => 'Miami', 'deg' => ['MD-PhD']],
            ['name' => 'University of South Florida Morsani College of Medicine', 'loc' => 'Tampa', 'deg' => ['MD-PhD']],
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
            ['name' => 'Central Florida Behavioral Health', 'loc' => 'Orland', 'type' => 'state_institution'],
            ['name' => 'Citrus Health Network, Inc', 'loc' => 'Hialeah and Miami', 'type' => 'state_institution'],
            ['name' => 'Devereux Florida', 'loc' => 'Orlando', 'type' => 'state_institution'],
            ['name' => 'Florida State Hospital', 'loc' => 'Chattahoochee', 'type' => 'state_institution'],
            ['name' => 'Fort Lauderdale Behavioral Health', 'loc' => 'Fort Lauderdale', 'type' => 'state_institution'],
            ['name' => 'Gulf Coast Treatment Center', 'loc' => 'Fort Walton Beach', 'type' => 'state_institution'],
            ['name' => 'La Amistad Behavioral Health Services Psychiatric Hospital', 'loc' => 'Maitland', 'type' => 'state_institution'],
            ['name' => 'Lakeview Center', 'loc' => 'Pensacola', 'type' => 'state_institution'],
            ['name' => 'LifeStream Behavioral Center', 'loc' => 'Leesburg', 'type' => 'state_institution'],
            ['name' => 'Palm Shores Behavioral Health', 'loc' => 'Bradenton', 'type' => 'state_institution'],
            ['name' => 'New Horizons of The Treasure Coast', 'loc' => 'Fort Pierce', 'type' => 'state_institution'],
            ['name' => 'North Florida Evaluation & Treatment Center', 'loc' => 'Gainesville', 'type' => 'state_institution'],
            ['name' => 'Northeast Florida State Hospital', 'loc' => 'Macclenny', 'type' => 'state_institution'],
            ['name' => 'Park Royal Hospital', 'loc' => 'Fort Myers', 'type' => 'state_institution'],
            ['name' => 'North Tampa Behavioral Health Hospital', 'loc' => 'Wesley Chapel', 'type' => 'state_institution'],
            ['name' => 'Port Saint Lucie Hospital', 'loc' => 'Port Saint Lucie', 'type' => 'state_institution'],
            ['name' => 'Sandy Pines Hospital', 'loc' => 'Jupiter', 'type' => 'state_institution'],
            ['name' => 'UF Health Shands Psychiatric Hospital', 'loc' => 'Gainesville', 'type' => 'state_institution'],
            ['name' => 'Springbrook Hospital', 'loc' => 'Brooksville', 'type' => 'state_institution'],
            ['name' => 'University Behavioral Center', 'loc' => 'Orlando', 'type' => 'state_institution'],
            ['name' => 'The Willough', 'loc' => 'Naples', 'type' => 'state_institution'],
            ['name' => 'Windmoor Healthcare', 'loc' => 'Clearwater', 'type' => 'state_institution'],

            // University Hospital (Client Placeholder)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Bruce W. Carter Department of Veterans Affairs Medical Center', 'loc' => 'Miami', 'type' => 'va_facility'],
            ['name' => 'C.W. Bill Young Department of Veterans Affairs Medical Center', 'loc' => 'Bay Pines', 'type' => 'va_facility'],
            ['name' => 'Jacksonville North VA Clinic', 'loc' => 'Jacksonville', 'type' => 'va_facility'],
            ['name' => 'James A. Haley Veterans\' Hospital', 'loc' => 'Tampa', 'type' => 'va_facility'],
            ['name' => 'Lake City VA Medical Center', 'loc' => 'Lake City', 'type' => 'va_facility'],
            ['name' => 'Malcom Randall Department of Veterans Affairs Medical Center', 'loc' => 'Gainesville', 'type' => 'va_facility'],
            ['name' => 'Orlando VA Medical Center', 'loc' => 'Orlando', 'type' => 'va_facility'],
            ['name' => 'West Palm Beach VA Medical Center', 'loc' => 'West Palm Beach', 'type' => 'va_facility'],
            ['name' => 'Bruce W. Carter Department of Veterans Affairs Medical Center', 'loc' => 'Miami', 'type' => 'va_facility'],
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
