<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EnsureCustomerIsAuthenticated
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        if (Auth::guard('customer')->check()) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            abort(401, 'Unauthenticated.');
        }

        return redirect()->guest(route('customer.login'));
    }
}
