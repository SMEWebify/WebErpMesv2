<?php

return [
    'label' => 'Motivo di perdita',
    'plural' => 'Motivi di perdita',
    'none' => 'Non indicato',
    'inactive' => 'inattivo',
    'info' => 'Motivi proposti quando un preventivo passa allo stato Perso o un\'opportunità a Persa. Il motivo è richiesto non appena almeno un motivo è attivo; i documenti persi prima della sua introduzione restano validi senza motivo. Un motivo già utilizzato non si elimina: disattivato, non viene più proposto ma resta visualizzato sui documenti che lo riportano.',
    'new' => 'Nuovo motivo',
    'edit' => 'Modifica :label',
    'code' => 'Codice',
    'order' => 'Ordine',
    'active' => 'Attivo',
    'usage' => 'Utilizzo',
    'usage_detail' => ':quotes preventivo/i, :opportunities opportunità',
    'created' => 'Motivo di perdita creato.',
    'updated' => 'Motivo di perdita aggiornato.',
    'code_locked' => 'Il codice non è modificabile.',

    'defaults' => [
        'PRICE' => 'Prezzo',
        'DELAY' => 'Tempi di consegna',
        'TECHNICAL' => 'Tecnico',
        'COMPETITOR' => 'Concorrente',
        'NO_FOLLOW_UP' => 'Senza seguito',
    ],

    'dialog' => [
        'title_quote' => 'Preventivo perso: perché?',
        'title_opportunity' => 'Opportunità persa: perché?',
        'reason' => 'Motivo',
        'choose' => '— Scegliere un motivo —',
        'comment' => 'Commento',
        'comment_help' => 'Concorrente scelto, differenza di prezzo, precisazione del cliente…',
        'cancel' => 'Annulla',
        'confirm' => 'Salva',
        'edit' => 'Modifica',
        'fill' => 'Indica',
        'error' => 'Errore durante il salvataggio del motivo.',
    ],

    'required' => 'Indicate il motivo della perdita.',
    'not_lost' => 'Il motivo di perdita si inserisce solo su un documento perso.',

    'stats' => [
        'title' => 'Perdite commerciali',
        'by_reason' => 'Per motivo',
        'by_category' => 'Per categoria di commessa',
        'count' => 'Preventivi',
        'amount' => 'Importo imponibile',
        'share' => 'Quota',
        'total' => 'Totale',
        'no_category' => 'Senza categoria',
        'empty' => 'Nessun preventivo perso nel periodo.',
        'period' => 'Periodo',
        'periods' => [
            '12m' => 'Ultimi 12 mesi',
            'year' => 'Anno in corso',
            'all' => 'Dall\'inizio',
        ],
        'period_help' => 'Preventivi persi creati nel periodo.',
    ],
];
