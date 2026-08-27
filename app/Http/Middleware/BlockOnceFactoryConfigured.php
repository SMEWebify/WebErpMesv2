<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Admin\Factory;
use App\Models\Accounting\AccountingVat;
use App\Models\Accounting\AccountingPaymentConditions;
use App\Models\Accounting\AccountingPaymentMethod;
use App\Models\Accounting\AccountingDelivery;
use App\Models\Methods\MethodsUnits;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ferme le wizard d'installation une fois que la société est configurée.
 *
 * Miroir inversé de CheckFactory : ce middleware coupe l'accès aux routes
 * /setup/* dès que les six tables de base sont peuplées, pour qu'un
 * utilisateur authentifié post-install ne puisse pas ré-écrire la
 * ligne Factory ni écraser les référentiels comptables via le wizard.
 *
 * Le paramétrage post-install passe par les écrans admin dédiés
 * (FactoryController, AccountingVatController, etc.).
 */
class BlockOnceFactoryConfigured
{
    public function handle(Request $request, Closure $next): Response
    {
        if (
            Factory::exists()
            && AccountingVat::exists()
            && AccountingPaymentConditions::exists()
            && AccountingPaymentMethod::exists()
            && AccountingDelivery::exists()
            && MethodsUnits::exists()
        ) {
            if ($request->expectsJson()) {
                abort(403, 'Setup wizard is closed.');
            }

            return redirect()->route('dashboard');
        }

        return $next($request);
    }
}
