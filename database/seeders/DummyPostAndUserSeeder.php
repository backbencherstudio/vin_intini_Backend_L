<?php

namespace Database\Seeders;

use App\Models\Connection;
use App\Models\Post;
use App\Models\PostLike;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class DummyPostAndUserSeeder extends Seeder
{
    public function run(): void
    {
        $userRole = Role::firstOrCreate(['name' => 'user', 'guard_name' => 'api']);

        $dummyUsers = [
            [
                'first_name' => 'Sarah',
                'last_name' => 'Jenkins',
                'username' => 'sarah-jenkins',
                'email' => 'sarah.jenkins@mindunite.com',
                'password' => Hash::make('password123'),
                'is_verified' => 1,
                'email_verified_at' => now(),
                'profile' => [
                    'country' => 'United States',
                    'postal_code' => '94305',
                    'profession' => ['Neuroscientist', 'Brain Mapping Specialist'],
                    'highest_degree' => 'PhD',
                    'study_category' => 'Neuroscience',
                    'field_study' => 'Cognitive & Systems Neuroscience',
                    'institution' => 'Stanford University',
                    'graduation_year' => '2019',
                    'interests' => ['Optogenetics', 'Cortical Mapping', 'Neural Circuits'],
                    'about' => 'Senior Neurobiology researcher at Stanford. Focusing on mapping cortical circuitry and functional connectivity in sensory processing.',
                    'notify_jobs' => true,
                    'notify_publications' => true,
                    'notify_residency' => false,
                    'notify_offers' => true,
                ],
                'posts' => [
                    "Excited to share our team's recent preprint on high-resolution cortical mapping using optogenetics! We observed unexpected functional divergence in sensory processing pathways. Would love to hear feedback and discuss implications with fellow researchers in the neuroscience community! 🧠🔬\n\n#Neuroscience #BrainMapping #Optogenetics #Research",
                    "A reminder to early-career researchers: High-throughput imaging generates enormous data, but clarity of hypothesis is what transforms noise into insight. Quality always precedes quantity in scientific inquiry.",
                ],
            ],
            [
                'first_name' => 'Alex',
                'last_name' => 'Rivera',
                'username' => 'alex-rivera',
                'email' => 'alex.rivera@mindunite.com',
                'password' => Hash::make('password123'),
                'is_verified' => 1,
                'email_verified_at' => now(),
                'profile' => [
                    'country' => 'United Kingdom',
                    'postal_code' => 'OX1 2JD',
                    'profession' => ['Clinical Psychologist', 'Cognitive Behavioral Researcher'],
                    'highest_degree' => 'PsyD',
                    'study_category' => 'Psychology',
                    'field_study' => 'Clinical Cognitive Psychology',
                    'institution' => 'University of Oxford',
                    'graduation_year' => '2021',
                    'interests' => ['Cognitive Behavioral Therapy', 'Anxiety Treatment', 'Mental Health Protocols'],
                    'about' => 'Practicing psychologist and behavioral researcher exploring acute stress intervention mechanisms and evidence-based clinical protocols.',
                    'notify_jobs' => true,
                    'notify_publications' => true,
                    'notify_residency' => true,
                    'notify_offers' => true,
                ],
                'posts' => [
                    "Reframing cognitive distortions in acute stress scenarios: A clinical summary from our recent trial. Early intervention protocols showed a notable 34% reduction in recurring panic episodes across participants. Key findings and methodology summary attached below! 📝✨\n\n#Psychology #MentalHealth #ClinicalResearch #CBT",
                ],
            ],
            [
                'first_name' => 'Elena',
                'last_name' => 'Rostova',
                'username' => 'elena-rostova',
                'email' => 'elena.rostova@mindunite.com',
                'password' => Hash::make('password123'),
                'is_verified' => 1,
                'email_verified_at' => now(),
                'profile' => [
                    'country' => 'United States',
                    'postal_code' => '21218',
                    'profession' => ['Neuropharmacologist', 'Biotechnology Lead'],
                    'highest_degree' => 'MD/PhD',
                    'study_category' => 'Neuroscience',
                    'field_study' => 'Molecular Neuropharmacology',
                    'institution' => 'Johns Hopkins University',
                    'graduation_year' => '2018',
                    'interests' => ['Neurodegenerative Diseases', 'Drug Discovery', 'Peptide Therapeutics'],
                    'about' => 'Translational neuroscientist leading preclinical drug development targeting microglial activation and neuroinflammatory cascades.',
                    'notify_jobs' => true,
                    'notify_publications' => true,
                    'notify_residency' => false,
                    'notify_offers' => true,
                ],
                'posts' => [
                    "Our phase-II preclinical investigation on novel neuroprotective peptide conjugates has concluded with exceptionally strong efficacy signals across neuroinflammatory markers. Thrilled to collaborate with our clinical partners as we prepare for human trials next quarter! 💊🧬\n\n#Biotechnology #DrugDiscovery #Neuropharmacology #Innovation",
                ],
            ],
            [
                'first_name' => 'Marcus',
                'last_name' => 'Thorne',
                'username' => 'marcus-thorne',
                'email' => 'marcus.thorne@mindunite.com',
                'password' => Hash::make('password123'),
                'is_verified' => 1,
                'email_verified_at' => now(),
                'profile' => [
                    'country' => 'United States',
                    'postal_code' => '10027',
                    'profession' => ['Behavioral Health Specialist', 'Academic Lecturer'],
                    'highest_degree' => 'MS',
                    'study_category' => 'Psychology',
                    'field_study' => 'Behavioral Health & Counseling',
                    'institution' => 'Columbia University',
                    'graduation_year' => '2020',
                    'interests' => ['Public Health', 'Adolescent Psychology', 'Academic Mentorship'],
                    'about' => 'Academic lecturer and community mental health advocate bridging the gap between empirical psychological science and clinical frontline practice.',
                    'notify_jobs' => true,
                    'notify_publications' => false,
                    'notify_residency' => false,
                    'notify_offers' => true,
                ],
                'posts' => [
                    "A message to graduate researchers and students submitting their first journal papers this month: Scientific resilience is as vital as experimental design. Every reviewer critique is valuable signal. Keep pushing your boundaries! 🎓💡\n\n#Academia #Mentorship #ScienceCommunity #MentalHealth",
                ],
            ],
            [
                'first_name' => 'David',
                'last_name' => 'Chen',
                'username' => 'david-chen',
                'email' => 'david.chen@mindunite.com',
                'password' => Hash::make('password123'),
                'is_verified' => 1,
                'email_verified_at' => now(),
                'profile' => [
                    'country' => 'United States',
                    'postal_code' => '02139',
                    'profession' => ['Neural Network Architect', 'Computational Biologist'],
                    'highest_degree' => 'PhD',
                    'study_category' => 'Neuroscience',
                    'field_study' => 'Computational Neuroscience & AI',
                    'institution' => 'Massachusetts Institute of Technology',
                    'graduation_year' => '2022',
                    'interests' => ['Deep Learning', 'Neural Decoding', 'Brain-Computer Interfaces'],
                    'about' => 'Research scientist working at the intersection of deep learning architectures and mammalian cortical modeling.',
                    'notify_jobs' => true,
                    'notify_publications' => true,
                    'notify_residency' => false,
                    'notify_offers' => true,
                ],
                'posts' => [
                    "Can biological synapses truly be approximated by static weight matrices? Presenting our theoretical framework on dynamic neuromodulation in artificial recurrent networks at the upcoming computational neuroscience symposium. Looking forward to engaging discussions! 🤖🧠\n\n#ArtificialIntelligence #ComputationalNeuroscience #DeepLearning #Research",
                ],
            ],
        ];

        $createdUsers = [];
        $createdPosts = [];

        foreach ($dummyUsers as $data) {
            $user = User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'first_name' => $data['first_name'],
                    'last_name' => $data['last_name'],
                    'username' => $data['username'],
                    'password' => $data['password'],
                    'is_verified' => $data['is_verified'],
                    'email_verified_at' => $data['email_verified_at'],
                ]
            );

            $user->assignRole($userRole);

            UserProfile::updateOrCreate(
                ['user_id' => $user->id],
                $data['profile']
            );

            $createdUsers[] = $user;

            foreach ($data['posts'] as $postText) {
                $post = Post::updateOrCreate(
                    [
                        'user_id' => $user->id,
                        'description' => $postText,
                    ],
                    [
                        'visibility' => 'public',
                        'who_can_comment' => 'anyone',
                        'total_like' => 0,
                        'total_comment' => 0,
                    ]
                );

                $createdPosts[] = $post;
            }
        }

        // Add some reciprocal connections and likes between the dummy users
        for ($i = 0; $i < count($createdUsers); $i++) {
            $sender = $createdUsers[$i];
            $receiver = $createdUsers[($i + 1) % count($createdUsers)];

            Connection::updateOrCreate(
                ['sender_id' => $sender->id, 'receiver_id' => $receiver->id],
                ['status' => Connection::STATUS_ACCEPTED]
            );
        }

        // Add likes across posts
        foreach ($createdPosts as $post) {
            foreach ($createdUsers as $liker) {
                if ($liker->id !== $post->user_id && random_int(0, 1) === 1) {
                    PostLike::firstOrCreate([
                        'post_id' => $post->id,
                        'user_id' => $liker->id,
                    ]);
                }
            }

            $post->update(['total_like' => $post->likes()->count()]);
        }
    }
}
