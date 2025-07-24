<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StaffPasswordChanged
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->user('staff')->password_changed_at === null) {
            return redirect()->route('staff.password.change');
        }

        return $next($request);
    }
}
