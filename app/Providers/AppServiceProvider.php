<?php

namespace App\Providers;

use App\Services\IntegrationSettingsService;
use App\Services\RevenueCatPlanSyncService;
use App\Services\RevenueCatService;
use App\Services\Socialite\AppleProvider;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(RevenueCatService::class);
        $this->app->singleton(RevenueCatPlanSyncService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(IntegrationSettingsService $settings): void
    {
        DevCommands::artisan('serve', 'server');
        DevCommands::artisan('schedule:work', 'schedule');
        $settings->applyOverrides();

        Event::listen(SocialiteWasCalled::class, fn(SocialiteWasCalled $event) => $event->extendSocialite('apple', AppleProvider::class));
    }
}
