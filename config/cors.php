<?php

declare(strict_types=1);

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://mindunite.com',
        'https://www.mindunite.com',
        'https://mindunit.netlify.app',
        'https://vin-intini-web-frontend.vercel.app',
        'https://mindunite.pixelstack.cloud',
        'https://mindunite.softvenceomegaforce.cloud',
        'https://vini.softvenceomegaforce.cloud',
        'http://localhost:3000',
        'https://cleared-month-virgin-murphy.trycloudflare.com',
    ],

    'allowed_origins_patterns' => [
        '#^https?://.*\.softvenceomegaforce\.cloud$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['Content-Disposition'],

    'max_age' => 86400,

    'supports_credentials' => true,
];
