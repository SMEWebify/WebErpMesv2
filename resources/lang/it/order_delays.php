<?php

return [
    'fields' => [
        'delivery_date' => 'Data di consegna',
        'internal_delay' => 'Data interna',
    ],
    'origins' => [
        'manual' => 'Inserimento manuale',
        'order' => 'Data dell\'ordine',
        'import' => 'Importazione',
        'api' => 'API',
        'pre_order' => 'Pre-ordine',
        'system' => 'Sistema',
        'non_conformity' => 'Non conformità',
    ],

    'reason_label' => 'Motivo del rinvio',
    'reason_placeholder' => 'Facoltativo: ritardo fornitore, richiesta del cliente…',
    'promised_label' => 'Promessa il',
    'postponed' => 'Rinviata',
    'history_title' => 'Cronologia dei rinvii',
    'no_history' => 'Nessun rinvio.',
    'loading' => 'Caricamento…',
    'load_error' => 'Impossibile caricare la cronologia.',
    'by' => 'da',

    // Taux de service (widget OTD du tableau de bord)
    'otd_current' => 'Data attuale',
    'otd_promised' => 'Data promessa',
    'otd_promised_help' => 'Consegne puntuali rispetto alla data promessa al cliente, rinvii compresi.',
    'otd_fallback' => ':count riga/righe senza data promessa: usata la data attuale',
];
