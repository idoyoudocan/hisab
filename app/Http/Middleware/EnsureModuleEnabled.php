<?php

namespace App\Http\Middleware;

use App\Support\Hisab;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleEnabled
{
    /**
     * When the module is off, show a friendly notice with a link to
     * Settings instead of a 404 or a blank page.
     */
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $business = Hisab::business();

        if ($business !== null && ! $business->moduleEnabled($module)) {
            return response()->view('partials.module-disabled', [
                'moduleLabel' => \App\Models\Business::MODULES[$module] ?? 'এই ফিচার',
            ], 200);
        }

        return $next($request);
    }
}
