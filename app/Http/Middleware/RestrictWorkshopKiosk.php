<?php

namespace App\Http\Middleware;

use App\Services\Production\ScanStation;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Compte de poste atelier partagé (permission workshop-kiosk) : il n'ouvre que
 * l'écran de scan. Toute autre page renvoie vers lui ; un appel AJAX reçoit 403.
 * Sans la permission (tous les autres comptes), rien ne change.
 */
class RestrictWorkshopKiosk
{
    /** Routes ouvertes au compte de poste : l'écran de scan et la déconnexion. */
    private const ALLOWED = ['workshop.scan', 'workshop.scan.store', 'logout', 'login'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user || ! ScanStation::isKiosk($user)) {
            return $next($request);
        }

        if (in_array($request->route()?->getName(), self::ALLOWED, true)) {
            return $next($request);
        }

        if ($request->expectsJson() || ! $request->isMethod('GET')) {
            abort(403);
        }

        return redirect()->route('workshop.scan');
    }
}
