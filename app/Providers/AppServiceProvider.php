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

        // Configure rate limiters for concurrent exam traffic.
        // Keys prefer authenticated user id to avoid one-shared-IP bottlenecks.
        $limiterKey = function (Request $request): string {
            $userId = $request->user()?->id;
            return $userId ? 'u:' . $userId : 'ip:' . $request->ip();
        };

        // Keep API limiter high enough for 100+ concurrent students.
        RateLimiter::for('api', function (Request $request) use ($limiterKey) {
            return Limit::perMinute(1200)->by($limiterKey($request));
        });

        RateLimiter::for('web', function (Request $request) use ($limiterKey) {
            return Limit::perMinute(1200)->by($limiterKey($request));
        });

        // Student polling endpoint (status checks).
        RateLimiter::for('exam-poll', function (Request $request) use ($limiterKey) {
            return Limit::perMinute(90)->by($limiterKey($request));
        });

        // Auto-save answer endpoint.
        RateLimiter::for('exam-save-answer', function (Request $request) use ($limiterKey) {
            return Limit::perMinute(180)->by($limiterKey($request));
        });

        // Bulk auto-save endpoint.
        RateLimiter::for('exam-save-bulk', function (Request $request) use ($limiterKey) {
            return Limit::perMinute(120)->by($limiterKey($request));
        });

        // Integrity logging can spike on device/browser edge cases.
        RateLimiter::for('integrity-log', function (Request $request) use ($limiterKey) {
            return Limit::perMinute(120)->by($limiterKey($request));
        });

        // Admin monitor polling endpoint.
        RateLimiter::for('admin-monitor', function (Request $request) use ($limiterKey) {
            return Limit::perMinute(300)->by($limiterKey($request));
        });

        // Force HTTPS only in production
        if (app()->environment('production')) {
            URL::forceScheme('https');
        }
    }
}
