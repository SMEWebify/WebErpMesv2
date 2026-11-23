<?php

// Offres fournisseurs indicées des demandes de prix (App\Services\Purchases\PurchaseOfferRevisions)
return [
    'print_caption'  => 'Offerta del fornitore',
    'print_revision' => 'revisione :revision',
    'created'        => 'Nuova offerta :code creata: la richiesta precedente è sostituita.',
    'refused' => [
        'superseded' => 'Questa offerta è stata sostituita: solo l\'ultima revisione (:code) si modifica o si trasforma in ordine.',
        'ordered'    => 'Da questa richiesta d\'offerta è già stato emesso un ordine: una nuova offerta permetterebbe di ordinare una seconda volta.',
    ],
    'screen' => [
        'reference'         => 'Riferimento dell\'offerta',
        'revision'          => 'Revisione dell\'offerta',
        'date'              => 'Data dell\'offerta',
        'validity'          => 'Validità dell\'offerta',
        'new_revision'      => 'Nuova offerta (revisione successiva)',
        'new_revision_help' => 'Ricopia la richiesta e le sue righe. Questa passa a «sostituita»: non sarà più né modificata né ordinata.',
        'revision_prompt'   => 'Revisione indicata dal fornitore (lasciare vuoto per la revisione successiva):',
        'history'           => 'Cronologia delle offerte',
        'superseded'        => 'Sostituita',
        'current'           => 'Ultima revisione',
        'superseded_banner' => 'Offerta sostituita da :code: non si modifica né si ordina più.',
        'open_latest'       => 'Apri l\'ultima revisione',
        'no_revision'       => 'senza revisione',
        'revision_short'    => 'rev.',
        'total'             => 'Totale',
    ],
];
