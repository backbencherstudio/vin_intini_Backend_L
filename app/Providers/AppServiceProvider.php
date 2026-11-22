<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Billing\RevenueCatService;
use App\Services\Settings\IntegrationSettingsService;
use App\Services\Socialite\AppleProvider;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\DevCommands;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(RevenueCatService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(IntegrationSettingsService $settings): void
    {
        DevCommands::artisan('serve', 'server');
        DevCommands::artisan('schedule:work', 'schedule');
        $settings->applyOverrides();

        Event::listen(SocialiteWasCalled::class, fn (SocialiteWasCalled $event) => $event->extendSocialite('apple', AppleProvider::class));

        // High-concurrency Rate Limiters (Per-IP bursting with strict per-email abuse prevention)
        RateLimiter::for('api-register', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(60)->by($request->ip())->response(function () {
                    return response()->json([
                        'status' => false,
                        'message' => 'Too many registration requests from this network. Please wait a minute and try again.',
                    ], 429);
                }),
                Limit::perMinute(5)->by($email ?: $request->ip())->response(function () {
                    return response()->json([
                        'status' => false,
                        'message' => 'Too many registration attempts for this email. Please wait a minute and try again.',
                    ], 429);
                }),
            ];
        });

        RateLimiter::for('api-resend-otp', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(30)->by($request->ip())->response(function () {
                    return response()->json([
                        'status' => false,
                        'message' => 'Too many OTP requests from this network. Please wait a minute and try again.',
                    ], 429);
                }),
                Limit::perMinute(3)->by($email ?: $request->ip())->response(function () {
                    return response()->json([
                        'status' => false,
                        'message' => 'Too many OTP resend attempts. Please wait a minute before requesting another code.',
                    ], 429);
                }),
            ];
        });

        RateLimiter::for('api-verify-otp', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(60)->by($request->ip())->response(function () {
                    return response()->json([
                        'status' => false,
                        'message' => 'Too many verification requests from this network. Please wait a moment and try again.',
                    ], 429);
                }),
                Limit::perMinute(10)->by($email ?: $request->ip())->response(function () {
                    return response()->json([
                        'status' => false,
                        'message' => 'Too many invalid attempts. Please wait a moment and try again.',
                    ], 429);
                }),
            ];
        });

        RateLimiter::for('api-login', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(60)->by($request->ip())->response(function () {
                    return response()->json([
                        'status' => false,
                        'message' => 'Too many login requests from this network. Please wait a minute and try again.',
                    ], 429);
                }),
                Limit::perMinute(10)->by($email ?: $request->ip())->response(function () {
                    return response()->json([
                        'status' => false,
                        'message' => 'Too many login attempts. Please wait a minute and try again.',
                    ], 429);
                }),
            ];
        });

        RateLimiter::for('api-send-otp', function (Request $request) {
            $email = strtolower(trim((string) $request->input('email')));

            return [
                Limit::perMinute(30)->by($request->ip())->response(function () {
                    return response()->json([
                        'status' => false,
                        'message' => 'Too many requests from this network. Please wait a minute and try again.',
                    ], 429);
                }),
                Limit::perMinute(5)->by($email ?: $request->ip())->response(function () {
                    return response()->json([
                        'status' => false,
                        'message' => 'Too many OTP requests for this account. Please wait a minute and try again.',
                    ], 429);
                }),
            ];
        });
    }
}
