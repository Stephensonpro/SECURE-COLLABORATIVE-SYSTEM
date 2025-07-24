<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfNotStaff
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!auth()->guard('staff')->check()) {
            return redirect()->route('staff.login');
        }

        return $next($request);
    }
}
