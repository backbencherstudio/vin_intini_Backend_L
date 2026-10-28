<?php

return [
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://mindunite.com',
        'https://www.mindunite.com',
        'https://mindunit.netlify.app',
        'https://vin-intini-web-frontend.vercel.app',
        'https://mindunite.pixelstack.cloud',
        'http://localhost:3000',
        'https://cleared-month-virgin-murphy.trycloudflare.com'
    ],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,
];
