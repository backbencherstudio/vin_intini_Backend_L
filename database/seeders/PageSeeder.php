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

                'founder_info' => [
                    'name'        => 'Vanni Intini',
                    'designation' => 'Founder & CEO of Mind Unite',
                    'bio'         => 'Hello, my name is Vanni Intini and I am the founder and CEO of Mind Unite. I created Mind Unite to bridge the gap between brain health professionals and industry partners.',
                    'photo'       => null,
                    'signature'   => 'Vanni Intini'
                ],

                'team_members' => [
                    [
                        'name' => 'Vanni Intini',
                        'title' => 'Founder & CEO',
                        'bio' => 'Vanni is an undergraduate student at UNC Chapel Hill.',
                        'photo' => null
                    ],
                    [
                        'name' => 'Macy Johnson',
                        'title' => 'Co-Founder',
                        'bio' => 'An undergraduate student passionate about brain health.',
                        'photo' => null
                    ]
                ],

                'features_videos' => [
                    [
                        'title'     => 'Introduction Video',
                        'source'    => 'url',
                        'url'       => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
                        'path'      => null,
                        'thumbnail' => null
                    ]
                ],

                'faqs' => [
                    [
                        'question' => 'What is Mind Unite?',
                        'answer'   => 'Mind Unite is a networking platform for brain health professionals.'
                    ]
                ],
            ],
            [
                'title' => 'Privacy Policy',
                'slug'  => 'privacy-policy',
                'content' => '<h3>Privacy Policy</h3><p>We respect your data and privacy.</p>',
                'founder_info' => null,
                'team_members' => null,
                'features_videos' => null,
                'faqs' => null,
            ],
            [
                'title' => 'Terms & Conditions',
                'slug'  => 'terms-and-conditions',
                'content' => '<h3>Terms and Conditions</h3><p>By using our website, you agree to these terms.</p>',
                'founder_info' => null,
                'team_members' => null,
                'features_videos' => null,
                'faqs' => null,
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
