<?php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Services\Purchases\SupplierAcknowledgement;

/**
 * Liste « Commandes d'achat sans AR » du menu Achats (lecture seule ; l'AR se saisit
 * sur la fiche commande).
 */
class SupplierAcknowledgementController extends Controller
{
    public function index(SupplierAcknowledgement $acknowledgement)
    {
        return view('purchases/purchases-acknowledgements-missing', [
            'purchases' => $acknowledgement->overdueList(),
            'days'      => $acknowledgement->overdueDays(),
        ]);
    }
}
