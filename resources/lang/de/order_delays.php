<?php

return [
    'fields' => [
        'delivery_date' => 'Liefertermin',
        'internal_delay' => 'Interner Termin',
    ],
    'origins' => [
        'manual' => 'Manuelle Eingabe',
        'order' => 'Auftragsdatum',
        'import' => 'Import',
        'api' => 'API',
        'pre_order' => 'Vorauftrag',
        'system' => 'System',
        'non_conformity' => 'Abweichung',
    ],

    'reason_label' => 'Grund der Verschiebung',
    'reason_placeholder' => 'Optional: Lieferantenverzug, Kundenwunsch…',
    'promised_label' => 'Zugesagt am',
    'postponed' => 'Verschoben',
    'history_title' => 'Verlauf der Verschiebungen',
    'no_history' => 'Keine Verschiebung.',
    'loading' => 'Wird geladen…',
    'load_error' => 'Der Verlauf kann nicht geladen werden.',
    'by' => 'von',

    // Taux de service (widget OTD du tableau de bord)
    'otd_current' => 'Aktueller Termin',
    'otd_promised' => 'Zugesagter Termin',
    'otd_promised_help' => 'Termingerechte Lieferungen bezogen auf den dem Kunden zugesagten Termin, einschließlich Verschiebungen.',
    'otd_fallback' => ':count Position(en) ohne zugesagten Termin: aktueller Termin verwendet',
];
