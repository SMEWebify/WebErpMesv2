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
