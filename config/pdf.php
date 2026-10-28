<?php

use App\Models\Purchases\PurchaseReceipt;
use App\Models\Purchases\Purchases;
use App\Models\Purchases\PurchasesQuotation;
use App\Models\Quality\QualityNonConformity;
use App\Models\Workflow\CreditNotes;
use App\Models\Workflow\Deliverys;
use App\Models\Workflow\Invoices;
use App\Models\Workflow\Orders;
use App\Models\Workflow\Quotes;

return [
    'fallback_theme' => 'default',

    'documents' => [
        Quotes::class => 'print/pdf-sales',
        Orders::class => 'print/pdf-sales',
        Invoices::class => 'print/pdf-invoice',
        Deliverys::class => 'print/pdf-delivery',
        CreditNotes::class => 'print/pdf-credit-note',
        PurchasesQuotation::class => 'print/pdf-purchases-quotation',
        Purchases::class => 'print/pdf-purchases',
        PurchaseReceipt::class => 'print/pdf-purchases-receipt',
        QualityNonConformity::class => 'print/pdf-nc',
    ],

    /*
    | Modèles d'impression (App\Services\Documents\PdfLayoutRegistry).
    |
    | « short » est la vue de base (print/pdf-sales…), thème compris : c'est le
    | rendu historique, et celui des e-mails. Chaque autre modèle est un dossier
    | de print/layouts/{modèle} (versionné) ou de print/custom/layouts/{modèle}
    | (propre au client, non versionné) qui reprend les noms de fichier de
    | print/ : un modèle est proposé pour un document dès que son dossier
    | contient le fichier de la vue de base (pdf-sales.blade.php pour devis,
    | commande et AR). layout.json, facultatif, en donne le libellé, s'il porte
    | des prix et les données à préparer — voir print/layouts/long/layout.json.
    */
    'layouts_path'        => 'print/layouts',
    'custom_layouts_path' => 'print/custom/layouts',

    // Langues proposées à l'impression, indépendamment de celle de l'utilisateur.
    'languages' => [
        'fr' => 'Français',
        'en' => 'English',
    ],

    'themes' => [
        'default' => [
            'print/pdf-sales' => 'print/pdf-sales',
            'print/pdf-invoice' => 'print/pdf-invoice',
            'print/pdf-delivery' => 'print/pdf-delivery',
            'print/pdf-credit-note' => 'print/pdf-credit-note',
            'print/pdf-purchases-quotation' => 'print/pdf-purchases-quotation',
            'print/pdf-purchases' => 'print/pdf-purchases',
            'print/pdf-purchases-receipt' => 'print/pdf-purchases-receipt',
            'print/pdf-nc' => 'print/pdf-nc',
        ],
    ],
];
