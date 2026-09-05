<?php

namespace App\Providers;

use App\Services\IntegrationSettingsService;
use App\Services\RevenueCatPlanSyncService;
use App\Services\RevenueCatService;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\ServiceProvider;

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
    }
}
