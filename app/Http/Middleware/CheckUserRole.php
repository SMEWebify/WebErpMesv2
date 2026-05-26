<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->roles()->count() === 0) {
            if (Route::has('setup.index')) {
                return redirect()->route('setup.index');
            }

            abort(403, 'No role assigned.');
        }

        return $next($request);
    }
}
