<?php

return [
    'saved'    => 'Valutazione del fornitore salvata.',
    'no_lines' => 'Ricevimento senza righe: nessun ordine d\'acquisto a cui collegare la valutazione.',

    // Fenêtre de notation rapide (fiche réception)
    'window' => [
        'card_title'      => 'Valutazione del fornitore',
        'title'           => 'Valuta il fornitore',
        'intro'           => 'Facoltativo: pochi secondi per seguire la qualità dei vostri fornitori.',
        'conformity'      => 'Conformità',
        'conformity_help' => 'Pezzi conformi all\'ordine',
        'delay'           => 'Tempi di consegna',
        'delay_help'      => 'Rispetto della data promessa',
        'documents'       => 'Documenti',
        'documents_help'  => 'DDT, certificati, etichettatura',
        'comment'         => 'Commento',
        'later'           => 'Più tardi',
        'save'            => 'Salva',
        'saving'          => 'Salvataggio…',
        'rate'            => 'Valuta',
        'edit'            => 'Modifica',
        'not_rated'       => 'Ricevimento non valutato.',
        'no_lines'        => 'Aggiungete una riga per poter valutare il fornitore.',
        'days_late'       => ':days gg di ritardo rispetto alla data promessa',
        'on_time'         => 'Consegnato alla data promessa',
        'no_promised'     => 'Nessuna data promessa sulle righe d\'acquisto',
        'suggested'       => 'proposto',
        'has_nc'          => 'Non conformità su questo ricevimento: la valutazione della qualità conta per :score/5.',
        'rolling'         => 'Valutazione mobile :months mesi',
        'rolling_detail'  => ':count valutazione/i, :nc ricevimento/i non conforme/i',
        'no_rolling'      => 'Nessuna valutazione negli ultimi :months mesi',
        'error'           => 'Errore durante il salvataggio',
    ],

    // Fiche fournisseur
    'company' => [
        'rolling'        => 'Valutazione mobile (:months mesi)',
        'since'          => 'dal :date',
        'quality'        => 'Qualità',
        'logistics'      => 'Tempi di consegna',
        'service'        => 'Documenti / servizio',
        'count'          => ':count valutazione/i',
        'nc_receipts'    => ':count ricevimento/i con NC',
        'none'           => 'Nessuna valutazione né ricevimento non conforme nel periodo.',
        'receipt'        => 'Ricevimento',
    ],

    // Taux de livraison à l'heure (App\Services\Purchases\SupplierOnTimeDelivery)
    'on_time' => [
        'title'     => 'Consegne puntuali (:months mesi)',
        'global'    => 'Tutti i fornitori',
        'rate'      => 'Puntuali',
        'lines'     => 'Righe',
        'detail'    => ':on_time riga/e puntuale/i su :measured',
        'undated'   => ':count riga/e senza data richiesta né confermata, non conteggiata/e',
        'tolerance' => 'Tolleranza: :days gg',
        'rule'      => 'Riga ricevuta interamente, alla data del suo ultimo ricevimento, rispetto alla data confermata (altrimenti richiesta).',
        'none'      => 'Nessuna riga ricevuta e datata nel periodo.',
        'worst'     => 'I meno puntuali',
    ],
];
