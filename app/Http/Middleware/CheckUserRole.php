<?php

namespace App\Http\Middleware;

use App\Support\SetupState;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Response;

class CheckUserRole
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check() && auth()->user()->roles()->count() === 0) {
            // Première installation : l'assistant attribue le rôle (étape 7).
            if (!SetupState::anyUserHasRole() && Route::has('setup.index')) {
                return redirect()->route('setup.index');
            }

            // Instance installée : c'est à un administrateur de donner un rôle. Renvoyer vers
            // l'assistant (fermé) bouclait : setup → dashboard → setup…
            if (Route::has('pending.role')) {
                return redirect()->route('pending.role');
            }

            abort(403, 'No role assigned.');
        }

        return $next($request);
    }
}
