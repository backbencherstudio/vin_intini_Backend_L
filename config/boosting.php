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
];
