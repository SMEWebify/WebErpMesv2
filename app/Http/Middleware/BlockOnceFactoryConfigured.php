<?php

namespace App\Http\Middleware;

use App\Support\SetupState;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ferme le wizard d'installation au fur et à mesure qu'il est rempli (App\Support\SetupState).
 *
 * Paramètre de route :
 * - (aucun) étapes 1 à 6 : fermées dès que les six référentiels existent, pour qu'un utilisateur
 *   post-install ne puisse pas réécrire la société ni les référentiels comptables ;
 * - role : étape 7, ouverte tant qu'aucun utilisateur n'a de rôle (première installation) ;
 * - budget : étape 8, ouverte tant qu'aucun objectif de chiffre n'existe ;
 * - page : l'écran lui-même, ouvert tant que l'installation n'est pas terminée.
 *
 * Avant ce découpage, les étapes 7 et 8 étaient refusées dès la fin de l'étape 6.
 * Le paramétrage post-install passe par les écrans admin dédiés.
 */
class BlockOnceFactoryConfigured
{
    public function handle(Request $request, Closure $next, string $stage = 'referentials'): Response
    {
        $closed = match ($stage) {
            'role'   => SetupState::referentialsDone() && SetupState::anyUserHasRole(),
            'budget' => SetupState::referentialsDone() && SetupState::budgetDone(),
            'page'   => SetupState::finished(),
            default  => SetupState::referentialsDone(),
        };

        if ($closed) {
            if ($request->expectsJson()) {
                abort(403, 'Setup wizard is closed.');
            }

            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
