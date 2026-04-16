<?php

namespace App\Providers;

use App\Services\CryptoService;
use App\Services\KeyringService;
use App\Services\LegacyCryptoService;
use App\Services\SodiumCryptoService;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(KeyringService::class, function () {
            return new KeyringService();
        });

        $this->app->singleton(LegacyCryptoService::class, function ($app) {
            return new LegacyCryptoService($app->make(KeyringService::class));
        });

        $this->app->singleton(SodiumCryptoService::class, function ($app) {
            return new SodiumCryptoService($app->make(KeyringService::class));
        });

        $this->app->singleton(CryptoService::class, function ($app) {
            return new CryptoService(
                $app->make(SodiumCryptoService::class),
            );
        });
    }

    public function boot(): void
    {
        // Login: keyed by IP + username to prevent both brute-force and
        // credential stuffing across accounts from the same IP.
        RateLimiter::for('auth.login', function (Request $request) {
            $key = $request->ip() . '|' . strtolower((string) $request->input('username', ''));
            return Limit::perMinute(5)->by($key);
        });

        // Sensitive one-shot flows (password reset, OTP deactivation):
        // 5 attempts per hour per IP.
        RateLimiter::for('auth.sensitive', function (Request $request) {
            return Limit::perHour(5)->by($request->ip());
        });

        // Registration: 10 per hour per IP.
        RateLimiter::for('auth.register', function (Request $request) {
            return Limit::perHour(10)->by($request->ip());
        });

        // General app API: 60 requests / min, keyed by user ID when
        // authenticated, falling back to IP for unauthenticated endpoints.
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
