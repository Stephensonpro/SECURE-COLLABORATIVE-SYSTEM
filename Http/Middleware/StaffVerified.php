<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class StaffVerified
{
    public function handle(Request $request, Closure $next)
{
    $staff = auth()->guard('staff')->user();

    if (!$staff) {
        return redirect()->route('staff.login');
    }

    // If password hasn't been changed yet
    if ($staff->password_changed_at === null) {
        return redirect()->route('staff.password.change');
    }

    // If email hasn't been verified yet
    if (!$staff->email_verified_at) {
        // Check if we're already on the verification page
        if ($request->routeIs('staff.verify*')) {
            return $next($request);
        }
        return redirect()->route('staff.verify');
    }

    return $next($request);
}
}
