<?php

return [
    // Emergency deploy endpoint for hosting without SSH.
    // Keep disabled by default and enable only during deployment.
    'cache_warm_endpoint_enabled' => (bool) env('DEPLOY_CACHE_WARM_ENABLED', false),

    // Generate a long random token (at least 32 chars) before enabling endpoint.
    'cache_warm_token' => (string) env('DEPLOY_CACHE_WARM_TOKEN', ''),

    // GET is easier from browser. Set false to enforce POST-only calls.
    'cache_warm_allow_get' => (bool) env('DEPLOY_CACHE_WARM_ALLOW_GET', true),

    // Route cache is optional. Keep false if your route files still contain closures.
    'cache_warm_include_route' => (bool) env('DEPLOY_CACHE_WARM_INCLUDE_ROUTE', false),

    // Lock file prevents re-execution after successful run.
    'cache_warm_lock_file' => storage_path('app/deploy/cache_warm.lock'),
];
