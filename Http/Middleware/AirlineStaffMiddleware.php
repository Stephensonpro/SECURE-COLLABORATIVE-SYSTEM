<?php


namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AirlineStaffMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check() && Auth::user()->isAirlineStaff()) {
            return $next($request);
        }

        abort(403, 'Unauthorized access - Airline staff only');
    }
}
