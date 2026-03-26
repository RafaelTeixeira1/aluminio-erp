<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
    public function boot(): void
    {
        RateLimiter::for('api-auth', function (Request $request) {
            $userId = $request->user()?->id;
            $key = $userId !== null ? 'user:'.$userId : 'ip:'.$request->ip();

            return Limit::perMinute(180)->by($key);
        });

        RateLimiter::for('api-login', function (Request $request) {
            $key = strtolower((string) $request->input('email', '')).'|'.$request->ip();

            return Limit::perMinute(10)->by($key);
        });
    }
}
