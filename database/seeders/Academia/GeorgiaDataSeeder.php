<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class GeorgiaDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'georgia'],
            ['name' => 'Georgia', 'code' => 'GA']
        );

        $universities = [
            ['name' => 'Abraham Baldwin Ag College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Agnes Scott College', 'psych' => ['BA', 'MA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Augusta University', 'psych' => ['BS', 'MS', 'MEd'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'Albany State University', 'psych' => ['BA', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Atlanta Metro State College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Berry College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Beulah Heights University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Brenau University', 'psych' => ['BS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Brewton-Parker College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Clark Atlanta University', 'psych' => ['BS', 'BA'], 'neuro' => ['BS', 'BS+MS'], 'ol' => false],
            ['name' => 'Clayton State University', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'College of Coastal Georgia', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Columbus State University', 'psych' => ['BS', 'MS', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Covenant College', 'psych' => ['BS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Dalton State College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'East Georgia State College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Emory University', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'Fort Valley State University', 'psych' => ['BA', 'BA (OL)', 'MS', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Emmanuel University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Georgia College and State Univ', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Georgia Gwinnett College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Georgia Institute of Technology', 'psych' => ['BS', 'MS', 'PhD'], 'neuro' => ['BS', 'PhD'], 'ol' => false],
            ['name' => 'Georgia Southern University', 'psych' => ['BS', 'MS', 'MEd', 'PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Georgia Southwestern State Univ', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Georgia State University', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'Gordon State College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Kennesaw State University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'LaGrange College', 'psych' => ['BA', 'BA+MA', 'MA', 'MA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Life University', 'psych' => ['BS', 'BS (OL)', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Mercer University', 'psych' => ['BS', 'BA', 'MS', 'PsyD', 'MPH+PsyD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Middle Georgia State University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Morehouse College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],

            ['name' => 'Morehouse School of Medicine', 'psych' => [], 'neuro' => ['BS+MS', 'MS'], 'ol' => false],
            ['name' => 'Oglethorpe University', 'psych' => ['BS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Paine College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Piedmont University', 'psych' => ['BA', 'MS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Point University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Reinhardt University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Savannah College of Art & Design', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Savannah State University', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Shorter University', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'South Georgia State College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'South University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Spelman College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Thomas University', 'psych' => ['BA', 'MS (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Toccoa Falls College', 'psych' => ['BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Truett McConnell University', 'psych' => ['BS', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Georgia', 'psych' => ['BS', 'MS', 'EdS', 'PhD'], 'neuro' => ['PhD'], 'ol' => false],
            ['name' => 'University of North Georgia', 'psych' => ['BS', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of West Georgia', 'psych' => ['BS', 'MA', 'MEd', 'PhD', 'EdD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Valdosta State University', 'psych' => ['BS', 'BA', 'MS', 'MEd', 'EdS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Wesleyan College', 'psych' => ['BS', 'BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Young Harris College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'Emory University School of Medicine Psychiatry Residency Program', 'loc' => 'Atlanta', 'deg' => ['MD-DO']],
            ['name' => 'Morehouse School of Medicine', 'loc' => 'Atlanta', 'deg' => ['MD-DO']],
            ['name' => 'Medical College of Georgia', 'loc' => 'Augusta', 'deg' => ['MD-DO']],
            ['name' => 'Piedmont Macon Medical Center', 'loc' => 'Macon', 'deg' => ['MD-DO']],
            ['name' => 'Gateway Behavioral Health Comm Service Board', 'loc' => 'Savannah', 'deg' => ['MD-DO']],
            ['name' => 'Northeast Georgia Medical Center', 'loc' => 'Gainesville', 'deg' => ['MD-DO']],
            ['name' => 'South GA Med Education and Research Consortium', 'loc' => 'Moultrie', 'deg' => ['MD-DO']],
            ['name' => 'St. Francis-Emory Healthcare', 'loc' => 'Columbus', 'deg' => ['MD-DO']],
            ['name' => 'Emory University School of Medicine', 'loc' => 'Atlanta', 'deg' => ['MD-PhD']],
            ['name' => 'Morehouse School of Medicine', 'loc' => 'Atlanta', 'deg' => ['MD-PhD']],
            ['name' => 'Medical College of Georgia at Augusta University', 'loc' => 'Augusta', 'deg' => ['MD-PhD']],
        ];

        foreach ($residencies as $res) {
            AcademiaMedicalResidency::create([
                'state_id' => $state->id,
                'program_name' => $res['name'],
                'location' => $res['loc'],
                'degree_types' => $res['deg'],
            ]);
        }

        $stateInstitutions = [
            ['name' => 'Anchor Hospital', 'loc' => 'Atlanta'],
            ['name' => 'Bradley Center of Saint Francis', 'loc' => 'Columbus'],
            ['name' => 'Central State Hospital', 'loc' => 'Milledgeville'],
            ['name' => 'Coastal Harbor Treatment Center', 'loc' => 'Savannah'],
            ['name' => 'Coliseum Psychiatric Hospital', 'loc' => 'Macon'],
            ['name' => 'Community Mental Health Center of East Central Georgia', 'loc' => 'Augusta'],
            ['name' => 'Devereux Georgia', 'loc' => 'Kennesaw'],
            ['name' => 'Georgia Regional Hospital at Atlanta', 'loc' => 'Decatur'],
            ['name' => 'Greenleaf Behavioral Health Hospital', 'loc' => 'Valdosta'],
            ['name' => 'Lakeview Behavioral Health Hospital', 'loc' => 'Norcross'],
            ['name' => 'Laurel Heights Hospital', 'loc' => 'Atlanta'],
            ['name' => 'Macon Behavioral Health Treatment Center', 'loc' => 'Macon'],
            ['name' => 'Murphy-Harpst Children\'s Centers', 'loc' => 'Cedartown'],
            ['name' => 'Peachford Behavioral Health System', 'loc' => 'Atlanta'],
            ['name' => 'Saint Simons By-The-Sea Hospital', 'loc' => 'Saint Simons Island'],
            ['name' => 'Ridgeview Institute', 'loc' => 'Smyrna'],
            ['name' => 'River Edge Behavioral Health Center', 'loc' => 'Macon'],
            ['name' => 'Riverwoods Behavioral Health System', 'loc' => 'Riverdale'],
            ['name' => 'Turning Point Hospital', 'loc' => 'Moultrie'],
            ['name' => 'Willingway Hospital', 'loc' => 'Statesboro'],
        ];

        foreach ($stateInstitutions as $inst) {
            AcademiaFacility::create([
                'state_id' => $state->id,
                'name' => $inst['name'],
                'location' => $inst['loc'],
                'type' => 'state_institution',
            ]);
        }

        AcademiaFacility::create([
            'state_id' => $state->id,
            'name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine',
            'location' => 'Birmingham',
            'type' => 'university_hospital',
        ]);

        $vaFacilities = [
            ['name' => 'Carl Vinson Veterans\' Administration Medical Center', 'loc' => 'Dublin'],
            ['name' => 'Charlie Norwood Department of Veterans Affairs Medical Center', 'loc' => 'Augusta'],
            ['name' => 'Joseph Maxwell Cleland Atlanta VA Medical Center', 'loc' => 'Decatur'],
        ];

        foreach ($vaFacilities as $va) {
            AcademiaFacility::create([
                'state_id' => $state->id,
                'name' => $va['name'],
                'location' => $va['loc'],
                'type' => 'va_facility',
            ]);
        }
    }
}
