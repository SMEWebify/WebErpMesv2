<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use Symfony\Component\HttpFoundation\Response;

class CheckFactory
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $factory = Factory::first();

        if (!$factory) {
            return redirect()->route('admin.factory')->with('error', 'Please check factory information');
        }

        return $next($request);
    }
}
