<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class NewInstitutionSeeder extends Seeder
{
    public function run(): void
    {
        $rawList = "
            Alabama A&M University
            Auburn University
            Auburn University- Montgomery
            Jacksonville State University
            Samford University
            Spring Hill College
            Talladega College
            Tuskegee University
            University of Alabama Birmingham
            University of Alabama Huntsville
            University of Alabama Tuscaloosa
            University of Mobile
            University of Montevallo
            University of South Alabama
            University of West Alabama
            Alaska Bible College
            Alaska Pacific University
            University of Alaska Anchorage
            University of Alaska Fairbanks
            University of Alaska Southeast
            Arkansas Baptist College
            Arkansas State University
            Arkansas Tech University
            Central Baptist College
            Crowley's Ridge College
            Ecclesia College
            Harding University
            Henderson State University
            Hendrix College
            John Brown University
            Lyon College
            Ouachita Baptist University
            Philander Smith College
            Southern Arkansas University
            University of Arkansas
            University of Central Arkansas
            University of the Ozarks
            Williams Baptist University
            Arizona Christian University
            Arizona State University- Phoenix
            Arizona State University- Polytechnic
            Arizona State University- Tempe
            Arizona State University- West Valley
            Embry-Riddle Aeronautical University
            Grand Canyon University
            International Baptist College
            Midwestern University
            Northern Arizona University
            Prescott College
            The School of Architecture
            University of Advanced Technology
            University of Arizona
            Adams State University
            Colorado Christian University
            Colorado College
            Colorado Mesa University
            Colorado School of Mines
            Colorado State University
            Colorado State University Pueblo
            Colorado Technical University
            Fort Lewis College
            Metropolitan State University
            Naropa University
            Nazarene Bible College
            Regis University
            Rocky Mountain College of Art + Design
            Rocky Vista University
            United States Air Force Academy
            University of Colorado Boulder
            University of Colorado Colorado Springs
            University of Colorado Denver
            University of Colorado Anschutz
            University of Denver
            University of Northern Colorado
            Western Colorado University
            Albertus Magnus College
            Central Connecticut State University
            Charter Oak State College
            Connecticut College
            Eastern Connecticut State University
            Goodwin University
            Holy Apostles College and Seminary
            Fairfield University
            Mitchell College
            Post University
            Quinnipiac University
            Sacred Heart University
            Southern Connecticut State University
            Trinity College
            United States Coast Guard Academy
            University of Bridgeport
            University of Connecticut
            University of Hartford
            University of New Haven
            University of Saint Joseph
            Wesleyan University
            Western Connecticut State University
            Yale University
            Delaware State University
            Goldey-Beacom College
            University of Delaware
            Wilmington University
            Abraham Baldwin Agricultural College
            Agnes Scott College
            Augusta University
            Albany State University
            Atlanta Metropolitan State College
            Berry College
            Beulah Heights University
            Brenau University
            Brewton-Parker College
            Clark Atlanta University
            Clayton State University
            College of Coastal Georgia
            Columbus State University
            Covenant College
            Dalton State College
            East Georgia State College
            Emory University
            Fort Valley State University
            Emmanuel University
            Georgia College & State University
            Georgia Gwinnett College
            Georgia Institute of Technology
            Georgia Southern University
            Georgia Southwestern State Univ
            Georgia State University
            Gordon State College
            Kennesaw State University
            LaGrange College
            Life University
            Mercer University
            Middle Georgia State University
            Morehouse College
            Morehouse School of Medicine
            Oglethorpe University
            Paine College
            Piedmont University
            Point University
            Reinhardt University
            Savannah College of Art & Design
            Savannah State University
            Shorter University
            South Georgia State College
            South University
            Spelman College
            Thomas University
            Toccoa Falls College
            Truett McConnell University
            University of Georgia
            University of North Georgia
            University of West Georgia
            Valdosta State University
            Wesleyan College
            Young Harris College
            Brigham Young University – Hawaii
            Chaminade University
            Hawaii Pacific University
            University of Hawaii at Hilo
            University of Hawaii at Manoa
            University of Hawaii at West Oahu
            Boise Bible College
            Boise State University
            Brigham Young University – Idaho
            College of Idaho
            Idaho State University
            Lewis-Clark State College
            Northwest Nazarene University
            University of Idaho
            Anderson University
            Ball State University
            Bethel University
            Butler University
            Calumet College of St. Joseph
            DePauw University
            Earlham College
            Franklin College
            Goshen College
            Grace College & Seminary
            Hanover College
            Holy Cross College
            Huntington University
            Indiana Institute of Technology
            Indiana State University
            Indiana University – Bloomington
            Indiana University – Columbus
            Indiana University – East
            Indiana University – Fort Wayne
            Indiana University – Indianapolis
            Indiana University – Kokomo
            Indiana University – Northwest
            Indiana University – South Bend
            Indiana University – Southeast
            Indiana Wesleyan University
            Manchester University
            Marian University
            Martin University
            Oakland City University
            Purdue University – Fort Wayne
            Purdue University – Northwest
            Purdue University – West Lafayette
            Rose-Hulman Institute of Technology
            Saint Mary-of-the-Woods College
            Saint Mary's College
            Taylor University
            Trine University
            University of Evansville
            University of Indianapolis
            University of Notre Dame
            University of Saint Francis – Fort Wayne
            University of Southern Indiana
            Valparaiso University
            Vincennes University
            Wabash College
            Allen College
            Briar Cliff University
            Buena Vista University
            Central College
            Clarke University
            Coe College
            Cornell College
            Des Moines University
            Dordt College
            Drake University
            Emmaus University
            Faith Baptist Bible Coll & Theological Sem
            Graceland University
            Grand View University
            Grinnell College
            Iowa State University
            Loras College
            Luther College
            Maharishi International University
            Mercy College of Health Sciences
            Morningside University
            Mount Mercy University
            Northwestern College
            Simpson College
            St. Ambrose University
            University of Dubuque
            University of Iowa
            University of Northern Iowa
            Upper Iowa University
            Waldorf University
            Wartburg College
            William Penn University
            Baker University
            Barclay College
            Benedictine College
            Bethany College
            Bethel College
            Central Christian College of Kansas
            Cleveland University-Kansas City
            Donnelly College
            Fort Hays State University
            Friends University
            Kansas State University
            Kansas Wesleyan University
            Manhattan Christian College
            McPherson College
            MidAmerica Nazarene University
            Newman University
            Ottawa University
            Pittsburg State University
            Southwestern College
            Sterling College
            Tabor College
            University of Kansas
            University of Saint Mary
            Washburn University
            Wichita State University
            Alice Lloyd College
            Asbury University
            Bellarmine University
            Berea College
            Brescia University
            Campbellsville University
            Centre College
            Clear Creek Baptist Bible College
            Eastern Kentucky University
            Georgetown College
            Kentucky Christian University
            Kentucky State University
            Kentucky Wesleyan College
            Lindsey Wilson College
            Midway University
            Morehead State University
            Murray State University
            Northern Kentucky University
            Spalding University
            Sullivan University
            Thomas More University
            Transylvania University
            Union College
            University of the Cumberlands
            University of Kentucky
            University of Louisville
            University of Pikeville
            Western Kentucky University
            Bates College
            Bowdoin College
            Colby College
            College of the Atlantic
            Husson University
            Maine College of Art
            Maine Maritime Academy
            Saint Joseph's College of Maine
            Thomas College
            University of Maine- Augusta
            University of Maine- Farmington
            University of Maine- Fort Kent
            University of Maine- Machias
            University of Maine- Orono
            University of Maine- Presque Isle
            University of New England
            University of Southern Maine
            Adrian College
            Albion College
            Alma College
            Andrews University
            Aquinas College
            Baker College
            Calvin University
            Central Michigan University
            Cleary University
            College for Creative Studies
            Concordia University – Ann Arbor
            Cornerstone University
            Cranbrook Academy of Art
            Davenport University
            Eastern Michigan University
            Ferris State University
            Grace Christian University
            Grand Valley State University
            Great Lakes Christian College
            Hillsdale College
            Hope College
            Kalamazoo College
            Kettering University
            Kuyper College
            Lake Superior State University
            Lawrence Technological University
            Madonna University
            Michigan School of Psychology
            Michigan State University
            Michigan Technological University
            Northern Michigan University
            Northwood University
            Oakland University
            Olivet College
            Rochester University
            Saginaw Valley State University
            Spring Arbor University
            University of Detroit Mercy
            University of Michigan – Ann Arbor
            University of Michigan – Dearborn
            University of Michigan – Flint
            Walsh College
            Wayne State University
            Western Michigan University
            Adler Graduate School
            Augsburg University
            Bemidji State University
            Bethany Lutheran College
            Bethel University
            Carleton College
            College of St Benedict; St John's University
            The College of St. Scholastica
            Concordia College
            Concordia University at St. Paul
            Crown College
            Dunwoody College of Technology
            Gustavus Adolphus College
            Hamline University
            Macalester College
            Martin Luther College
            Mayo Clinic College of Med & Science
            Metropolitan State University
            Minneapolis College of Art and Design
            Minnesota State University
            Mitchell Hamline School of Law
            North Central University
            Northwestern Health Sciences University
            Oak Hills Christian College
            Rasmussen University
            Saint Mary's University of Minnesota
            Southwest Minnesota State University
            St. Catherine University
            St. Cloud State University
            St. Olaf College
            University of Minnesota – Crookston
            University of Minnesota – Duluth
            University of Minnesota – Morris
            University of Minnesota – Rochester
            University of Minnesota – Twin Cities
            University of Northwestern at St. Paul
            University of St. Thomas
            Winona State University
            Alcorn State University
            Belhaven University
            Blue Mountain Christian University
            Delta State University
            Jackson State University
            Millsaps College
            Mississippi College
            Mississippi State University
            Mississippi University for Women
            Mississippi Valley State University
            Rust College
            Southeastern Baptist College
            Tougaloo College
            University of Mississippi
            University of Mississippi Medical Center
            The University of Southern Mississippi
            William Carey University
            Carroll College
            Montana State University - Billings
            Montana State University - Bozeman
            Montana State University - Northern
            Montana Technological University
            Rocky Mountain College
            University of Montana
            University of Montana Western
            University of Providence
            Bellevue University
            Bryan College of Health Sciences
            Chadron State College
            Clarkson College
            College of Saint Mary
            Concordia University
            Creighton University
            Doane University
            Hastings College
            Midland University
            Nebraska Wesleyan University
            Peru State College
            Union College
            University of Nebraska Lincoln
            University of Nebraska at Kearney
            University of Nebraska Medical Center
            University of Nebraska at Omaha
            Wayne State College
            York University
            College of Southern Nevada
            Great Basin College
            Nevada State University
            Roseman University of Health Sciences
            University of Nevada, Las Vegas
            University of Nevada, Reno
            Western Nevada College
            Antioch University New England
            Colby-Sawyer College
            Dartmouth College
            Franklin Pierce University
            University of New Hampshire- Durham
            University of New Hampshire- Manchester
            Keene State College
            New England College
            Plymouth State University
            Rivier University
            Saint Anselm College
            Southern New Hampshire University
            Thomas More College of Liberal Arts
            Eastern New Mexico University
            New Mexico Highlands University
            New Mexico Institute of Mining and Technology
            New Mexico State University
            Northern New Mexico College
            Southwestern College
            St. John's College at Santa Fe
            University of New Mexico
            University of the Southwest
            Western New Mexico University
            Appalachian State University
            Barton College
            Belmont Abbey College
            Bennett College
            Brevard College
            Campbell University
            Cabarrus College of Health Sciences
            Carolina University
            Catawba College
            Chowan University
            Davidson College
            Duke University
            East Carolina University
            Elizabeth City State University
            Elon University
            Fayetteville State University
            Gardner-Webb University
            Greensboro College
            Guilford College
            High Point University
            Johnson C. Smith University
            Lees-McRae College
            Lenoir-Rhyne University
            Livingstone College
            Mars Hill University
            Meredith College
            Methodist University
            Mid-Atlantic Christian University
            Montreat College
            North Carolina Central University
            North Carolina State University
            North Carolina A&T State University
            North Carolina Wesleyan University
            Queens University of Charlotte
            Salem College
            Shaw University
            St. Andrew's University
            St. Augustine's University
            University of Mount Olive
            University of North Carolina – Asheville
            University of North Carolina – Chapel Hill
            University of North Carolina – Charlotte
            University of North Carolina – Greensboro
            University of North Carolina – Pembroke
            University of North Carolina – Wilmington
            Pfeiffer University
            Wake Forest University
            Warren Wilson College
            Western Carolina University
            William Peace University
            Wingate University
            Winston-Salem State University
            Bismarck State College
            Dickinson State University
            Mayville State University
            Minot State University
            North Dakota State University
            Trinity Bible College
            University of Jamestown
            University of Mary
            University of North Dakota
            Valley City State University
            Art Academy of Cincinnati
            Ashland University
            Baldwin Wallace University
            Bluffton University
            Bowling Green State University
            Capital University
            Case Western Reserve University
            Cedarville University
            Central State University
            Cleveland Institute of Art
            Cleveland Institute of Music
            Cleveland State University
            The College of Wooster
            Columbus College of Art and Design
            Defiance College
            Denison University
            Franciscan University of Steubenville
            Franklin University
            God's Bible School and College
            Good Samaritan Coll Nursing & Health Sci
            Heidelberg University
            Hiram College
            John Carroll University
            Kent State University
            Lake Erie College
            Lourdes University
            Malone University
            Marietta College
            Mercy College of Ohio
            Mount Carmel College of Nursing
            Miami University
            Mount St. Joseph University
            Mount Vernon Nazarene University
            Muskingum University
            Northeast Ohio Medical University
            Oberlin College
            Ohio Christian University
            Ohio Dominican University
            Ohio Northern University
            Ohio State University
            Ohio University
            Ohio University- Chillicothe
            Ohio University- Southern
            Ohio Wesleyan University
            Otterbein University
            Shawnee State University
            Tiffin University
            Tri-State Bible College
            University of Akron
            University of Cincinnati
            University of Dayton
            University of Findlay
            University of Mount Union
            University of Northwestern Ohio
            University of Rio Grande
            University of Toledo
            Ursuline College
            Walsh University
            Wilberforce University
            Wilmington College
            Wittenberg University
            Wright State University
            Xavier University
            Youngstown State University
            Cameron University
            East Central University
            Langston University- Langston
            Langston University- Oklahoma City
            Langston University- Tulsa
            Mid-America Christian University
            Northeastern State University
            Northwestern Oklahoma State University
            Oklahoma Baptist University
            Oklahoma Christian University
            Oklahoma City University
            Oklahoma Panhandle State University
            Oklahoma State University
            Oklahoma Wesleyan University
            Oral Roberts University
            Rogers State University
            Southeastern Oklahoma State University
            Southern Nazarene University
            Southwestern Christian University
            Southwestern Oklahoma State University
            University of Central Oklahoma
            University of Oklahoma
            Univ of Oklahoma Health Sciences Center
            University of Science & Arts of Oklahoma
            University of Tulsa
            Bushnell University
            Corban University
            Eastern Oregon University
            George Fox University
            Lewis & Clark College
            Linfield University
            Multnomah University
            New Hope Christian College
            Oregon Health & Science University
            Oregon Institute of Technology
            Oregon State University
            Pacific University
            Portland State University
            Reed College
            Southern Oregon University
            University of Oregon
            University of Portland
            University of Western States
            Warner Pacific University
            Western Oregon University
            Willamette University
            Brown University
            Bryant University
            Johnson & Wales University- Charlotte
            Johnson & Wales University- Providence
            New England Institute of Technology
            Providence College
            Rhode Island College
            Roger Williams University
            Salve Regina University
            University of Rhode Island
            Allen University
            Anderson University
            Benedict College
            Bob Jones University
            Charleston Southern University
            The Citadel
            Claflin University
            Clemson University
            Coastal Carolina University
            Coker University
            College of Charleston
            Columbia College
            Columbia International University
            Converse University
            Erskine College
            Francis Marion University
            Furman University
            Lander University
            Medical University of South Carolina
            Morris College
            Newberry College
            North Greenville University
            Presbyterian College
            South Carolina State University
            Southern Wesleyan University
            University of South Carolina - Aiken
            University of South Carolina - Beaufort
            University of South Carolina- Columbia
            University of South Carolina - Upstate
            Voorhees College
            Winthrop University
            Wofford College
            Augustana University
            Black Hills State University
            Dakota State University
            Dakota Wesleyan University
            Mount Marty University
            Northern State University
            South Dakota School of Mines & Technology
            South Dakota State University
            University of Sioux Falls
            University of South Dakota
            American Baptist College
            Aquinas College
            Baptist Health Sciences University
            Austin Peay State University
            Belmont University
            Bethel University
            Bryan College
            Carson-Newman University
            Christian Brothers University
            Cumberland University
            East Tennessee State University
            Fisk University
            Freed-Hardeman University
            King University
            Lane College
            Lee University
            LeMoyne-Owen College
            Lincoln Memorial University
            Lipscomb University
            Maryville College
            Meharry Medical College
            Middle Tennessee State University
            Milligan University
            Richmont Graduate University
            Rhodes College
            Sewanee- The University of the South
            South College
            Southern Adventist University
            Tennessee State University
            Tennessee Technological University
            Tennessee Wesleyan University
            Trevecca Nazarene University
            Tusculum University
            Union University
            University of Memphis
            University of Tennessee – Chattanooga
            University of Tennessee – Knoxville
            University of Tennessee – Martin
            University of Tennessee – Southern
            Univ of Tennessee Health Science Center
            Vanderbilt University
            Welch College
            Williamson College
            Brigham Young University
            Southern Utah University
            University of Utah
            Utah State University
            Utah Tech University
            Utah Valley University
            Weber State University
            Westminster College
            Bennington College
            Champlain College
            Landmark College
            Middlebury College
            Norwich University
            Saint Michael's College
            SIT Graduate Institute
            Sterling College
            University of Vermont
            Vermont State University
            Antioch University Seattle
            Bellevue College
            Bastyr University
            Centralia College
            Central Washington University
            City University of Seattle
            Columbia Basin College
            Cornish College of the Arts
            Eastern Washington University
            The Evergreen State College
            Gonzaga University
            Heritage University
            Lake Washington Institute of Technology
            Northwest University
            Pacific Lutheran University
            Seattle Central College
            Seattle Pacific University
            Seattle University
            St. Martin's University
            University of Puget Sound
            University of Washington
            Walla Walla University
            Washington State University
            Western Washington University
            Whitman College
            Whitworth University
            Appalachian Bible College
            Bethany College
            Bluefield State University
            Concord University
            Davis & Elkins College
            Fairmont State University
            Glenville State University
            Marshall University
            Salem University
            Shepherd University
            University of Charleston
            West Liberty University
            West Virginia School of Osteopathic Medicine
            West Virginia State University
            West Virginia University
            West Virginia Institute of Technology
            West Virginia Wesleyan College
            Wheeling University
            Alverno College
            Bellin College
            Beloit College
            Carroll University
            Carthage College
            Concordia University Wisconsin
            Edgewood College
            Lakeland University
            Lawrence University
            Maranatha Baptist University
            Marian University
            Medical College of Wisconsin
            Milwaukee Institute of Art & Design
            Marquette University
            Milwaukee School of Engineering
            Mount Mary University
            Ripon College
            St. Norbert College
            University of Wisconsin – Eau Claire
            University of Wisconsin – Green Bay
            University of Wisconsin – La Crosse
            University of Wisconsin – Madison
            University of Wisconsin – Milwaukee
            University of Wisconsin – Oshkosh
            University of Wisconsin – Parkside
            University of Wisconsin – Platteville
            University of Wisconsin – River Falls
            University of Wisconsin – Stevens Point
            University of Wisconsin – Stout
            University of Wisconsin – Superior
            University of Wisconsin – Whitewater
            Viterbo University
            Wisconsin Lutheran College
            WI School of Professional Psychology
            University of Wyoming
            Adler University
            Augustana College
            Aurora University
            Benedictine University
            Blackburn College
            Blessing-Rieman College of Nursing & Health Sciences
            Bradley University
            Chamberlain University
            Chicago State University
            Columbia College Chicago
            Concordia University Chicago
            DePaul University
            Dominican University
            Eastern Illinois University
            East-West University
            Elmhurst College
            Eureka College
            Governors State University
            Greenville University
            Illinois College
            Illinois Institute of Technology
            Illinois State University
            Illinois Wesleyan University
            Judson University
            Knox College
            Lake Forest College
            Lewis University
            Loyola University
            McKendree University
            Methodist College
            Midwestern University
            Millikin University
            Monmouth College
            Moody Bible Institute
            National Louis Univ at Chicago
            National University of Health Sciences
            North Central College
            North Park University
            Northeastern Illinois University
            Northern Illinois University
            Northwestern University
            Olivet Nazarene University
            Principia College
            Quincy University
            Rosalind Franklin University of Medicine & Science
            Rockford University
            Roosevelt University- Chicago
            Roosevelt University- Schaumburg
            Saint Augustine College
            Saint Francis Medical Center College of Nursing
            Saint Xavier University
            School of the Art Institute Chicago
            Southern Illinois University
            Trinity Christian College
            Trinity International University
            University of Chicago
            University of Illinois Chicago
            University of Illinois Urbana-Champaign
            University of Illinois Springfield
            University of St. Francis
            Western Illinois University
            Wheaton College
            Centenary College
            Dillard University
            Franciscan Missionaries of Our Lady Univ
            Grambling State University
            Louisiana Christian University
            Louisiana State University - Alexandria
            Louisiana State University - Baton Rouge
            Louisiana State University - Shreveport
            LSU Health Sciences Center - New Orleans
            LSU Health Sciences Center-Shreveport
            Louisiana Tech University
            Loyola University New Orleans
            McNeese State University
            Nicholls State University
            Northwestern State University
            Southeastern Louisiana University
            Southern University and A&M College
            Southern University Law Center
            Southern University at New Orleans
            Tulane University
            University of Holy Cross
            University of Louisiana - Lafayette
            University of New Orleans
            Xavier University of Louisiana
            Bowie State University
            Capitol Technology University
            Coppin State University
            Frostburg State University
            Goucher College
            Hood College
            Johns Hopkins University
            Loyola University Maryland
            Maryland Institute College of Art
            McDaniel College
            Morgan State University
            Mount St. Mary's University
            Notre Dame of Maryland University
            Salisbury University
            St. John's College
            St. Mary's College of Maryland
            Stevenson University
            Towson University
            United States Naval Academy
            University of Baltimore
            Univ of Maryland – Baltimore County
            Univ of Maryland – College Park
            Univ of Maryland – Eastern Shore
            Washington Adventist University
            Washington College
            A.T. Still University
            Avila University
            Calvary University
            Central Christian College of the Bible
            Central Methodist University
            College of the Ozarks
            Columbia College
            Cottey College
            Cox College
            Culver-Stockton College
            Drury University
            Evangel University
            Goldfarb School of Nursing at Barnes
            Hannibal-LaGrange University
            Harris-Stowe State University
            Kansas City Art Institute
            Kansas City University
            Lincoln University
            Lindenwood University
            Logan University
            Maryville University
            Mission University
            Missouri Baptist University
            Missouri Southern State University
            Missouri State University
            Missouri Univ of Science and Technology
            Missouri Valley College
            Missouri Western State University
            Northwest Missouri State University
            Ozark Christian College
            Park University
            Research College of Nursing
            Rockhurst University
            Saint Louis University
            Southeast Missouri State University
            Southwest Baptist University
            Stephens College
            Truman State University
            University of Central Missouri
            University of Health Sciences & Pharmacy in St. Louis
            University of Missouri- Columbia
            University of Missouri-Kansas City
            University of Missouri-St. Louis
            Washington University in St. Louis
            Webster University
            Westminster College
            William Jewell College
            William Woods University
            Bloomfield College
            Caldwell University
            Centenary University
            College of New Jersey
            Drew University
            Fairleigh Dickinson University
            Felician University
            Georgian Court University
            Kean University
            Monmouth University
            Montclair State University
            New Jersey City University
            New Jersey Institute of Technology
            Pillar College
            Princeton University
            Ramapo College of New Jersey
            Rider University
            Rowan University
            Rutgers University - Camden
            Rutgers University - Newark
            Rutgers University - New Brunswick
            Saint Elizabeth University
            Seton Hall University
            St. Peter's University
            Stevens Institute of Technology
            Stockton University
            Thomas Edison State University
            William Paterson University
            Averett University
            Bluefield University
            Bridgewater College
            Christopher Newport University
            College of William and Mary
            Eastern Mennonite University
            Eastern Virginia Medical School
            ECPI University
            Emory & Henry University
            Ferrum College
            George Mason University
            Hampden-Sydney College
            Hampton University
            Hollins University
            James Madison University
            Liberty University
            Longwood University
            Mary Baldwin University
            Marymount University
            Norfolk State University
            Old Dominion University
            Radford University
            Randolph College
            Randolph-Macon College
            Regent University
            Roanoke College
            Shenandoah University- Loudoun
            Shenandoah University- Winchester
            Southern Virginia University
            Sweet Briar College
            University of Lynchburg
            University of Mary Washington
            University of the Potomac
            University of Richmond
            University of Virginia
            UVA College at Wise
            Virginia Commonwealth University
            Virginia Military Institute
            Virginia Tech University
            Virginia State University
            Virginia Union University
            Virginia Wesleyan University
            Washington & Lee University
            Albright College
            Allegheny College
            Alvernia University
            American College of Financial Services
            Arcadia University
            Commonwealth University of Pennsylvania- Bloomsburg
            Bryn Athyn College
            Bryn Mawr College
            Bucknell University
            Cairn University
            Carlow University
            Carnegie Mellon University
            Cedar Crest College
            Central Penn College
            Chatham University
            Chestnut Hill College
            Cheyney University of Pennsylvania
            Curtis Institute of Music
            Delaware Valley University
            DeSales University
            Dickinson College
            Drexel University
            Duquesne University
            East Stroudsburg University
            Eastern University
            Elizabethtown College
            Franklin & Marshall College
            Gannon University
            Geisinger Commonwealth School of Medicine
            Geneva College
            Gettysburg College
            Gratz College
            Grove City College
            Gwynedd Mercy University
            Harrisburg University of Science & Technology
            Haverford College
            Holy Family University
            Immaculata University
            Indiana University of Pennsylvania
            Juniata College
            Keystone College
            King's College
            Kutztown University of Pennsylvania
            Lancaster Bible College
            La Roche College
            La Salle University
            Lafayette College
            Lebanon Valley College
            Lehigh University
            Lincoln University
            Lock Haven University
            Lycoming College
            Mansfield University of Pennsylvania
            Marywood University
            Mercyhurst University
            Messiah College
            Millersville University
            Misericordia University
            Moore College of Art and Design
            Moravian College
            Mount Aloysius College
            Muhlenberg College
            Neumann University
            PA College of Technology
            Penn State College of Medicine
            Pennsylvania State University- Behrend
            Pennsylvania State University- Harrisburg
            Pennsylvania State University- Hershey
            Pennsylvania State University- Great Valley
            Pennsylvania State University- University Park
            PennWest University- California
            PennWest University- Clarion
            PennWest University- Edinboro
            Point Park University
            Robert Morris University
            Rosemont College
            Saint Francis University
            Saint Joseph's University
            Saint Vincent College
            Salus University
            Seton Hill University
            Shippensburg University
            Slippery Rock University
            Susquehanna University
            Swarthmore College
            Temple University
            Thiel College
            Thomas Jefferson University
            University of Pennsylvania
            University of Pittsburgh
            University of Scranton
            University of Valley Forge
            Ursinus College
            Villanova University
            Washington & Jefferson College
            Waynesburg University
            West Chester University
            Westminster College
            Widener University
            Wilkes University
            Wilson College
            WON Institute of Grad Studies
            York College of Pennsylvania
            Adelphi University
            Albany Law School
            Albany Medical College
            Albany College of Pharmacy and Health Sciences
            Alfred State College
            Alfred University
            Bank Street College of Education
            Bard College
            Barnard College
            Baruch College
            Berkeley College
            Binghamton University
            Boricua College
            Brooklyn College
            Brooklyn Law School
            Bryant and Stratton College
            Buffalo State University
            Canisius College
            City College of New York
            Clarkson University
            Colgate University
            College of Staten Island
            The College of Westchester
            Columbia University
            Columbia University- Teacher's College
            Cooper Union for the Advancement of Science & Art
            Cornell University
            Culinary Institute of America
            CUNY School of Law
            CUNY Graduate Center
            CUNY York College
            Daemen University
            Davis College
            Dominican University
            D'Youville University
            Elmira College
            Empire State University
            Excelsior University
            Fashion Institute of Technology
            Farmingdale State College
            Five Towns College
            Fordham University
            Hamilton College
            Hartwick College
            Helene Fuld College of Nursing
            Hilbert College
            Hobart and William Smith Colleges
            Houghton University
            Hunter College
            Icahn School of Medicine at Mt Sinai
            Iona University
            Ithaca College
            John Jay College of Criminal Justice
            The Juilliard School
            Keuka College
            The King's College
            Le Moyne College
            Lehman College
            LIM College
            Long Island University- Brooklyn
            Long Island University- Post
            Manhattan College
            Manhattan School of Music
            Manhattanville College
            Maria College
            Marist University
            Marymount Manhattan College
            Medgar Evers College
            Mercy University
            Metropolitan College of New York
            Molloy College
            Monroe University
            State University of New York (SUNY) Morrisville
            Mount Saint Mary College
            Nazareth University
            The New School
            New York Academy of Art
            New York City College of Technology
            New York College of Podiatric Medicine
            New York Institute of Technology
            New York Law School
            New York Medical College
            New York School of Interior Design
            New York University
            Niagara University
            Pace University
            Paul Smith's College
            Plaza College
            Pratt Institute
            Queens College- City University of New York (CUNY)
            Relay Graduate School of Education
            Roberts Wesleyan University
            Rochester Institute of Technology
            The Rockefeller University
            Russell Sage College
            Sarah Lawrence College
            School of Visual Arts
            Siena College
            Skidmore College
            St. Bonaventure University
            St. Francis College
            St. John's University
            St. John Fisher College
            St. Joseph's University- Brooklyn
            St. Joseph's University- Long Island
            St. Lawrence University
            St. Thomas Aquinas College
            State University of New York (SUNY) Brockport
            State University of New York (SUNY) Canton
            State University of New York (SUNY) Cobleskill
            State University of New York (SUNY) Cortland
            State University of New York (SUNY) Delhi
            State University of New York (SUNY) Fredonia
            State University of New York (SUNY) Geneseo
            State University of New York (SUNY) Maritime College
            State University of New York (SUNY) Downstate Medical Center
            State University of New York (SUNY) New Paltz
            State University of New York (SUNY) Oneonta
            State University of New York (SUNY) Oswego
            State University of New York (SUNY) Plattsburgh
            State University of New York (SUNY) Polytechnic Institute
            State University of New York (SUNY) Potsdam
            State University of New York (SUNY) Purchase
            SUNY Coll of Environmental Science & Forestry
            SUNY College at Old Westbury
            SUNY College of Optometry
            Stony Brook University
            Syracuse University
            Touro University
            Trocaire College
            Union College
            US Merchant Marine Academy
            US Military Academy
            University at Albany
            University at Buffalo
            University of Mount Saint Vincent
            University of Rochester
            Upstate Medical University
            Utica University
            Vassar College
            Villa Maria College
            Vaughn College of Aeronautics & Technology
            Wagner College
            Webb Institute
            Yeshiva University
            Academy of Art University
            American Jewish University
            Antioch University Los Angeles
            Antioch University Santa Barbara
            ArtCenter College of Design
            Azusa Pacific University
            Bethesda University
            Biola University
            California Baptist University
            California College of the Arts
            California Institute of the Arts
            California Institute of Technology
            California Lutheran University
            California Miramar University
            Cal Polytechnic State University
            Cal State Polytechnic University, Humboldt
            Cal State Polytechnic University, Pomona
            Cal State University, Bakersfield
            Cal State University Channel Islands
            Cal State University, Chico
            Cal State University, Dominguez Hills
            Cal State University, East Bay
            Cal State University, Fresno
            Cal State University, Fullerton
            Cal State University, Long Beach
            Cal State University, Los Angeles
            Cal State University, Monterey Bay
            Cal State University, Northridge
            Cal State University, Sacramento
            Cal State University, San Bernardino
            California State University San Marcos
            California State University, Stanislaus
            California Western School of Law
            California Institute of Integral Studies
            Chapman University
            Charles R. Drew University of Medicine & Science
            The Chicago School of Professional Psychology- Anaheim
            Claremont McKenna College
            Claremont Graduate University
            Concordia University Irvine
            Dharma Realm Buddhist University
            Dominican School of Philosophy & Theology
            Dominican University of California
            Fresno Pacific University
            Golden Gate University
            Harvey Mudd College
            Holy Names University
            Hope International University
            Humphreys University
            Irell and Manella Grad School of Bio Sci
            Jessup University
            John Paul the Great Catholic University
            Keck Graduate Institute
            La Sierra University
            Laguna College of Art and Design
            Life Pacific University
            Loma Linda University
            Loyola Marymount University
            Marshall B. Ketchum University
            The Master's University and Seminary
            Menlo College
            Mills College at Northeastern University
            Mount St. Mary's University
            National University
            NewSchool of Architecture and Design
            Notre Dame de Namur University
            Occidental College
            Otis College of Art and Design
            Pacifica Graduate Institute
            Pacific Oaks College
            Pacific Union College
            Palo Alto University
            Pepperdine University
            Pitzer College
            Point Loma Nazarene University
            Pomona College
            Providence Christian College
            Saint Mary's College of California
            Samuel Merritt University
            San Diego Christian College
            San Diego State University
            San Francisco Conservatory of Music
            San Francisco State University
            San Joaquin College of Law
            San Jose State University
            Santa Clara University
            Saybrook University
            Scripps College
            Simpson University
            Soka University of America
            Sofia University
            Sonoma State University
            Southern California Institute of Architecture
            Southwestern Law School
            Stanford University
            Thomas Aquinas College
            Thomas Jefferson School of Law
            Touro University California
            United States University
            University of California Berkeley
            University of California Davis
            University of California Irvine
            University of California Los Angeles
            University of California Merced
            University of California Riverside
            University of California San Diego
            University of California San Francisco
            University of California Santa Barbara
            University of California Santa Cruz
            University of La Verne
            University of the Pacific
            University of Redlands
            University of San Diego
            University of San Francisco
            University of St. Augustine for Health Sciences
            University of Silicon Valley
            University of Southern California
            The University of West Los Angeles
            University of the West
            Vanguard University
            West Coast University-Los Angeles
            Western University of Health Sciences
            Westmont College
            Whittier College
            Woodbury University
            AdventHealth University
            Ave Maria University
            Baptist University of Florida
            Barry University
            Beacon College
            Bethune-Cookman University
            Chipola College
            College of Central Florida
            Daytona State College
            Eckerd College
            Edward Waters University
            Embry-Riddle Aeronautical University
            Everglades University
            Flagler College
            Florida A&M University
            Florida Atlantic University
            Florida College
            Florida Gulf Coast University
            Florida Institute of Technology
            Florida International University
            Florida Memorial University
            Florida National University
            Florida Polytechnic University
            Florida Southern College
            Florida Southwestern State College
            Florida State College at Jacksonville
            Florida State University
            Indian River State College
            Gulf Coast State College
            Jacksonville University
            Keiser University
            Lynn University
            Miami Dade College
            New College of Florida
            Nova Southeastern University
            Northwest Florida State College
            Palm Beach Atlantic University
            Palm Beach State College
            Pensacola State College
            Polk State College
            Ringling College of Art and Design
            Rollins College
            Saint Leo University
            Seminole State College of Florida
            Southeastern University
            State College of FL Manatee-Sarasota
            St. Johns River State College
            St. Petersburg College
            St. Thomas University
            Stetson University
            Trinity College of Florida
            University of Central Florida
            University of Florida
            University of Miami
            University of North Florida
            University of South Florida
            University of Tampa
            University of West Florida
            Valencia College
            Warner University
            Webber International University
            American International College
            Amherst College
            Anna Maria College
            Assumption University
            Babson College
            Bard College at Simon's Rock
            Bay Path University
            Bay State College
            Ben Franklin Cummings Institute of Technology
            Bentley University
            Berklee College of Music
            Boston Architectural College
            Boston College
            Boston Graduate School of Psychoanalysis
            Boston University
            Brandeis University
            Bridgewater State University
            Cambridge College
            Clark University
            College of Our Lady of the Elms
            College of the Holy Cross
            Conway School of Landscape Design
            Curry College
            Dean College
            Emerson College
            Emmanuel College
            Endicott College
            Fisher College
            Franklin W. Olin College of Engineering
            Fitchburg State University
            Framingham State University
            Gordon College
            Hampshire College
            Harvard University
            Hult International Business School
            Hebrew College
            Labouré College
            Lasell College
            Lesley University
            Massachusetts College of Liberal Arts
            Massachusetts College of Art and Design
            Massachusetts Institute of Technology
            Massachusetts Maritime Academy
            Massachusetts School of Law
            Massachusetts College of Pharmacy and Health Sciences
            Merrimack College
            MGH Institute of Health Professions
            Montserrat College of Art
            Mount Holyoke College
            New England Law Boston
            Nichols College
            Northeastern University
            Northpoint Bible College
            Regis College
            Salem State University
            Simmons University
            Stonehill College
            Springfield College
            Smith College
            Suffolk University
            Tufts University
            University of Massachusetts- Amherst
            University of Massachusetts- Boston
            University of Massachusetts- Chan
            University of Massachusetts- Dartmouth
            University of Massachusetts- Lowell
            University of Massachusetts- Global
            Wellesley College
            Wentworth Institute of Technology
            Western New England University
            Westfield State University
            Wheaton College
            William James College
            Williams College
            Worcester Polytechnic Institute
            Worcester State University
            Abilene Christian University
            Amberton University
            Angelo State University
            Arlington Baptist University
            Austin College
            Baptist University of the Americas
            Baylor College of Medicine
            Baylor University
            Brazosport College
            College of Biblical Studies
            Concordia University Texas
            Criswell College
            Dallas Baptist University
            Dallas Christian College
            East Texas A&M University
            East Texas Baptist University
            Hardin-Simmons University
            Houston Christian University
            Howard Payne University
            Huston-Tillotson University
            Jarvis Christian University
            The King's University
            Lamar University
            LeTourneau University
            Lubbock Christian University
            McMurry University
            Midland College
            Midwestern State University
            Nelson University
            North American University
            Our Lady of the Lake University
            Parker University
            Paul Quinn College
            Prairie View A&M University
            Rice University
            Sam Houston State University
            Schreiner University
            Southern Methodist University
            South Texas College of Law
            South Texas College
            Southwestern Adventist University
            Southwestern University
            St. Edward's University
            St. Mary's University
            Stephen F. Austin State University
            Sul Ross State University
            Tarleton State University
            Texas A&M International University
            Texas A&M University- College Station
            Texas A&M University-Corpus Christi
            Texas A&M University at Galveston
            Texas A&M University-Kingsville
            Texas A&M University-San Antonio
            Texas A&M University-Texarkana
            Texas Christian University
            Texas College
            Texas Lutheran University
            Texas Southern University
            Texas Tech University
            Texas Tech Univ Health Sciences Center
            Texas Wesleyan University
            Texas Woman's University
            Trinity University
            University of Dallas
            University of Houston
            University of Houston-Clear Lake
            University of Houston-Downtown
            University of Houston-Victoria
            University of the Incarnate Word
            University of Mary Hardin-Baylor
            University of North Texas
            University of St. Thomas
            University of Texas at Arlington
            University of Texas Austin
            University of Texas Dallas
            University of Texas El Paso
            University of Texas Permian Basin
            University of Texas Rio Grande Valley
            University of Texas at San Antonio
            University of Texas at Tyler
            Wayland Baptist University
            West Texas A&M University
            Wiley University
            Wade College
            Miles College
            Jackson Theological Institute
            Diné College
            Colorado Mountain College
            Broward College
            The Chicago School of Professional Psychology- Los Angeles
            The Chicago School of Professional Psychology- Online
            The Chicago School of Professional Psychology- San Diego
            American University
            The Catholic University of America
            The Chicago School at Washington DC
            George Washington University
            Gallaudet University
            Georgetown University
            Howard University
            The Institute of World Politics
            National Defense University
            NewU University
            Pontifical Faculty of Immaculate Conception at the Dominican House of Studies
            Strayer University
            Trinity Washington University
            University of the District of Columbia
            University of the Potomac
            Ottowa University
            Sonoran University of Health Sciences
            Rhode Island School of Design
            Langston University- Oklahoma City
        ";

        $names = explode("\n", $rawList);

        foreach ($names as $name) {
            $trimmedName = trim($name);

            if (! empty($trimmedName)) {
                Institution::updateOrCreate(
                    ['name' => $trimmedName],
                    []
                );
            }
        }
    }
}
