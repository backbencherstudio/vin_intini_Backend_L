<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Free New Release Boost Duration (Hours)
    |--------------------------------------------------------------------------
    |
    | When an industry creates a new product, it automatically receives
    | free New Release priority visibility for this number of hours.
    | Set to 0 to disable the free new release boost.
    |
    */
    'new_release_hours' => (int) env('NEW_RELEASE_DURATION_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Custom Duration Boost Slider Configuration
    |--------------------------------------------------------------------------
    |
    | Pricing and duration constraints for advertiser-controlled custom
    | boosting duration sliders.
    |
    */
    'custom_slider' => [
        'enabled' => true,
        'channel' => 'web_only', // Custom duration slider is available for Web checkout (Stripe) only
        'daily_rate' => (float) env('BOOST_CUSTOM_DAILY_RATE', 3.00),
        'currency' => env('BOOST_CURRENCY', 'USD'),
        'min_days' => (int) env('BOOST_MIN_DAYS', 3),
        'max_days' => (int) env('BOOST_MAX_DAYS', 60),
        'daily_estimated_impressions_min' => (int) env('BOOST_DAILY_IMPRESSIONS_MIN', 700),
        'daily_estimated_impressions_max' => (int) env('BOOST_DAILY_IMPRESSIONS_MAX', 1400),
    ],

    /*
    |--------------------------------------------------------------------------
    | Campaign Pause & Freeze Policy (Days)
    |--------------------------------------------------------------------------
    |
    | Defines the maximum number of consecutive days a campaign may remain paused
    | before automatically expiring, along with proactive email warning thresholds.
    |
    */
    'pause_policy' => [
        'max_days' => (int) env('BOOST_MAX_PAUSE_DAYS', 30),
        'first_reminder_days' => (int) env('BOOST_PAUSE_FIRST_REMINDER_DAYS', 27),
        'final_reminder_days' => (int) env('BOOST_PAUSE_FINAL_REMINDER_DAYS', 29),
    ],

    /*
    |--------------------------------------------------------------------------
    | Newsfeed Sponsored Ad Injection Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for balancing user experience (anti-fatigue) and advertiser
    | impression fairness in the main social newsfeed timeline.
    |
    */
    'newsfeed_ads' => [
        'enabled' => (bool) env('BOOST_NEWSFEED_ADS_ENABLED', true),
        'min_posts_before_ad' => (int) env('BOOST_NEWSFEED_MIN_POSTS', 4),
        'first_ad_slot' => (int) env('BOOST_NEWSFEED_FIRST_SLOT', 4),
        'ad_interval' => (int) env('BOOST_NEWSFEED_INTERVAL', 8),
        'max_ads_per_page' => (int) env('BOOST_NEWSFEED_MAX_ADS_PER_PAGE', 2),
        'max_daily_impressions_per_user' => (int) env('BOOST_MAX_USER_DAILY_IMPRESSIONS', 5),
    ],
];
