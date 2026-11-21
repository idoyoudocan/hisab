<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        $admins = config('hisab.admin_emails', []);

        if ($user === null || ! in_array(strtolower($user->email), array_map('strtolower', $admins), true)) {
            abort(403, 'এই পাতাটি শুধু হিসাবের মালিকের জন্য।');
        }

        return $next($request);
    }
}
