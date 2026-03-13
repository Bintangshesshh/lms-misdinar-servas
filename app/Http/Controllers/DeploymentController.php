<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DeploymentController extends Controller
{
    /**
     * One-time cache warm endpoint for hosting without SSH.
     * Protected by env flag + secret token + lock file.
     */
    public function cacheWarm(Request $request): JsonResponse
    {
        if (!config('deploy.cache_warm_endpoint_enabled', false)) {
            abort(404);
        }

        if ($request->isMethod('get') && !config('deploy.cache_warm_allow_get', true)) {
            return response()->json([
                'ok' => false,
                'message' => 'GET method is disabled for this endpoint.',
            ], 405);
        }

        $expectedToken = (string) config('deploy.cache_warm_token', '');
        $providedToken = (string) (
            $request->query('token')
            ?? $request->header('X-DEPLOY-TOKEN')
            ?? $request->input('token')
            ?? ''
        );

        if ($expectedToken === '' || $providedToken === '' || !hash_equals($expectedToken, $providedToken)) {
            return response()->json([
                'ok' => false,
                'message' => 'Invalid deployment token.',
            ], 403);
        }

        $lockFile = (string) config('deploy.cache_warm_lock_file', storage_path('app/deploy/cache_warm.lock'));
        if (is_file($lockFile)) {
            $usedAt = trim((string) @file_get_contents($lockFile));
            return response()->json([
                'ok' => false,
                'status' => 'locked',
                'message' => 'Endpoint already used and locked.',
                'used_at' => $usedAt !== '' ? $usedAt : null,
            ], 410);
        }

        $commands = ['config:cache', 'view:cache'];
        if ((bool) config('deploy.cache_warm_include_route', false)) {
            $commands[] = 'route:cache';
        }

        $results = [];
        $allSucceeded = true;

        foreach ($commands as $command) {
            try {
                $exitCode = Artisan::call($command);
                $output = trim(Artisan::output());
            } catch (\Throwable $e) {
                $exitCode = 1;
                $output = $e->getMessage();
            }

            if ($exitCode !== 0) {
                $allSucceeded = false;
            }

            $results[$command] = [
                'exit_code' => $exitCode,
                'output' => $output,
            ];
        }

        if ($allSucceeded) {
            $lockDir = dirname($lockFile);
            if (!is_dir($lockDir)) {
                @mkdir($lockDir, 0755, true);
            }
            @file_put_contents($lockFile, now()->toIso8601String());
        }

        return response()->json([
            'ok' => $allSucceeded,
            'status' => $allSucceeded ? 'locked_after_success' : 'partial_or_failed',
            'commands' => $results,
            'next_step' => $allSucceeded
                ? 'Set DEPLOY_CACHE_WARM_ENABLED=false in .env after deploy.'
                : 'Fix failed command output, rerun endpoint, then disable DEPLOY_CACHE_WARM_ENABLED.',
        ], $allSucceeded ? 200 : 500);
    }
}
