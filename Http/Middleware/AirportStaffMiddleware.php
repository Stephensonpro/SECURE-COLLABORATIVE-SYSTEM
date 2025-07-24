<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AirportStaffMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->isAirportStaff()) {
            return $next($request);
        }

        abort(403, 'Unauthorized access - Airport staff only');
    }
}
