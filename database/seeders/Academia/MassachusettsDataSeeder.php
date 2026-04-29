<?php

namespace Database\Seeders\Academia;

use Illuminate\Database\Seeder;
use App\Models\State;
use App\Models\AcademiaUniversity;
use App\Models\AcademiaMedicalResidency;
use App\Models\AcademiaFacility;

class MassachusettsDataSeeder extends Seeder
{
    public function run()
    {
        $state = State::updateOrCreate(
            ['slug' => 'massachusetts'],
            ['name' => 'Massachusetts', 'code' => 'MA']
        );

        $universities = [
            ['name' => 'American International College', 'psych' => ['BS', 'MS', 'MA', 'EdD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Amherst College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Anna Maria College', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Assumption University', 'psych' => ['BA', 'BA+MA'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'Babson College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Bard College at Simon\'s Rock', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Bay Path University', 'psych' => ['BA', 'MS (OL)', 'EdS'], 'neuro' => [], 'ol' => true],
            ['name' => 'Bay State College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Ben Franklin Cummings Inst of Tech', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Bentley University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Berklee College of Music', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Boston Architectural College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Boston College', 'psych' => ['BS', 'BA', 'BA+MA', 'MA', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Boston Grad School of Psychoanalysis', 'psych' => ['MA', 'MA+PsyaD', 'PsyaD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Boston University', 'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'], 'neuro' => ['BA', 'MA', 'EdM', 'PhD'], 'ol' => false],
            ['name' => 'Brandeis University', 'psych' => ['BA', 'MS', 'MA', 'PhD'], 'neuro' => ['BS', 'MS', 'PhD'], 'ol' => false],
            ['name' => 'Bridgewater State University', 'psych' => ['BS', 'MA', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Cambridge College', 'psych' => ['BA', 'BA (OL)', 'MEd', 'MEd (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Clark University', 'psych' => ['BA', 'MEd', 'EdS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'College of Our Lady of the Elms', 'psych' => ['BA', 'MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'College of the Holy Cross', 'psych' => ['BS', 'BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Conway School of Landscape Design', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Curry College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Dean College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Emerson College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Emmanuel College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Endicott College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Fisher College', 'psych' => ['BA', 'BA (OL)'], 'neuro' => [], 'ol' => true],
            ['name' => 'Franklin W. Olin College of Engineering', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Fitchburg State University', 'psych' => ['BS', 'BA', 'MS', 'EdS'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Framingham State University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Gordon College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Hampshire College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Harvard University', 'psych' => ['AB', 'ALB', 'ALM', 'PhD'], 'neuro' => ['AB', 'PhD'], 'ol' => false],

            ['name' => 'Hult International Business School', 'psych' => ['B-PEP'], 'neuro' => [], 'ol' => false],
            ['name' => 'Hebrew College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Labouré College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Lasell College', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Lesley University', 'psych' => ['BA', 'BA (OL)', 'MA', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Massachusetts College of Liberal Arts', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Massachusetts College of Art and Design', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Massachusetts Institute of Technology', 'psych' => ['BS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Massachusetts Maritime Academy', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Massachusetts School of Law', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'MCPHS University', 'psych' => ['BS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Merrimack College', 'psych' => ['BS', 'BA (OL)', 'MS', 'MEd'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'MGH Institute of Health Professions', 'psych' => ['MS'], 'neuro' => [], 'ol' => false],
            ['name' => 'Montserrat College of Art', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Mount Holyoke College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Mount Vernon Nazarene University', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'New England College of Optometry', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'New England Conservatory', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'New England Law Boston', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Nichols College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northeastern University at Burlington', 'psych' => ['BS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Northpoint Bible College', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Regis College', 'psych' => ['BA', 'MS', 'MS (OL)', 'MA'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'Salem State University', 'psych' => ['BS', 'BA', 'BS+MS', 'MS', 'MEd'], 'neuro' => [], 'ol' => false],
            ['name' => 'Simmons University', 'psych' => ['BA', 'BA (OL)', 'MS', 'MS (OL)', 'EdS', 'PhD', 'PhD (OL)'], 'neuro' => ['BS'], 'ol' => true],
            ['name' => 'Stonehill College', 'psych' => ['BA'], 'neuro' => ['BS', 'BA'], 'ol' => false],
            ['name' => 'Springfield College', 'psych' => ['BS', 'MS', 'MEd', 'MEd (OL)', 'PsyD', 'PhD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Smith College', 'psych' => ['BS', 'BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Suffolk University', 'psych' => ['BS', 'BA', 'BA+MS', 'MS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'Tufts University', 'psych' => ['BS', 'BA', 'MA', 'EdS', 'PhD'], 'neuro' => ['PhD'], 'ol' => false],
            ['name' => 'University of Massachusetts- Amherst', 'psych' => ['BS', 'BA', 'MS', 'MA', 'PhD'], 'neuro' => ['MS', 'PhD'], 'ol' => false],
            ['name' => 'University of Massachusetts- Boston', 'psych' => ['BS', 'BA', 'MS', 'MEd', 'EdS', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Massachusetts- Chan', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Massachusetts- Dartmouth', 'psych' => ['BA', 'MS', 'MA'], 'neuro' => [], 'ol' => false],

            ['name' => 'University of Massachusetts- Lowell', 'psych' => ['BA', 'MA', 'PhD'], 'neuro' => [], 'ol' => false],
            ['name' => 'University of Massachusetts- Global', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Wellesley College', 'psych' => ['BA'], 'neuro' => ['BA'], 'ol' => false],
            ['name' => 'Wentworth Institute of Technology', 'psych' => [], 'neuro' => [], 'ol' => false],
            ['name' => 'Western New England University', 'psych' => ['BS', 'BA', 'MS', 'PhD'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'Westfield State University', 'psych' => ['BA', 'MA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Wheaton College', 'psych' => ['BA'], 'neuro' => ['BS'], 'ol' => false],
            ['name' => 'William James College', 'psych' => ['BS (OL)', 'MA', 'MA (OL)', 'PsyD'], 'neuro' => [], 'ol' => true],
            ['name' => 'Williams College', 'psych' => ['BA'], 'neuro' => [], 'ol' => false],
            ['name' => 'Worcester Polytechnic Institute', 'psych' => ['BS'], 'neuro' => ['BS+MS', 'MS'], 'ol' => false],
            ['name' => 'Worcester State University', 'psych' => ['BS', 'EdS'], 'neuro' => [], 'ol' => false],
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
            ['name' => 'Mass General Brigham/Brigham and Women\'s Hospital/Harvard Medical School', 'loc' => 'Boston', 'deg' => ['MD-DO']],
            ['name' => 'Boston Medical Center Brighton', 'loc' => 'Boston', 'deg' => ['MD-DO']],
            ['name' => 'Cambridge Health Alliance Psychiatry Residency Program', 'loc' => 'Cambridge', 'deg' => ['MD-DO']],
            ['name' => 'Boston University Medical Center', 'loc' => 'Boston', 'deg' => ['MD-DO']],
            ['name' => 'Tufts Medical Center Psychiatry Residency Program', 'loc' => 'Boston', 'deg' => ['MD-DO']],
            ['name' => 'UMASS Chan Medical School', 'loc' => 'Worcester', 'deg' => ['MD-DO']],
            ['name' => 'Boston VA Healthcare System (Brockton-West Roxbury)/Harvard Medical School', 'loc' => 'Brockton', 'deg' => ['MD-DO']],
            ['name' => 'Mass Gen Brigham/Mass Gen Hospital/McLean Hospital Psychiatry Residency', 'loc' => 'Boston', 'deg' => ['MD-DO']],
            ['name' => 'UMASS Chan-Baystate', 'loc' => 'Springfield', 'deg' => ['MD-DO']],
            ['name' => 'Berkshire Medical Center', 'loc' => 'Pittsfield', 'deg' => ['MD-DO']],
            ['name' => 'Beth Israel Deaconess Medical Center Psychiatry Residency Program', 'loc' => 'Boston', 'deg' => ['MD-DO']],
            ['name' => 'Lahey Clinic Psychiatry Residency Program', 'loc' => 'Burlington', 'deg' => ['MD-DO']],
            ['name' => 'Boston University School of Medicine', 'loc' => 'Boston', 'deg' => ['MD-PhD']],
            ['name' => 'Harvard Medical School', 'loc' => 'Boston', 'deg' => ['MD-PhD']],
            ['name' => 'Tufts University School of Medicine', 'loc' => 'Boston', 'deg' => ['MD-PhD']],
            ['name' => 'University of Massachusetts Medical School', 'loc' => 'Worcester', 'deg' => ['MD-PhD']],
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
            ['name' => 'AdCare Hospital', 'loc' => 'Worcester', 'type' => 'state_institution'],
            ['name' => 'Arbour Hospital', 'loc' => 'Boston', 'type' => 'state_institution'],
            ['name' => 'Austen Riggs Center', 'loc' => 'Stockbridge', 'type' => 'state_institution'],
            ['name' => 'Bridgewater State Hospital', 'loc' => 'Bridgewater', 'type' => 'state_institution'],
            ['name' => 'Fuller Hospital', 'loc' => 'South Attleboro', 'type' => 'state_institution'],
            ['name' => 'Human Resource Institute Hospital', 'loc' => 'Brookline', 'type' => 'state_institution'],
            ['name' => 'McLean Hospital', 'loc' => 'Belmont', 'type' => 'state_institution'],
            ['name' => 'Northampton VA Medical Center', 'loc' => 'Northampton', 'type' => 'state_institution'],
            ['name' => 'Pembroke Hospital', 'loc' => 'Pembroke', 'type' => 'state_institution'],
            ['name' => 'Providence Behavioral Health Hospital', 'loc' => 'Holyoke', 'type' => 'state_institution'],
            ['name' => 'Southcoast Behavioral Health Hospital', 'loc' => 'Dartmouth', 'type' => 'state_institution'],
            ['name' => 'The Trauma Center', 'loc' => 'Boston', 'type' => 'state_institution'],
            ['name' => 'Westwood Lodge Hospital', 'loc' => 'Westwood', 'type' => 'state_institution'],

            // University Hospital (Placeholder from client data)
            ['name' => 'Univ of Alabama at Birmingham Center for Psychiatric Medicine', 'loc' => 'Birmingham', 'type' => 'university_hospital'],

            // VA Facilities
            ['name' => 'Brockton VA Medical Center', 'loc' => 'Brockton', 'type' => 'va_facility'],
            ['name' => 'Edith Nourse Rogers Memorial Veterans\' Hospital', 'loc' => 'Bedford', 'type' => 'va_facility'],
            ['name' => 'Jamaica Plain VA Medical Center', 'loc' => 'Boston', 'type' => 'va_facility'],
            ['name' => 'Northampton VA Medical Center', 'loc' => 'Leeds', 'type' => 'va_facility'],
            ['name' => 'White River Junction VA Medical Center', 'loc' => 'White River Junction, VT', 'type' => 'va_facility'],
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
