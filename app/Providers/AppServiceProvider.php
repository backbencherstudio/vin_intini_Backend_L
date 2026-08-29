<?php

namespace App\Providers;

use App\Services\IntegrationSettingsService;
use Illuminate\Foundation\DevCommands;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(IntegrationSettingsService $settings): void
    {
        DevCommands::artisan('schedule:work', 'schedule');
        $settings->applyOverrides();
    }
}
