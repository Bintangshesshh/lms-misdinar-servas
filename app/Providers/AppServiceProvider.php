<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;

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
        // ========================================
        // Dynamic URL detection for phone/LAN access
        // ========================================
        // When accessed via IP (e.g. 192.168.1.9/LMS-Misdinar/public/),
        // Laravel's route() generates URLs based on APP_URL (lms-misdinar.test)
        // which phones can't resolve. This forces the correct base URL.
        $request = request();
        $host = $request->getHost();
        $appHost = parse_url(config('app.url'), PHP_URL_HOST);

        if ($host && $appHost && $host !== $appHost) {
            // Detect if running under a subdirectory (e.g. /LMS-Misdinar/public)
            $scriptName = $request->server('SCRIPT_NAME', '');
            $basePath = dirname($scriptName);
            if ($basePath === '\\' || $basePath === '/') {
                $basePath = '';
            }
            $rootUrl = $request->getSchemeAndHttpHost() . $basePath;
            URL::forceRootUrl($rootUrl);
        }

        // Configure rate limiters - very generous for development/testing
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(500)->by($request->user()?->id ?: $request->ip());
        });

        RateLimiter::for('web', function (Request $request) {
            return Limit::perMinute(500)->by($request->user()?->id ?: $request->ip());
        });

        // Force HTTPS only in production
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
