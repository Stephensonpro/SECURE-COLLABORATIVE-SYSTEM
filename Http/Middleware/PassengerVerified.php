<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class PassengerVerified
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user('passenger')->email_verified_at) {
            return redirect()->route('passenger.verify');
        }

        return $next($request);
    }
}
