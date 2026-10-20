<?php

namespace Database\Seeders;

use App\Enums\AdCampaignStatus;
use App\Models\AdCampaign;
use App\Models\BoostPackage;
use App\Models\Group;
use App\Models\Industry;
use App\Models\IndustryCategory;
use App\Models\IndustryProduct;
use App\Models\IndustrySections;
use App\Models\Post;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class PreviewDataSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'api']);

        // 2. Primary Users
        $niaz = User::updateOrCreate(
            ['email' => 'niaz.softvence@gmail.com'],
            [
                'first_name' => 'Niaz',
                'last_name' => 'Nayeem',
                'username' => 'niaz_softvence',
                'title' => 'Lead Full Stack & AI Architect',
                'mobile' => '+8801800000000',
                'password' => bcrypt('111111'),
                'is_verified' => true,
                'profile_image' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=200&h=200&q=80',
            ]
        );
        $niaz->assignRole($userRole);

        UserProfile::updateOrCreate(
            ['user_id' => $niaz->id],
            [
                'country' => 'Bangladesh',
                'profession' => ['Full Stack Engineer'],
                'highest_degree' => "Bachelor's Degree",
                'about' => 'Pioneering cutting edge neuro-tech software systems and digital research platforms.',
            ]
        );

        $elena = User::updateOrCreate(
            ['email' => 'elena.rostova@neurotech.org'],
            [
                'first_name' => 'Elena',
                'last_name' => 'Rostova',
                'username' => 'elena_rostova',
                'title' => 'Chief Scientific Officer at NeuroTech Labs',
                'mobile' => '+15552345678',
                'password' => bcrypt('111111'),
                'is_verified' => true,
                'profile_image' => 'https://images.unsplash.com/photo-1573496359142-b8d87734a5a2?auto=format&fit=crop&w=200&h=200&q=80',
            ]
        );
        $elena->assignRole($userRole);

        $marcus = User::updateOrCreate(
            ['email' => 'marcus.vance@mindwave.io'],
            [
                'first_name' => 'Marcus',
                'last_name' => 'Vance',
                'username' => 'marcus_vance',
                'title' => 'Principal Investigator in Cognitive Diagnostics',
                'mobile' => '+15553456789',
                'password' => bcrypt('111111'),
                'is_verified' => true,
                'profile_image' => 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=200&h=200&q=80',
            ]
        );
        $marcus->assignRole($userRole);

        // 3. Research Groups
        $groups = [
            [
                'name' => 'Cognitive Neuroscience & BCI Pioneers',
                'description' => 'A global forum for researchers developing non-invasive telemetry and high-density EEG caps.',
                'industry' => ['biotechnology', 'neuroscience'],
                'creator_id' => $elena->id,
            ],
            [
                'name' => 'Clinical Psychology & Behavioral Tech',
                'description' => 'Exploring algorithmic behavioral biomarkers, digital health therapeutics, and clinical research.',
                'industry' => ['psychology'],
                'creator_id' => $marcus->id,
            ],
            [
                'name' => 'Next-Gen Neural Diagnostics Society',
                'description' => 'Peer discussions and preprints on ERP analysis, artifact removal, and spatial EEG filtering.',
                'industry' => ['biotechnology'],
                'creator_id' => $niaz->id,
            ],
        ];

        foreach ($groups as $g) {
            Group::firstOrCreate(
                ['name' => $g['name']],
                [
                    'slug' => Str::slug($g['name']),
                    'description' => $g['description'],
                    'industry' => $g['industry'],
                    'creator_id' => $g['creator_id'],
                    'type' => 'public',
                ]
            );
        }

        // 4. Boost Packages
        $pkgStarter = BoostPackage::firstOrCreate(
            ['id' => 1],
            [
                'name' => '7-Day Starter Boost',
                'target_entity' => 'product',
                'duration_days' => 7,
                'price' => 19.99,
                'badge' => 'Sponsored',
                'priority_weight' => 1,
                'is_active' => true,
            ]
        );

        $pkgGrowth = BoostPackage::firstOrCreate(
            ['id' => 2],
            [
                'name' => '14-Day Growth Boost',
                'target_entity' => 'product',
                'duration_days' => 14,
                'price' => 34.99,
                'badge' => 'Sponsored',
                'priority_weight' => 2,
                'is_active' => true,
            ]
        );

        $pkgPro = BoostPackage::firstOrCreate(
            ['id' => 3],
            [
                'name' => '30-Day Pro Boost',
                'target_entity' => 'product',
                'duration_days' => 30,
                'price' => 69.99,
                'badge' => 'Sponsored',
                'priority_weight' => 3,
                'is_active' => true,
            ]
        );

        // 5. Industries (Companies)
        $neuroTechLab = Industry::firstOrCreate(
            ['name' => 'NeuroTech Labs Global'],
            [
                'slug' => 'neurotech-labs-global',
                'industry' => 'biotechnology',
                'website' => 'https://neurotechlabs.example.com',
                'tagline' => 'Engineering High-Density Non-Invasive Brain-Computer Interfaces',
                'description' => 'Global leader in EEG spatial filters, cognitive telemetry hardware, and neurofeedback systems.',
                'company_size' => '250-500',
                'created_by' => $elena->id,
                'authorization_confirmed' => true,
                'authorization_confirmed_at' => now(),
            ]
        );

        $mindWave = Industry::firstOrCreate(
            ['name' => 'MindWave BioDiagnostics'],
            [
                'slug' => 'mindwave-biodiagnostics',
                'industry' => 'biotechnology',
                'website' => 'https://mindwave.example.com',
                'tagline' => 'Next-Generation Ambulatory Biosensors & Diagnostic Apparatus',
                'description' => 'Precision biomedical instrumentation engineered for neurological clinics and university laboratories.',
                'company_size' => '50-100',
                'created_by' => $marcus->id,
                'authorization_confirmed' => true,
                'authorization_confirmed_at' => now(),
            ]
        );

        $synapsePharma = Industry::firstOrCreate(
            ['name' => 'SynapseBio Therapeutics'],
            [
                'slug' => 'synapsebio-therapeutics',
                'industry' => 'psychotropics',
                'website' => 'https://synapsebio.example.com',
                'tagline' => 'Targeted Pharmacological Formulations for Cognitive Health',
                'description' => 'Dedicated to breakthrough psychiatric research, neuro-protective molecules, and clinical trials.',
                'company_size' => '100-250',
                'created_by' => null,
                'authorization_confirmed' => true,
                'authorization_confirmed_at' => now(),
            ]
        );

        // 6. Sections & Categories for Psychology -> Biotechnology
        $sec1 = IndustrySections::firstOrCreate(
            ['name' => 'Diagnostic & Research Equipment', 'network_type' => 'psychology', 'industry_type' => 'biotechnology']
        );
        $cat1_1 = IndustryCategory::firstOrCreate(['section_id' => $sec1->id, 'category_name' => 'EEG & Brain Scanners']);
        $cat1_2 = IndustryCategory::firstOrCreate(['section_id' => $sec1->id, 'category_name' => 'Telemetry Biosensors']);
        $cat1_3 = IndustryCategory::firstOrCreate(['section_id' => $sec1->id, 'category_name' => 'Neural Imaging Apparatus']);

        $sec2 = IndustrySections::firstOrCreate(
            ['name' => 'Therapeutic & Laboratory Systems', 'network_type' => 'psychology', 'industry_type' => 'biotechnology']
        );
        $cat2_1 = IndustryCategory::firstOrCreate(['section_id' => $sec2->id, 'category_name' => 'Neurofeedback Systems']);
        $cat2_2 = IndustryCategory::firstOrCreate(['section_id' => $sec2->id, 'category_name' => 'Clinical Hardware']);
        $cat2_3 = IndustryCategory::firstOrCreate(['section_id' => $sec2->id, 'category_name' => 'Sensory Simulators']);

        $sec3 = IndustrySections::firstOrCreate(
            ['name' => 'Rehabilitation & Wearable Tech', 'network_type' => 'psychology', 'industry_type' => 'biotechnology']
        );
        $cat3_1 = IndustryCategory::firstOrCreate(['section_id' => $sec3->id, 'category_name' => 'Wearable Bands']);
        $cat3_2 = IndustryCategory::firstOrCreate(['section_id' => $sec3->id, 'category_name' => 'Mobility Tracking']);
        $cat3_3 = IndustryCategory::firstOrCreate(['section_id' => $sec3->id, 'category_name' => 'Cognitive Aids']);

        // 7. Seed Products
        $productsData = [
            // Section 1 Products
            [
                'section_id' => $sec1->id,
                'category_id' => $cat1_1->id,
                'product_name' => 'NextGen Wireless 32-Channel EEG Headset',
                'description' => 'State-of-the-art ambulatory 32-channel EEG headset engineered with active dry-contact silver sensors, ultra-low input noise (<0.5 μV RMS), and instant Bluetooth 5.3 low-latency research telemetry.',
                'product_url' => 'https://neurotechlabs.example.com/eeg-32ch',
                'image' => 'https://images.unsplash.com/photo-1550751827-4bd374c3f58b?auto=format&fit=crop&w=800&q=80',
                'tags' => ['EEG', 'Brain-Computer Interface', 'Diagnostics'],
                'poc_name' => 'Dr. Elena Rostova',
                'poc_email' => 'elena.rostova@neurotech.org',
                'poc_phone' => '+1 (555) 234-5678',
                'is_boosted' => true,
                'boost_package_id' => $pkgGrowth->id,
                'impressions' => 1240,
                'clicks' => 84,
                'views' => 142,
                'unique_views' => 98,
                'likes' => 38,
            ],
            [
                'section_id' => $sec1->id,
                'category_id' => $cat1_2->id,
                'product_name' => 'SynapsePro Telemetry Biosensor Matrix',
                'description' => 'Clinical-grade wearable multi-modal biosensor recording continuous galvanic skin conductance, heart-rate variability, and peripheral temperature with automated artifact rejection.',
                'product_url' => 'https://mindwave.example.com/synapse-pro',
                'image' => 'https://images.unsplash.com/photo-1576091160399-112ba8d25d1d?auto=format&fit=crop&w=800&q=80',
                'tags' => ['Biosensor', 'Telemetry', 'Clinical'],
                'poc_name' => 'Marcus Vance',
                'poc_email' => 'marcus.vance@mindwave.io',
                'poc_phone' => '+1 (555) 345-6789',
                'is_boosted' => true,
                'boost_package_id' => $pkgPro->id,
                'impressions' => 2180,
                'clicks' => 142,
                'views' => 210,
                'unique_views' => 165,
                'likes' => 64,
            ],
            [
                'section_id' => $sec1->id,
                'category_id' => $cat1_3->id,
                'product_name' => 'CortexScan Optical Topography Imaging Unit',
                'description' => 'Functional near-infrared spectroscopy (fNIRS) optical scanner mapping cortical hemodynamic activations in real time with 64 optical optodes.',
                'product_url' => 'https://neurotechlabs.example.com/cortex-scan',
                'image' => 'https://images.unsplash.com/photo-1582719478250-c89cae4dc85b?auto=format&fit=crop&w=800&q=80',
                'tags' => ['fNIRS', 'Imaging', 'Neuroscience'],
                'poc_name' => 'Dr. Elena Rostova',
                'poc_email' => 'elena.rostova@neurotech.org',
                'poc_phone' => '+1 (555) 234-5678',
                'is_new_release' => true,
                'views' => 45,
                'unique_views' => 32,
                'likes' => 19,
            ],
            [
                'section_id' => $sec1->id,
                'category_id' => $cat1_1->id,
                'product_name' => 'BioTrace Ultra Compact 8-Channel Amplifier',
                'description' => 'Pocket-sized EEG/EMG/ECG amplifier designed for field experiments and mobile neuroergonomic research.',
                'product_url' => 'https://mindwave.example.com/biotrace-8',
                'image' => 'https://images.unsplash.com/photo-1581093458791-9f3c3900df4b?auto=format&fit=crop&w=800&q=80',
                'tags' => ['Amplifier', 'Laboratory', 'Mobile'],
                'poc_name' => 'Marcus Vance',
                'poc_email' => 'marcus.vance@mindwave.io',
                'poc_phone' => '+1 (555) 345-6789',
                'views' => 68,
                'unique_views' => 54,
                'likes' => 14,
            ],

            // Section 2 Products
            [
                'section_id' => $sec2->id,
                'category_id' => $cat2_1->id,
                'product_name' => 'MindSync Clinical Neurofeedback Workstation',
                'description' => 'Comprehensive dual-screen clinician station providing quantitative EEG (qEEG) normative database comparisons, z-score training protocols, and interactive sensory reinforcement.',
                'product_url' => 'https://neurotechlabs.example.com/mindsync',
                'image' => 'https://images.unsplash.com/photo-1516549655169-df83a0774514?auto=format&fit=crop&w=800&q=80',
                'tags' => ['Neurofeedback', 'Therapeutics', 'qEEG'],
                'poc_name' => 'Dr. Elena Rostova',
                'poc_email' => 'elena.rostova@neurotech.org',
                'poc_phone' => '+1 (555) 234-5678',
                'is_boosted' => true,
                'boost_package_id' => $pkgStarter->id,
                'impressions' => 840,
                'clicks' => 52,
                'views' => 95,
                'unique_views' => 70,
                'likes' => 29,
            ],
            [
                'section_id' => $sec2->id,
                'category_id' => $cat2_2->id,
                'product_name' => 'AeroStim Transcranial Direct Current Stimulator',
                'description' => 'High-precision research tDCS/tACS stimulator with programmable current waveforms, impedance monitoring, and double-blind sham protocols.',
                'product_url' => 'https://mindwave.example.com/aerostim',
                'image' => 'https://images.unsplash.com/photo-1532938911079-1b06ac7ceec7?auto=format&fit=crop&w=800&q=80',
                'tags' => ['Neuromodulation', 'tDCS', 'Hardware'],
                'poc_name' => 'Marcus Vance',
                'poc_email' => 'marcus.vance@mindwave.io',
                'poc_phone' => '+1 (555) 345-6789',
                'is_new_release' => true,
                'views' => 58,
                'unique_views' => 41,
                'likes' => 22,
            ],
            [
                'section_id' => $sec2->id,
                'category_id' => $cat2_3->id,
                'product_name' => 'SensoryChamber Multi-Modal Stimulus Generator',
                'description' => 'Sub-millisecond calibrated auditory, visual, and tactile stimulus presenter for Event-Related Potential (ERP) research.',
                'product_url' => 'https://mindwave.example.com/sensory-chamber',
                'image' => 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?auto=format&fit=crop&w=800&q=80',
                'tags' => ['ERP', 'Stimulation', 'Research'],
                'poc_name' => 'Marcus Vance',
                'poc_email' => 'marcus.vance@mindwave.io',
                'poc_phone' => '+1 (555) 345-6789',
                'views' => 34,
                'unique_views' => 28,
                'likes' => 11,
            ],

            // Section 3 Products
            [
                'section_id' => $sec3->id,
                'category_id' => $cat3_1->id,
                'product_name' => 'NeuroBand Sleep & Circadian Rhythm Tracker',
                'description' => 'Lightweight medical headband continuously capturing slow-wave sleep delta rhythms, sleep spindles, and REM transitions.',
                'product_url' => 'https://neurotechlabs.example.com/neuroband',
                'image' => 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&w=800&q=80',
                'tags' => ['Wearable', 'Sleep', 'Circadian'],
                'poc_name' => 'Dr. Elena Rostova',
                'poc_email' => 'elena.rostova@neurotech.org',
                'poc_phone' => '+1 (555) 234-5678',
                'views' => 112,
                'unique_views' => 88,
                'likes' => 47,
            ],
            [
                'section_id' => $sec3->id,
                'category_id' => $cat3_2->id,
                'product_name' => 'KineticGait Inertial Motion Capture Sensor',
                'description' => '6-axis wireless IMU sensors measuring tremor frequency, stride length variability, and postural stability in motor disorders.',
                'product_url' => 'https://mindwave.example.com/kinetic-gait',
                'image' => 'https://images.unsplash.com/photo-1584515979956-d9f6e5d09982?auto=format&fit=crop&w=800&q=80',
                'tags' => ['Gait', 'IMU', 'Rehabilitation'],
                'poc_name' => 'Marcus Vance',
                'poc_email' => 'marcus.vance@mindwave.io',
                'poc_phone' => '+1 (555) 345-6789',
                'views' => 74,
                'unique_views' => 61,
                'likes' => 16,
            ],
            [
                'section_id' => $sec3->id,
                'category_id' => $cat3_3->id,
                'product_name' => 'CogniGlove Haptic Feedback Neuro-Rehab System',
                'description' => 'Targeted vibrotactile rehabilitation glove stimulating somatosensory cortex reorganization in stroke recovery patients.',
                'product_url' => 'https://neurotechlabs.example.com/cogniglove',
                'image' => 'https://images.unsplash.com/photo-1530497610245-94d3c16cda28?auto=format&fit=crop&w=800&q=80',
                'tags' => ['Haptics', 'Rehabilitation', 'Neuroplasticity'],
                'poc_name' => 'Dr. Elena Rostova',
                'poc_email' => 'elena.rostova@neurotech.org',
                'poc_phone' => '+1 (555) 234-5678',
                'views' => 89,
                'unique_views' => 72,
                'likes' => 31,
            ],
        ];

        foreach ($productsData as $pData) {
            $isBoosted = ! empty($pData['is_boosted']);
            $isNewRelease = ! empty($pData['is_new_release']);

            $product = IndustryProduct::create([
                'creator_id' => $niaz->id,
                'industry_id' => $neuroTechLab->id,
                'network_type' => 'psychology',
                'industry_type' => 'biotechnology',
                'section_id' => $pData['section_id'],
                'category_id' => $pData['category_id'],
                'product_name' => $pData['product_name'],
                'slug' => Str::slug($pData['product_name']).'-'.Str::random(4),
                'description' => $pData['description'],
                'product_url' => $pData['product_url'],
                'image' => $pData['image'],
                'tags' => $pData['tags'],
                'poc_name' => $pData['poc_name'],
                'poc_email' => $pData['poc_email'],
                'poc_phone' => $pData['poc_phone'],
                'information_confirmed' => true,
                'status' => 'active',
                'is_boosted' => $isBoosted,
                'boosted_at' => $isBoosted ? now()->subDay() : null,
                'boost_expires_at' => $isBoosted ? now()->addDays(14) : null,
                'boost_score' => $isBoosted ? 120 : 0,
                'is_new_release' => $isNewRelease,
                'new_release_expires_at' => $isNewRelease ? now()->addHours(24) : null,
                'views_count' => $pData['views'] ?? 10,
                'unique_views_count' => $pData['unique_views'] ?? 8,
                'likes_count' => $pData['likes'] ?? 5,
                'impressions_count' => $pData['impressions'] ?? 0,
                'clicks_count' => $pData['clicks'] ?? 0,
            ]);

            // If boosted, create matching AdCampaign
            if ($isBoosted) {
                AdCampaign::create([
                    'user_id' => $niaz->id,
                    'industry_id' => $neuroTechLab->id,
                    'boost_package_id' => $pData['boost_package_id'] ?? $pkgGrowth->id,
                    'boostable_type' => IndustryProduct::class,
                    'boostable_id' => $product->id,
                    'ad_type' => 'product',
                    'network_type' => 'psychology',
                    'placements' => ['newsfeed', 'marketplace', 'search'],
                    'amount_paid' => 34.99,
                    'currency' => 'usd',
                    'payment_platform' => 'stripe',
                    'duration_days' => 14,
                    'starts_at' => now()->subDay(),
                    'ends_at' => now()->addDays(13),
                    'status' => AdCampaignStatus::ACTIVE,
                    'total_impressions' => $pData['impressions'] ?? 100,
                    'total_clicks' => $pData['clicks'] ?? 10,
                ]);
            }
        }

        // 8. Public Posts for Newsfeed
        $posts = [
            [
                'user_id' => $elena->id,
                'description' => "Proud to share our latest clinical preprint in the Journal of Neural Telemetry! We demonstrated that automated spatial artifact reduction decreases ocular noise by 84% without degrading P300 amplitude during high-density ambulatory EEG recordings.\n\nExcited to hear thoughts from other researchers in the field! #EEG #Neuroscience #Research #Biotech",
                'total_like' => 64,
                'total_comment' => 12,
            ],
            [
                'user_id' => $marcus->id,
                'description' => "Attending the European Cognitive Diagnostics Symposium in Geneva this week. The rapid evolution of dry-sensor BCI over the last 18 months has made long-term home monitoring feasible for patients with neurological conditions.\n\nCatch our live demo tomorrow afternoon at booth #104! #BrainHealth #ClinicalDiagnostics #Symposium",
                'total_like' => 48,
                'total_comment' => 8,
            ],
            [
                'user_id' => $niaz->id,
                'description' => "Our engineering team just shipped the latest firmware update for the MindUnite research portal. Now supporting synchronous streaming from up to 64 biosensors with microsecond timestamp synchronization across all connected channels.\n\nScalability tests showed sub-10ms roundtrip latency even on standard WiFi! #SoftwareEngineering #Architecture #Laravel #RealTime",
                'total_like' => 89,
                'total_comment' => 16,
            ],
            [
                'user_id' => $elena->id,
                'description' => 'Question for clinical neuroscientists: When evaluating Event-Related Potentials (ERPs) in pediatric populations, which montage configurations do you find yield the cleanest signal-to-noise ratio in sensory gating protocols? Looking forward to your insights.',
                'total_like' => 31,
                'total_comment' => 19,
            ],
            [
                'user_id' => $marcus->id,
                'description' => 'Fascinating data emerging from our multi-site study on continuous sleep architecture tracking. Slow-wave delta amplitude variations appear to correlate closely with next-day cognitive performance metrics in executive task batteries.',
                'total_like' => 55,
                'total_comment' => 9,
            ],
            [
                'user_id' => $niaz->id,
                'description' => 'Just finalized the real-time event aggregation pipeline using Redis and MySQL partitioned tables. Throughput increased by 300% under high-concurrency websocket load. Clean code and proper indexing always win!',
                'total_like' => 73,
                'total_comment' => 14,
            ],
            [
                'user_id' => $elena->id,
                'description' => 'Reviewing new grant applications focusing on wearable closed-loop neurostimulation. The convergence of edge computing and bio-potential amplifiers is transforming patient care faster than expected.',
                'total_like' => 42,
                'total_comment' => 6,
            ],
            [
                'user_id' => $marcus->id,
                'description' => 'Huge milestone for our laboratory: Our open-access repository for 64-channel resting-state EEG datasets just crossed 10,000 downloads worldwide. Open science accelerates neurological discovery!',
                'total_like' => 118,
                'total_comment' => 24,
            ],
            [
                'user_id' => $niaz->id,
                'description' => 'Building scalable backend architectures for healthcare and neuroscience requires relentless attention to data integrity, audit trails, and sub-second query performance. Loving this journey!',
                'total_like' => 96,
                'total_comment' => 18,
            ],
            [
                'user_id' => $elena->id,
                'description' => 'Excited to welcome 4 new postdoctoral fellows to our brain-computer interface research group this quarter. We will be investigating motor imagery decoding algorithms for prosthetic hand control.',
                'total_like' => 61,
                'total_comment' => 11,
            ],
            [
                'user_id' => $marcus->id,
                'description' => 'Comparing dry versus wet electrode impedance stability across 8-hour continuous ambulatory recordings. Preliminary findings suggest novel carbon nanotube polymers maintain signal fidelity exceptionally well.',
                'total_like' => 50,
                'total_comment' => 7,
            ],
            [
                'user_id' => $niaz->id,
                'description' => 'Always remember: optimize queries before adding servers, structure database indexes to match your exact access patterns, and use eager loading to avoid the silent killer that is N+1 queries.',
                'total_like' => 134,
                'total_comment' => 32,
            ],
        ];

        foreach ($posts as $postData) {
            Post::firstOrCreate(
                ['description' => $postData['description']],
                [
                    'user_id' => $postData['user_id'],
                    'visibility' => 'public',
                    'who_can_comment' => 'anyone',
                    'total_like' => $postData['total_like'],
                    'total_comment' => $postData['total_comment'],
                    'created_at' => now()->subHours(rand(1, 48)),
                ]
            );
        }
    }
}
