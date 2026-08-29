<?php

return [
    /*
    |--------------------------------------------------------------------------
    | RevenueCat API Configuration
    |--------------------------------------------------------------------------
    */

    'api_key' => env('REVENUECAT_API_KEY'),
    'project_id' => env('REVENUECAT_PROJECT_ID'),
    'app_id' => env('REVENUECAT_APP_ID'),
    'app_id_ios' => env('REVENUECAT_APP_ID_IOS'),
    'app_id_android' => env('REVENUECAT_APP_ID_ANDROID'),
    'base_url' => env('REVENUECAT_API_BASE_URL', 'https://api.revenuecat.com'),

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    */

    'webhook_secret' => env('REVENUECAT_WEBHOOK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | App User ID
    |--------------------------------------------------------------------------
    |
    | When the mobile app identifies a RevenueCat customer, it should use the
    | application user id configured below so webhooks can be mapped back to
    | our local users. Supported strategies:
    |
    | - 'user_id'  : use the primary key of the local User model
    | - 'email'    : use the user's email address
    |
    */

    'app_user_id_strategy' => env('REVENUECAT_APP_USER_ID_STRATEGY', 'user_id'),
];
