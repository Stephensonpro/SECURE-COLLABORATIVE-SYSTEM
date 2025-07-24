<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    public function handle(Request $request, Closure $next, $permission)
    {
        if (Auth::guard('admin')->check() && Auth::guard('admin')->user()->can($permission)) {
            return $next($request);
        }

        abort(403, 'Unauthorized action.');
    }
}
