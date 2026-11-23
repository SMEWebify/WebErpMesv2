<?php

return [
    'status' => [
        'qualified'      => 'Qualificato',
        'unqualified'    => 'Non qualificato',
        'review_overdue' => 'Riesame scaduto',
    ],

    'detail' => [
        'no_evaluation'  => 'nessuna valutazione del fornitore.',
        'not_approved'   => 'ultima valutazione con stato « :status ».',
        'review_overdue' => 'riesame di qualifica scaduto dal :date.',
    ],

    'blocked_title'           => 'Fornitore non qualificato',
    'blocked_create'          => 'Ordine d\'acquisto rifiutato: fornitore non qualificato.',
    'blocked_validate'        => 'Convalida dell\'ordine rifiutata: fornitore non qualificato.',
    'blocked_supplier_change' => 'Cambio fornitore rifiutato su un ordine convalidato: fornitore non qualificato. Riportate l\'ordine « in corso » per cambiare fornitore.',
    'no_permission'           => 'Non avete il permesso di ordinare a un fornitore non qualificato. Una richiesta d\'offerta resta possibile.',
    'override_reason'         => 'Motivo della deroga',
    'override_reason_help'    => 'Tracciato sull\'ordine e visibile sulla scheda del fornitore.',
    'override_reason_required'=> 'Indicate il motivo della deroga.',
    'override'                => 'Ordinare comunque',

    // Fiche société et fiche commande
    'label'           => 'Qualifica',
    'overrides_title' => 'Deroghe « fornitore non qualificato »',
    'override_on'     => 'Deroga su :document',
    'context'         => [
        'create'          => 'creazione',
        'validate'        => 'convalida',
        'supplier_change' => 'cambio fornitore',
    ],

    // Export
    'export'          => 'Fornitori qualificati (CSV)',
    'export_columns'  => [
        'code'              => 'Codice',
        'label'             => 'Ragione sociale',
        'qualification'     => 'Qualifica',
        'evaluation_status' => 'Stato dell\'ultima valutazione',
        'evaluated_at'      => 'Valutato il',
        'approved_at'       => 'Approvato il',
        'next_review_at'    => 'Prossimo riesame',
    ],
];
