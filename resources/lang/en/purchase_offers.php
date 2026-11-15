<?php

// Revised supplier offers on purchase quotations (App\Services\Purchases\PurchaseOfferRevisions)
return [
    'print_caption'  => 'Supplier offer',
    'print_revision' => 'rev. :revision',
    'created'        => 'New offer :code created: the previous quotation is superseded.',
    'refused' => [
        'superseded' => 'This offer has been superseded: only the latest revision (:code) can be edited or turned into an order.',
        'ordered'    => 'An order has already been created from this quotation: a new offer would allow ordering a second time.',
    ],
    'screen' => [
        'reference'         => 'Offer reference',
        'revision'          => 'Offer revision',
        'date'              => 'Offer date',
        'validity'          => 'Offer valid until',
        'new_revision'      => 'New offer (next revision)',
        'new_revision_help' => 'Copies the quotation and its lines. This one becomes "superseded": it can no longer be edited or ordered.',
        'revision_prompt'   => 'Revision given by the supplier (leave empty for the next one):',
        'history'           => 'Offer history',
        'superseded'        => 'Superseded',
        'current'           => 'Latest revision',
        'superseded_banner' => 'Offer superseded by :code: it can no longer be edited or ordered.',
        'open_latest'       => 'Open the latest revision',
        'no_revision'       => 'no revision',
        'revision_short'    => 'rev.',
        'total'             => 'Total',
    ],
];
