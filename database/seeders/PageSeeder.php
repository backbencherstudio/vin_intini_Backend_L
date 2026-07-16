<?php

namespace Database\Seeders;

use App\Models\Page;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PageSeeder extends Seeder
{
    public function run(): void
    {
        $pages = [
            [
                'title' => 'About Us',
                'slug'  => 'about-us',
                'content' => 'At Mind Unite, we attract Brain Health professionals and partner with Biotech, Psychotropic, and Publication Companies to create an environment for collaboration, resource optimization, and opportunity sharing in one location.',
                'vision'  => 'To create and grow a brain health professionals networking platform into a global collaboration and resource sharing enterprise.',
                'mission' => 'To connect current and future brain health professionals to information, resources, and opportunities.',
                'strategy' => 'Together we will build an online collaboration platform that will be used exclusively by current and future brain health professionals, and those industry partners who empower them, into the global networking platform in which users can thrive.',
                'what_we_do_image' => null,
                'team_members' => [
                    [
                        'name' => 'Vanni Intini',
                        'title' => 'Founder & CEO',
                        'bio' => 'Vanni is an undergraduate student at the University of North Carolina Chapel Hill Double Majoring in Psychology and Neuroscience.',
                        'photo' => null
                    ],
                    [
                        'name' => 'Macy Johnson',
                        'title' => 'Co-Founder',
                        'bio' => 'An undergraduate student passionate about brain health and networking platforms.',
                        'photo' => null
                    ]
                ],
                'features_videos' => [
                    [
                        'title' => 'Tutorial 1',
                        'source' => 'url',
                        'url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                        'path' => null
                    ],
                    [
                        'title' => 'Tutorial 2',
                        'source' => 'url',
                        'url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                        'path' => null
                    ]
                ],
            ],
            [
                'title' => 'Privacy Policy',
                'slug'  => 'privacy-policy',
                'content' => '<h3>Privacy Policy</h3><p>This is the default privacy policy content. We respect your data and privacy.</p>',
                'vision' => null,
                'mission' => null,
                'strategy' => null,
                'team_members' => null,
                'features_videos' => null,
            ],
            [
                'title' => 'Terms & Conditions',
                'slug'  => 'terms-and-conditions',
                'content' => '<h3>Terms and Conditions</h3><p>By using our website, you agree to these terms. Please read carefully.</p>',
                'vision' => null,
                'mission' => null,
                'strategy' => null,
                'team_members' => null,
                'features_videos' => null,
            ],
            [
                'title' => 'Guidelines',
                'slug'  => 'how-to-use',
                'content' => 'Follow these instructions to use the platform effectively.',
                'vision' => null,
                'mission' => null,
                'strategy' => null,
                'team_members' => null,
                'features_videos' => [
                    [
                        'title' => 'How to Login',
                        'source' => 'url',
                        'url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                        'path' => null
                    ],
                    [
                        'title' => 'How to Post',
                        'source' => 'url',
                        'url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                        'path' => null
                    ]
                ],
            ],
        ];

        foreach ($pages as $page) {
            Page::updateOrCreate(
                ['slug' => $page['slug']],
                $page
            );
        }
    }
}
