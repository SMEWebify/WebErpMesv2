<?php

// Offres fournisseurs indicées des demandes de prix (App\Services\Purchases\PurchaseOfferRevisions)
return [
    'print_caption'  => 'Lieferantenangebot',
    'print_revision' => 'Index :revision',
    'created'        => 'Neues Angebot :code angelegt: die vorherige Anfrage wird ersetzt.',
    'refused' => [
        'superseded' => 'Dieses Angebot wurde ersetzt: nur der letzte Index (:code) kann bearbeitet oder in eine Bestellung umgewandelt werden.',
        'ordered'    => 'Aus dieser Preisanfrage wurde bereits eine Bestellung erstellt: ein neues Angebot würde eine zweite Bestellung ermöglichen.',
    ],
    'screen' => [
        'reference'         => 'Angebotsreferenz',
        'revision'          => 'Angebotsindex',
        'date'              => 'Angebotsdatum',
        'validity'          => 'Gültigkeit des Angebots',
        'new_revision'      => 'Neues Angebot (nächster Index)',
        'new_revision_help' => 'Kopiert die Anfrage und ihre Positionen. Diese wird als „ersetzt“ markiert: sie kann weder bearbeitet noch bestellt werden.',
        'revision_prompt'   => 'Vom Lieferanten angegebener Index (leer lassen für den nächsten Index):',
        'history'           => 'Angebotsverlauf',
        'superseded'        => 'Ersetzt',
        'current'           => 'Letzter Index',
        'superseded_banner' => 'Angebot ersetzt durch :code: es kann weder bearbeitet noch bestellt werden.',
        'open_latest'       => 'Letzten Index öffnen',
        'no_revision'       => 'ohne Index',
        'revision_short'    => 'Ind.',
        'total'             => 'Summe',
    ],
];
