<?php

return [
    'form' => [
        'title'            => 'Emetti una nota di credito',
        'help'             => 'Spuntate le righe, poi regolate la quantità o il prezzo per una nota di credito parziale (pezzi scartati, reso parziale, gesto commerciale).',
        'line'             => 'Riga',
        'invoiced'         => 'Fatturato',
        'creditable'       => 'Residuo accreditabile',
        'qty'              => 'Q.tà da accreditare',
        'unit_price'       => 'P.U. accreditato IVA esclusa',
        'fully_credited'   => 'Già in nota di credito',
        'deduction'        => 'Detrazione acconto',
        'restore'          => 'Rimettere queste quantità nel residuo da fatturare (pezzi resi o rifatti, da rifatturare)',
        'restore_help'     => 'Togliete la spunta per un gesto commerciale o uno scarto che non verrà rifatto: l\'ordine resta fatturato.',
        'submit'           => 'Crea la nota di credito',
    ],

    'line' => [
        'restored'     => 'Rimesso da fatturare',
        'not_restored' => 'Non rimesso da fatturare',
    ],

    'refused' => [
        'no_line'            => 'Selezionate almeno una riga di fattura.',
        'several_invoices'   => 'Una nota di credito riguarda una sola fattura.',
        'fully_credited'     => 'La riga :line è già interamente in nota di credito.',
        'qty'                => 'Riga :line: la quantità deve essere positiva e non superare :max.',
        'price'              => 'Riga :line: il prezzo accreditato non può superare il prezzo fatturato (:max €).',
        'amount'             => 'Riga :line: l\'importo accreditato supera il residuo accreditabile (:max € IVA esclusa).',
        'already_reinvoiced' => 'Le quantità della riga :line sono state rifatturate nel frattempo: la nota di credito non può più essere rifiutata.',
    ],
];
