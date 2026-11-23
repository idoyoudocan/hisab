<?php

namespace App\Http\Middleware;

use App\Support\Hisab;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Disabled businesses cannot use the shop app: a logged-in shopkeeper
 * whose account the admin disabled is sent to the "account disabled"
 * page (which shows the admin's reason). Admins are never blocked —
 * otherwise the owner could lock themselves out of /admin — and the
 * disabled page + logout stay reachable so nobody gets stuck in a loop.
 */
class EnsureBusinessActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user !== null && ! Hisab::isAdmin($user)) {
            $business = $user->business;
            if ($business !== null && $business->isDisabled()
                && ! $request->routeIs('account.disabled', 'logout')) {
                return redirect()->route('account.disabled');
            }
        }

        return $next($request);
    }
}
