<?php

return [
    'document_title'  => "FATTURA D'ACCONTO",
    'type_label'      => 'Acconto',
    'invoice_label'   => 'Acconto su ordine :order',
    'line_label'      => 'Acconto :percent % su ordine :order',
    'deduction_label' => 'Detrazione acconto :code',

    'card' => [
        'title'          => 'Acconti',
        'none'           => 'Nessuna fattura d\'acconto.',
        'order_total'    => 'Totale ordine imponibile',
        'invoice'        => 'Fattura',
        'ht'             => 'Imponibile',
        'paid'           => 'Pagato IVA inclusa',
        'deducted'       => 'Detratto imponibile',
        'draft'          => 'Bozza',
        'mode_percent'   => '%',
        'mode_amount'    => '€ imponibile',
        'value'          => 'Importo',
        'create'         => 'Fattura d\'acconto',
        'help'           => 'L\'acconto non modifica le quantità: viene detratto dalle fatture successive in proporzione a quanto fatturato, fino a concorrenza di quanto pagato.',
    ],

    'invoice' => [
        'deducts'        => 'Detrae l\'acconto',
        'recompute'      => 'Ricalcola la detrazione dell\'acconto',
        'recomputed'     => 'Detrazione dell\'acconto ricalcolata.',
        'is_down_payment'=> 'Fattura d\'acconto dell\'ordine :order: imponibile :ht, già detratto :deducted, residuo da detrarre :remaining.',
        'unpaid_warning' => 'Acconto :code non pagato: non viene detratto da questa fattura.',
    ],

    'created' => 'Fattura d\'acconto :code creata in bozza.',

    'refused' => [
        'internal_order'  => 'Un ordine interno non riceve acconti.',
        'canceled_order'  => 'Ordine annullato: nessun acconto.',
        'empty_order'     => 'L\'ordine non ha alcun importo da fatturare.',
        'amount'          => 'L\'importo dell\'acconto deve essere positivo.',
        'over_total'      => 'Gli acconti supererebbero il totale dell\'ordine (residuo possibile: :max € imponibile).',
        'not_draft'       => 'La fattura non è più in bozza.',
        'not_a_deduction' => 'Questa riga non è una detrazione d\'acconto.',
        'deduction_max'   => 'Detrazione impossibile oltre la quota pagata e non detratta dell\'acconto (:max € imponibile).',
        'credit_note_on_deduction' => 'Una detrazione d\'acconto non si storna con una nota di credito: selezionate le righe vendute.',
        'credit_note_on_deducted'  => 'L\'acconto :code è già detratto da una fattura: emettete la nota di credito sulla fattura finale.',
    ],
];
