<?php

namespace Database\Seeders;

use App\Enums\PlanType;
use App\Models\Industry;
use App\Models\Plan;
use App\Models\Publication;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserProfile;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class PublicationSeeder extends Seeder
{
    /**
     * Run the database seeds to generate 500 publications.
     */
    public function run(): void
    {
        // 1. Ensure Plans and Roles exist
        $this->call(PlanSeeder::class);
        $roleUser = Role::where('name', 'user')->where('guard_name', 'api')->first()
            ?? Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        $industryPlan = Plan::where('name', 'Pro Industries')->first()
            ?? Plan::where('plan_type', PlanType::INDUSTRY->value)->first();

        $premiumPlan = Plan::where('name', 'Pro User')->first()
            ?? Plan::where('plan_type', PlanType::PREMIUM->value)->first();

        $passwordHash = Hash::make('password123');

        // 2. Setup 8 Pro Industry Companies & Owner Users
        $industryData = [
            ['name' => 'CogniSense Therapeutics', 'industry' => 'Biotechnology', 'tagline' => 'Next-generation neuro-restorative therapeutics.'],
            ['name' => 'Synapse Dynamics Medical', 'industry' => 'Biotechnology', 'tagline' => 'Precision brain interfaces and neuro-monitoring systems.'],
            ['name' => 'NeuroPulse BioDevices', 'industry' => 'Biotechnology', 'tagline' => 'Non-invasive trans-cranial neurological stimulation.'],
            ['name' => 'Cortex Innovations Lab', 'industry' => 'Biotechnology', 'tagline' => 'Advanced high-resolution cortical mapping technologies.'],
            ['name' => 'Vanguard Psychiatric Diagnostics', 'industry' => 'Psychotropics', 'tagline' => 'Empirical biomarker discovery for psychiatric diagnosis.'],
            ['name' => 'MindMetrics BioPharma', 'industry' => 'Psychotropics', 'tagline' => 'Novel pharmacotherapies for treatment-resistant mood disorders.'],
            ['name' => 'AuraBio Health Systems', 'industry' => 'Biotechnology', 'tagline' => 'Autonomous cognitive monitoring and neurological analytics.'],
            ['name' => 'Axon NeuroGenomics', 'industry' => 'Biotechnology', 'tagline' => 'Cellular and genetic therapies for neurodegenerative conditions.'],
        ];

        $seededIndustries = [];

        foreach ($industryData as $index => $item) {
            $userEmail = 'industry.owner'.($index + 1).'@example.com';
            $user = User::firstOrCreate(
                ['email' => $userEmail],
                [
                    'first_name' => 'Exec',
                    'last_name' => 'Director '.($index + 1),
                    'username' => 'industry_exec_'.($index + 1),
                    'password' => $passwordHash,
                    'is_verified' => 1,
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole('user')) {
                $user->assignRole($roleUser);
            }

            UserProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'country' => 'United States',
                    'about' => 'Bio-industry executive and research director.',
                ]
            );

            // Active Pro Industry subscription
            Subscription::firstOrCreate(
                ['user_id' => $user->id, 'plan_id' => $industryPlan->id],
                [
                    'platform' => 'stripe',
                    'status' => 'active',
                    'provider_subscription_id' => 'sub_ind_'.Str::lower(Str::random(12)),
                    'provider_customer_id' => 'cus_ind_'.Str::lower(Str::random(12)),
                    'current_period_start' => now()->subMonth(),
                    'current_period_end' => now()->addMonth(),
                ]
            );

            // Create Company Profile
            $companySlug = Str::slug($item['name']);
            $industry = Industry::firstOrCreate(
                ['name' => $item['name']],
                [
                    'slug' => $companySlug,
                    'industry' => $item['industry'],
                    'website' => 'https://www.'.Str::slug($item['name']).'.com',
                    'tagline' => $item['tagline'],
                    'description' => $item['tagline'].' Dedicated to peer-reviewed breakthrough research.',
                    'company_size' => '50-200',
                    'address' => 'Cambridge Innovation Hub, MA, USA',
                    'authorization_confirmed' => true,
                    'authorization_confirmed_at' => now(),
                    'created_by' => $user->id,
                ]
            );

            $seededIndustries[] = [
                'industry' => $industry,
                'user' => $user,
            ];
        }

        // 3. Setup 15 Premium Researcher Users
        $researcherNames = [
            ['first' => 'Sarah', 'last' => 'Connor', 'title' => 'Lead Cognitive Neuroscientist'],
            ['first' => 'Marcus', 'last' => 'Wright', 'title' => 'Professor of Experimental Psychology'],
            ['first' => 'Emily', 'last' => 'Watson', 'title' => 'Principal Investigator in Neuroplasticity'],
            ['first' => 'Jonathan', 'last' => 'Crane', 'title' => 'Senior Behavioral Health Scientist'],
            ['first' => 'Claire', 'last' => 'Sterling', 'title' => 'Director of Neurodevelopmental Studies'],
            ['first' => 'David', 'last' => 'Kim', 'title' => 'Computational Neuroscience Fellow'],
            ['first' => 'Helena', 'last' => 'Lindqvist', 'title' => 'Associate Professor in Affective Science'],
            ['first' => 'Alexander', 'last' => 'Morozov', 'title' => 'Chief Clinical Psychologist'],
            ['first' => 'Maya', 'last' => 'Patel', 'title' => 'Senior Neuroimaging Researcher'],
            ['first' => 'Lucas', 'last' => 'Brandt', 'title' => 'Independent Brain Health Researcher'],
            ['first' => 'Hannah', 'last' => 'Becker', 'title' => 'Assistant Professor of Behavioral Neuroscience'],
            ['first' => 'Julian', 'last' => 'Vance', 'title' => 'Lead Psychometric Methodologist'],
            ['first' => 'Sophia', 'last' => 'Al-Mansoor', 'title' => 'Translational Neurobiology Postdoc'],
            ['first' => 'Evelyn', 'last' => 'Reed', 'title' => 'Clinical Neuropsychology Specialist'],
            ['first' => 'Thomas', 'last' => 'Kovacs', 'title' => 'Fellow in Psychiatric Epidemiology'],
        ];

        $seededResearchers = [];

        foreach ($researcherNames as $index => $res) {
            $userEmail = 'researcher'.($index + 1).'@university.edu';
            $user = User::firstOrCreate(
                ['email' => $userEmail],
                [
                    'first_name' => $res['first'],
                    'last_name' => $res['last'],
                    'username' => strtolower($res['first'].'_'.$res['last']),
                    'title' => $res['title'],
                    'password' => $passwordHash,
                    'is_verified' => 1,
                    'email_verified_at' => now(),
                ]
            );

            if (! $user->hasRole('user')) {
                $user->assignRole($roleUser);
            }

            UserProfile::firstOrCreate(
                ['user_id' => $user->id],
                [
                    'country' => 'United States',
                    'about' => $res['title'].' conducting clinical and experimental studies.',
                    'highest_degree' => 'Ph.D. / M.D.',
                    'institution' => 'Stanford / Harvard Research Institute',
                ]
            );

            // Active Premium subscription
            Subscription::firstOrCreate(
                ['user_id' => $user->id, 'plan_id' => $premiumPlan->id],
                [
                    'platform' => 'stripe',
                    'status' => 'active',
                    'provider_subscription_id' => 'sub_prem_'.Str::lower(Str::random(12)),
                    'provider_customer_id' => 'cus_prem_'.Str::lower(Str::random(12)),
                    'current_period_start' => now()->subMonth(),
                    'current_period_end' => now()->addMonth(),
                ]
            );

            $seededResearchers[] = $user;
        }

        // 4. Generate 500 Publications
        // Target:
        // - 200 Professional publications (created by industry companies)
        // - 300 Peer-reviewed publications (created by premium users: 180 university, 90 freelance, 30 other)
        // - Evenly split between 'neuroscience' (250) and 'psychology' (250)

        $neuroTitles = $this->getNeuroscienceTitles();
        $psychTitles = $this->getPsychologyTitles();

        $neuroAbstracts = $this->getNeuroscienceAbstracts();
        $psychAbstracts = $this->getPsychologyAbstracts();

        $publications = [];
        $usedPublicationIds = [];
        $usedSlugs = [];
        $now = now();

        // Clean existing publications for fresh run
        Publication::query()->forceDelete();

        for ($i = 1; $i <= 500; $i++) {
            // Evenly alternate between neuroscience and psychology
            $network = ($i % 2 === 0) ? 'psychology' : 'neuroscience';

            // Determine Ownership & Publication Type:
            // 1-200 -> Industry (Professional) - 100 neuro, 100 psych
            // 201-380 -> Premium User (University) - 90 neuro, 90 psych
            // 381-470 -> Premium User (Freelance) - 45 neuro, 45 psych
            // 471-500 -> Premium User (Other) - 15 neuro, 15 psych
            if ($i <= 200) {
                $pubType = 'professional';
                $indPair = $seededIndustries[($i - 1) % count($seededIndustries)];
                $industryId = $indPair['industry']->id;
                $creatorId = $indPair['user']->id;
                $seedId = (int) $industryId;
            } else {
                if ($i <= 380) {
                    $pubType = 'university';
                } elseif ($i <= 470) {
                    $pubType = 'freelance';
                } else {
                    $pubType = 'other';
                }

                $researcher = $seededResearchers[($i - 201) % count($seededResearchers)];
                $industryId = null;
                $creatorId = $researcher->id;
                $seedId = (int) $creatorId;
            }

            // Pick title and abstract
            if ($network === 'neuroscience') {
                $baseTitle = $neuroTitles[($i - 1) % count($neuroTitles)];
                $abstract = $neuroAbstracts[($i - 1) % count($neuroAbstracts)];
            } else {
                $baseTitle = $psychTitles[($i - 1) % count($psychTitles)];
                $abstract = $psychAbstracts[($i - 1) % count($psychAbstracts)];
            }

            // Append iteration index if cycled
            $cycle = (int) floor(($i - 1) / 50);
            $title = $cycle > 0 ? "{$baseTitle} (Cohort Study {$cycle})" : $baseTitle;

            // Generate guaranteed unique slug
            do {
                $slug = Str::slug($title).'-'.Str::lower(Str::random(6));
            } while (isset($usedSlugs[$slug]));
            $usedSlugs[$slug] = true;

            // Generate guaranteed unique publication_id
            do {
                $pubId = Publication::generateUniquePublicationId($seedId);
            } while (isset($usedPublicationIds[$pubId]));
            $usedPublicationIds[$pubId] = true;

            // Authors
            $authors = $this->generateAuthors($pubType, $creatorId, $seededResearchers);

            // Realistic random dates within last 360 days
            $daysAgo = ($i * 7) % 360;
            $createdAt = Carbon::now()->subDays($daysAgo)->subHours($i % 24);

            // Status: 96% active, 2% inactive, 2% draft
            $status = ($i % 50 === 0) ? 'draft' : (($i % 45 === 0) ? 'inactive' : 'active');

            $publications[] = [
                'publication_id' => $pubId,
                'creator_id' => $creatorId,
                'industry_id' => $industryId,
                'network_type' => $network,
                'publication_type' => $pubType,
                'title' => $title,
                'slug' => $slug,
                'authors' => json_encode($authors),
                'abstract' => $abstract,
                'website_url' => 'https://www.nature.com/articles/s41583-026-'.(1000 + $i),
                'attachment' => null,
                'information_confirmed' => 1,
                'status' => $status,
                'views_count' => ($i * 19) % 3500 + 45,
                'likes_count' => ($i * 7) % 380 + 3,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ];
        }

        // Chunk insert 500 rows for high performance
        foreach (array_chunk($publications, 100) as $chunk) {
            Publication::insert($chunk);
        }

        $this->command->info('Successfully seeded 500 publications (200 Industry Professional, 300 Premium Peer-Reviewed)!');
    }

    /**
     * Generate 1 to 4 realistic author names.
     */
    protected function generateAuthors(string $pubType, int $creatorId, array $researchers): array
    {
        $creator = User::find($creatorId);
        $primary = $creator ? trim($creator->first_name.' '.$creator->last_name) : 'Dr. Sarah Connor';

        $names = [$primary];
        $pool = [
            'Prof. Marcus Wright',
            'Dr. Emily Watson',
            'Prof. Jonathan Crane',
            'Dr. Claire Sterling',
            'Dr. David Kim',
            'Prof. Helena Lindqvist',
            'Dr. Alexander Morozov',
            'Dr. Maya Patel',
            'Dr. Lucas Brandt',
            'Prof. Evelyn Reed',
        ];

        $extraCount = rand(1, 3);
        $shuffled = collect($pool)->shuffle()->take($extraCount)->all();

        return array_values(array_unique(array_merge($names, $shuffled)));
    }

    protected function getNeuroscienceTitles(): array
    {
        return [
            'Neural Oscillation Dynamics in Prefrontal Executive Networks',
            'Synaptic Plasticity and Functional Recovery in Hippocampal Memory Consolidation',
            'Cortical Microcircuit Reorganization During Ischemic Stroke Rehabilitation',
            'Biomarkers of Neuroinflammation in Early-Stage Amyloid and Tau Pathophysiology',
            'Dopaminergic Modulation of Striatal Circuits in Refractory Neurological Conditions',
            'Optogenetic Interrogation of Inhibitory Ensembles in Frontoparietal Attention Shifts',
            'Resting-State fMRI Connectivity Patterns in Subthalamic Nucleus Synchrony',
            'Axonal Regeneration Mechanisms Following Acute Traumatic Spinal Injury',
            'Microglial Activation Cascades in Pediatric Neurodevelopmental Trajectories',
            'Deep Brain Stimulation Modulation of Thalamocortical Rhythmogenesis',
            'Blood-Brain Barrier Integrity Alterations in Chronic Neurodegeneration',
            'Neuromorphic Decoding of Motor Intentions via High-Density Electrode Arrays',
            'Cerebellar-Frontal Information Transfer During Motor Sequence Learning',
            'GABAergic Interneuron Vulnerability in Early Huntington Disease Progression',
            'Quantitative EEG Spectral Biomarkers in Acute Cerebrovascular Ischemia',
            'Mitochondrial Homeostasis and Synaptic Pruning During Adolescent Brain Maturation',
            'Vagus Nerve Stimulation Effects on Cortical Plasticity and Working Memory',
            'Frontotemporal Lobar Degeneration Functional Network De-synchronization',
            'Molecular Cascades of Tau Oligomer Propagation in Cortical Interneurons',
            'Electrophysiological Correlates of Cognitive Load in Intracranial Recording',
            'Neuroprotective Efficacy of Nanoparticle Drug Carriers Across the Blood-Brain Barrier',
            'Structural Gray Matter Volumetric Alterations in Chronic Sleep Deprivation',
            'High-Field 7T MRI Biomarkers of Perivascular Space Clearance Dynamics',
            'Dendritic Spine Morphometry Following Chronic Transcranial Magnetic Stimulation',
            'Single-Cell Transcriptomics of Cortical Astrocyte Heterogeneity in Neuroinflammation',
            'Sensory-Motor Integration Deficits in Post-Concussive Encephalopathy',
            'Targeted Neuromodulation of the Default Mode Network in Cognitive Decline',
            'Glyphosate-Induced Oxidative Stress and Glial Phenotype Plasticity',
            'Amygdala-Prefrontal Functional Connectivity During Threat Appraisal',
            'Longitudinal Tractography of White Matter Microstructural Integrity',
            'Spontaneous Neural Avalanches and Criticality in Primary Auditory Cortex',
            'Optogenetic Control of Dopamine Release During Goal-Directed Navigational Tasks',
            'Proteomic Signatures of Cerebrospinal Fluid in Amyotrophic Lateral Sclerosis',
            'Prefrontal Beta Band Oscillations as Real-Time Predictors of Inhibitory Control',
            'Spinal Cord Circuit Remodeling Following Epidural Electrical Stimulation',
            'Circadian Clock Gene Expression in Human Cerebral Organoid Models',
            'High-Density Surface Electromyography Decoding of Distal Forelimb Movements',
            'Neuroinflammatory Cytokine Signatures in Post-Viral Cognitive Dysfunctions',
            'Calcium Imaging of Population Dynamics in Hippocampal CA1 Place Cells',
            'Automated AI-Driven Lesion Segmentation in Multiple Sclerosis MR Imaging',
            'Therapeutic Window for Hypothermia-Induced Neuroprotection in Neonatal Hypoxia',
            'Glutamate Receptor Trafficking Alterations in Traumatic Axonal Injury',
            'Non-Invasive Optoacoustic Tomography of Cerebral Microvascular Oxygenation',
            'Functional Ultrasound Neuroimaging of Deep Brain Structures During Seizure Propagation',
            'Mechanisms of Ketogenic Metabolic Interventions on Neuronal Excitability',
            'Synaptic Density Mapping with PET Tracers in Early Neurocognitive Impairment',
            'Preclinical Evaluation of Antisense Oligonucleotides for Spinal Muscular Atrophy',
            'Transcranial Ultrasound Neuromodulation of Subcortical Limbic Circuits',
            'Functional Decoupling of Salience and Executive Networks in Cognitive Aging',
            'Microscopic Mapping of Perineuronal Nets in Cortical Plasticity Re-opening',
        ];
    }

    protected function getPsychologyTitles(): array
    {
        return [
            'Cognitive Behavioral Interventions for Refractory Adolescent Anxiety',
            'Longitudinal Trajectories of Psychological Resilience in Trauma-Exposed Cohorts',
            'Metacognitive Protocols in Symptom Mitigation of Schizotypal Spectrum Disorders',
            'Executive Function Profiles Across Developmental Attention-Deficit Presentations',
            'Interpersonal Emotion Regulation Strategies and Relational Well-Being Outcomes',
            'Neurofeedback-Augmented Psychotherapy for Complex Post-Traumatic Stress Disorder',
            'Circadian Rhythm Entrainment and Affective Variability in Bipolar Mood Cycles',
            'Attachment Dimensions as Mediators of Therapeutic Alliance in Depressive Care',
            'Somatic Experiencing and Autonomic Modulation in Chronic Anxiety Conditions',
            'Psychometric Validation of Novel Digital Clinical Scales for Mood Assessment',
            'Mindfulness-Based Stress Reduction Impacts on Attentional Control and Burnout',
            'Cognitive Flexibility Deficits in Treatment-Resistant Obsessive-Compulsive Disorder',
            'Psychosocial Predictors of Recovery Following First-Episode Psychosis',
            'Social Cognition and Facial Emotion Decoding Across Borderline Personality Clusters',
            'Efficacy of Internet-Delivered Unified Protocol for Transdiagnostic Emotional Disorders',
            'Behavioral Activation Protocols in Geriatric Depression Comorbid with Chronic Illness',
            'Acceptance and Commitment Therapy for Intractable Chronic Pain Catastrophizing',
            'Epigenetic Correlates of Childhood Adversity and Adult Stress Reactivity',
            'Parent-Child Coregulation Dynamics and Emotional Dysregulation in Early Childhood',
            'Cognitive Bias Modification Protocols for Social Phobia and Evaluative Threat',
            'The Role of Self-Compassion in Attenuating Perfectionism-Driven Suicidal Ideation',
            'Ecological Momentary Assessment of Craving Dynamics in Alcohol Use Disorder',
            'Virtual Reality Exposure Therapy Efficacy for Panic Disorder with Agoraphobia',
            'Sleep Architecture Perturbations as Upstream Drivers of Generalized Anxiety',
            'Cross-Cultural Adaptations of Trauma-Focused CBT in Refugee Populations',
            'Dialectical Behavior Therapy Skills Training for Emotionally Impulsive Behaviors',
            'Neurocognitive Functioning in Remitted Major Depressive Disorder Under Maintenance',
            'Resilience Factors Protecting Against Compassion Fatigue in Emergency Responders',
            'Intergenerational Transmission of Trauma Correlates in Community Samples',
            'Brief Guided Imagery Protocols for Acute Procedural Anxiety Reduction',
            'Decoupling Intrusive Thoughts via Schema Therapy Interventions in OCD',
            'Predictive Utility of Heart Rate Variability Biofeedback in Panic Vulnerability',
            'Impact of Social Media Displacement on Adolescent Affective Symptom Burden',
            'Grit, Conscientiousness, and Coping Trajectories in Post-Disaster Reconstruction',
            'Psychological Flexibility as a Moderator of Stress in Healthcare Shift Workers',
            'Validation of the Multidimensional Sense of Belonging Scale in High-Risk Youth',
            'Narrative Exposure Therapy for Severe Psychological Polytrauma Recovery',
            'Attentional Bias Toward Threat in Remitted Depression: A Pupillometry Study',
            'Integrative Body-Mind Training for Enhancing Executive Working Memory Capacity',
            'The Mediating Role of Experiential Avoidance in Health-Related Anxiety',
            'Long-Term Efficacy of Cognitive Behavioral Therapy for Chronic Insomnia (CBT-I)',
            'Empathy Erosion in High-Stress Professional Environments: Structural Predictors',
            'Neuropsychological Correlates of Anhedonia in Major Mood Syndromes',
            'Validation of Brief Screening Tools for Perinatal Anxiety in Primary Care',
            'Cognitive Defusion Techniques for Managing Repetitive Negative Thinking',
            'Substance Recovery Self-Efficacy and Social Network Topology Over 24 Months',
            'Therapeutic Factors in Group Psychotherapy for Complicated Grief and Loss',
            'Emotion Differentiation and Non-Suicidal Self-Injury in Young Adult Cohorts',
            'Peer Support Intervention Outcomes for Individuals with Chronic Psychiatric Illness',
            'Psychological Predictors of Adherence to Long-Term Preventative Regimens',
        ];
    }

    protected function getNeuroscienceAbstracts(): array
    {
        return [
            'This investigation explores the physiological mechanisms and circuit-level neurobiological dynamics underlying neural computation. Using high-resolution recording paradigms, longitudinal imaging, and cellular biomarker assays, we characterized connectivity alterations and structural remodeling across targeted clinical populations. Our findings demonstrate robust modulation of oscillatory synchronization and identify specific molecular pathways that facilitate synaptic plasticity and long-term network recovery.',
            'Using multimodal neuroimaging combining resting-state functional MRI with diffusion tensor tractography, this research characterizes white-matter microstructural integrity and functional connectivity alterations. Longitudinal observation revealed significant associations between regional circuit reorganization and executive performance preservation across study participants.',
            'Electrophysiological investigation of oscillatory coherence and synaptic transmission was conducted using intracranial microelectrode arrays. Spectral power density analysis demonstrated significant theta-gamma phase-amplitude coupling during memory retrieval, suggesting a coordinated network architecture that governs adaptive neural representation.',
            'We investigated the cellular cascade of neuroinflammation and glial phenotype polarization across translational preclinical models. Pharmacological targeted interventions demonstrated substantial down-regulation of pro-inflammatory cytokine expression accompanied by accelerated axonal preservation and motor recovery.',
            'Through high-density optical topography and event-related potential recordings, this study evaluated the neurovascular coupling dynamics during cognitive task transitions. Quantitative hemodynamic response modeling identified distinct spatial clusters associated with rapid executive modulation and attentional stability.',
        ];
    }

    protected function getPsychologyAbstracts(): array
    {
        return [
            'This peer-reviewed paper examines clinical and empirical psychological paradigms across diverse patient cohorts. Utilizing controlled longitudinal assessments, multi-method psychological metrics, and validated diagnostic inventories, we evaluated treatment efficacy, cognitive appraisal adaptations, and behavioral regulation trajectories. The results highlight statistically significant interventions for mitigating symptom severity and enhancing sustained psychological resilience.',
            'Through a multi-center randomized controlled evaluation, we analyzed the comparative efficacy of targeted psychotherapy protocols versus standard behavioral management. Standardized psychometric evaluations at baseline, post-intervention, and six-month follow-up revealed marked reductions in symptom severity and durable gains in functional quality of life.',
            'Utilizing ecological momentary assessment (EMA) over a 12-week observational period, this investigation mapped real-time affective variability, stress reactivity, and adaptive coping strategy deployment. Structural equation modeling established meaningful pathways linking cognitive flexibility with emotional equilibrium under environmental stressors.',
            'This clinical study evaluates the interaction between cognitive appraisal biases, autonomic nervous system modulation, and subjective distress across cohorts. Psychophysiological recordings revealed that structured regulatory interventions induced significant improvements in parasympathetic tone and subjective coping confidence.',
            'We investigated psychosocial determinants of therapeutic engagement and sustained recovery across longitudinal cohorts. Multivariate regression analysis confirmed that therapeutic alliance strength and adaptive cognitive reappraisal strategies were principal mediators of sustained psychological well-being.',
        ];
    }
}
