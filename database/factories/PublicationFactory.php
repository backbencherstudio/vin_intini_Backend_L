<?php

namespace Database\Factories;

use App\Models\Industry;
use App\Models\Publication;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Publication>
 */
class PublicationFactory extends Factory
{
    protected $model = Publication::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $network = fake()->randomElement(['psychology', 'neuroscience']);
        $title = $this->generateTitle($network);

        $authors = [];
        $count = fake()->numberBetween(1, 4);
        for ($i = 0; $i < $count; $i++) {
            $prefix = fake()->randomElement(['Dr. ', 'Prof. ', '']);
            $authors[] = $prefix.fake()->firstName().' '.fake()->lastName();
        }

        return [
            'creator_id' => User::factory(),
            'industry_id' => null,
            'network_type' => $network,
            'publication_type' => fake()->randomElement(['university', 'freelance', 'other']),
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
            'authors' => $authors,
            'abstract' => $this->generateAbstract($network),
            'website_url' => fake()->randomElement([
                'https://www.nature.com/articles/s41583-026-'.fake()->numberBetween(1000, 9999),
                'https://www.sciencedirect.com/science/article/pii/S'.fake()->numberBetween(10000000, 99999999),
                'https://www.frontiersin.org/articles/10.3389/fnins.2026.'.fake()->numberBetween(100000, 999999),
                'https://jamanetwork.com/journals/jamapsychiatry/fullarticle/'.fake()->numberBetween(1000000, 9999999),
                'https://www.cell.com/neuron/fulltext/S0896-6273(26)00'.fake()->numberBetween(100, 999).'-X',
                'https://pubmed.ncbi.nlm.nih.gov/'.fake()->numberBetween(32000000, 39999999).'/',
            ]),
            'attachment' => null,
            'information_confirmed' => true,
            'status' => 'active',
            'views_count' => fake()->numberBetween(15, 3500),
            'likes_count' => fake()->numberBetween(0, 320),
        ];
    }

    public function professional(?Industry $industry = null, ?User $creator = null): static
    {
        return $this->state(function (array $attributes) use ($industry, $creator) {
            $owner = $creator ?? ($industry?->creator ?? User::factory()->create());
            $company = $industry ?? Industry::factory()->create(['created_by' => $owner->id]);

            return [
                'industry_id' => $company->id,
                'creator_id' => $owner->id,
                'publication_type' => 'professional',
            ];
        });
    }

    public function peerReviewed(?User $creator = null, string $type = 'university'): static
    {
        return $this->state(fn (array $attributes) => [
            'industry_id' => null,
            'creator_id' => $creator?->id ?? User::factory(),
            'publication_type' => $type,
        ]);
    }

    public function neuroscience(): static
    {
        return $this->state(function (array $attributes) {
            $title = $this->generateTitle('neuroscience');

            return [
                'network_type' => 'neuroscience',
                'title' => $title,
                'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
                'abstract' => $this->generateAbstract('neuroscience'),
            ];
        });
    }

    public function psychology(): static
    {
        return $this->state(function (array $attributes) {
            $title = $this->generateTitle('psychology');

            return [
                'network_type' => 'psychology',
                'title' => $title,
                'slug' => Str::slug($title).'-'.Str::lower(Str::random(6)),
                'abstract' => $this->generateAbstract('psychology'),
            ];
        });
    }

    protected function generateTitle(string $network): string
    {
        if ($network === 'neuroscience') {
            $prefixes = [
                'Neural Oscillation Dynamics in',
                'Synaptic Plasticity and Functional Recovery in',
                'Cortical Microcircuit Reorganization During',
                'Biomarkers of Neuroinflammation in',
                'Dopaminergic Modulation of Striatal Circuits in',
                'Optogenetic Interrogation of Inhibitory Ensembles in',
                'Resting-State fMRI Connectivity Patterns in',
                'Axonal Regeneration Mechanisms Following',
                'Microglial Activation Cascades in',
                'Deep Brain Stimulation Modulation of',
                'Blood-Brain Barrier Integrity Alterations in',
                'Neuromorphic Decoding of Motor Intentions in',
            ];
            $topics = [
                'Prefrontal Executive Networks',
                'Hippocampal Memory Consolidation',
                'Early-Stage Amyloid and Tau Pathophysiology',
                'Ischemic Stroke Neurorehabilitation',
                'Refractory Epilepsy Seizure Onset Zones',
                'Chronic Traumatic Encephalopathy Models',
                'Subthalamic Nucleus Synchrony in Parkinsonian States',
                'Cerebellar-Frontal Information Transfer',
                'Spinal Motor Neuron Degeneration',
                'Pediatric Neurodevelopmental Divergence',
            ];
        } else {
            $prefixes = [
                'Cognitive Behavioral Interventions for Refractory',
                'Longitudinal Trajectories of Psychological Resilience Following',
                'Metacognitive Protocols in Symptom Mitigation of',
                'Executive Function Divergence Across Developmental',
                'Interpersonal Emotion Regulation Predictors of',
                'Neurofeedback-Augmented Psychotherapy for Severe',
                'Circadian Rhythm Entrainment and Affective Variability in',
                'Attachment Orientations as Moderators of Recovery in',
                'Somatic Experiencing and Autonomic Modulation in',
                'Psychometric Validation of Novel Clinical Scales for',
            ];
            $topics = [
                'Adolescent Anxiety and Depressive Phenotypes',
                'Acute and Chronic Psychological Trauma',
                'Bipolar Spectrum Affective Dynamics',
                'Adult Attention-Deficit Hyperactivity Presentation',
                'Borderline Personality Symptom Clusters',
                'Obsessive-Compulsive Ritual Compulsions',
                'Social Phobia and Avoidance Behavior',
                'Complex Grief and Bereavement Pathways',
                'Addictive Behavior and Craving Extinction',
                'Burnout Syndrome in Healthcare Professionals',
            ];
        }

        return fake()->randomElement($prefixes).' '.fake()->randomElement($topics);
    }

    protected function generateAbstract(string $network): string
    {
        if ($network === 'neuroscience') {
            return 'This study investigates the physiological mechanisms and circuit-level neurobiological dynamics underlying neural computation. Using high-resolution recording paradigms, longitudinal imaging, and cellular biomarker assays, we characterized connectivity alterations and structural remodeling across targeted populations. Our findings demonstrate robust modulation of oscillatory synchronization and identify specific molecular pathways that facilitate synaptic plasticity and long-term network recovery.';
        }

        return 'This peer-reviewed paper examines clinical and empirical psychological paradigms across diverse patient cohorts. Utilizing controlled longitudinal assessments, multi-method psychological metrics, and validated diagnostic inventories, we evaluated treatment efficacy, cognitive appraisal adaptations, and behavioral regulation trajectories. The results highlight statistically significant interventions for mitigating symptom severity and enhancing sustained resilience.';
    }
}
