<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

/**
 * Secured deploy hook for hosts without shell access (e.g. cPanel).
 * POST /deploy/migrate with header X-Deploy-Token matching the
 * DEPLOY_TOKEN env value. If DEPLOY_TOKEN is empty, the route is 404.
 */
class DeployController extends Controller
{
    public function migrate(Request $request): JsonResponse
    {
        $token = (string) env('DEPLOY_TOKEN', '');

        if ($token === '' || ! hash_equals($token, (string) $request->header('X-Deploy-Token', ''))) {
            abort(404);
        }

        Artisan::call('migrate', ['--force' => true]);

        return response()->json([
            'ok' => true,
            'output' => Artisan::output(),
        ]);
    }
}
