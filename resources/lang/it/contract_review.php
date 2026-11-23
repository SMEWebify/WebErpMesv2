<?php

return [
    'tab' => 'Riesame del contratto',
    'quote_tab' => 'Riesame',
    'title' => 'Riesame del contratto',
    'intro' => 'Prima di avviare l\'ordine, verificare che corrisponda all\'offerta e che l\'azienda sia in grado di rispettarlo. Il visto congela il riesame.',

    // Indicateur (index, onglet)
    'status' => [
        'done' => 'Riesame eseguito',
        'rejected' => 'Riesame respinto',
        'todo' => 'Riesame da eseguire',
    ],
    'column' => 'Riesame',

    'checklist_title' => 'Punti riesaminati',
    'checklist' => [
        'requirements_understood' => 'Requisiti del cliente compresi e conformi all\'offerta',
        'lead_time_achievable' => 'Termine di consegna richiesto rispettabile',
        'contract_documents_received' => 'Documenti contrattuali ricevuti',
        'quality_requirements_identified' => 'Requisiti di qualità e codici applicabili individuati',
    ],
    'checklist_removed' => 'punto rimosso dalla configurazione',

    'gaps' => 'Scostamenti rispetto all\'offerta',
    'gaps_help' => 'Differenze tra l\'ordine e l\'offerta (prezzo, quantità, termine di consegna, requisiti) e come vengono risolte. Obbligatorio per respingere, o per approvare con un punto non spuntato.',
    'no_gaps' => 'Nessuno scostamento dichiarato.',

    'documents' => 'Documenti contrattuali',
    'documents_help' => 'Documenti della gestione documentale dell\'ordine di tipo « Documento contrattuale ».',
    'no_documents' => 'Nessun documento contrattuale nella gestione documentale dell\'ordine.',
    'open_documents' => 'Apri i documenti',

    'save' => 'Salva la preparazione',
    'saved' => 'Preparazione salvata.',
    'approve' => 'Vistare: riesame approvato',
    'reject' => 'Vistare: riesame respinto',
    'approve_confirm' => 'Approvare il riesame del contratto? Verrà congelato e sarà preparata la conferma d\'ordine.',
    'reject_confirm' => 'Respingere il riesame del contratto? Verrà congelato e l\'ordine non potrà più ricevere il rilascio per la produzione.',
    'visa_reserved' => 'Il visto è riservato alle persone autorizzate (permesso « contract-review-approve »).',
    'visa' => 'Vistato il :date da :user',
    'frozen' => 'Riesame vistato: non può più essere modificato.',
    'unchecked_warning' => 'Punto/i non spuntato/i: descrivere gli scostamenti prima di approvare.',
    'saving' => 'Salvataggio…',
    'error' => 'Il salvataggio non è riuscito.',

    // Avertissement de la fiche commande (jamais bloquant)
    'warning_todo' => 'Il riesame del contratto di questo ordine non è vistato.',
    'warning_rejected' => 'Il riesame del contratto di questo ordine è stato respinto.',
    'warning_link' => 'Apri il riesame',
    'risk_register' => [
        'warning' => 'La categoria di commessa richiede un registro dei rischi e nessun rischio è stato censito. Il visto resta possibile.',
        'link' => 'Censisci i rischi della commessa',
        'no_affair' => 'L\'ordine non è collegato ad alcuna commessa (opportunità): i rischi si censiscono sull\'opportunità.',
    ],

    // Demande de changement
    'change' => [
        'title' => 'Richiesta di modifica',
        'help' => 'Modifica richiesta dopo il riesame (indice, quantità, termine di consegna…). Finché non è approvata, non viene dato alcun rilascio per la produzione.',
        'pending' => 'Modifica in attesa di approvazione',
        'save' => 'Salva la modifica',
        'saved' => 'Richiesta di modifica salvata.',
    ],

    // Avenants (remplacent la demande de changement)
    'amendment' => [
        'title' => 'Varianti contrattuali',
        'help' => 'Una variante contrattuale (indice, quantità, termine di consegna, prezzo…) riapre il riesame del contratto: l\'ordine torna a « riesame da eseguire » e non viene dato alcun rilascio per la produzione fino al visto del riesame. Il preventivo d\'origine non viene modificato.',
        'number' => 'Variante n. :number',
        'new' => 'Nuova variante',
        'reason' => 'Motivo',
        'requested_by' => 'Richiedente',
        'date' => 'Data',
        'create' => 'Crea la variante e riapri il riesame',
        'create_confirm' => 'Creare la variante? Il riesame del contratto sarà riaperto fino al suo visto.',
        'created' => 'Variante creata: il riesame del contratto è riaperto.',
        'cancel' => 'Annulla la variante',
        'cancel_confirm' => 'Annullare questa variante? Il riesame del contratto tornerà allo stato precedente alla variante.',
        'cancelled_ok' => 'Variante annullata: il riesame precedente è ripristinato.',
        'amount_before' => 'IVA inclusa prima',
        'amount_after' => 'IVA inclusa dopo',
        'amount_pending' => 'al visto',
        'decision' => 'Visto',
        'none' => 'Nessuna variante.',
        'pending_exists' => 'Una variante è già aperta: vistarla (riesame del contratto) o annullarla prima di crearne un\'altra.',
        'reopened' => 'Riesame riaperto dalla variante n. :number: vistarlo vista la variante.',
        'warning' => 'La variante n. :number è in attesa del visto del riesame del contratto.',
        'status' => [
            'pending' => 'In attesa di visto',
            'approved' => 'Approvata',
            'rejected' => 'Respinta',
            'cancelled' => 'Annullata',
        ],
        'legacy' => 'Vecchia richiesta di modifica',
        'legacy_help' => 'Inserita prima delle varianti, conservata per lo storico.',
        'legacy_approved_at' => 'Approvata il',
        'errors' => [
            'not_applicable' => 'Un ordine interno non ha varianti.',
            'already_pending' => 'Una variante è già in attesa di visto su questo ordine.',
            'closed' => 'Questa variante è già chiusa.',
        ],
    ],

    'history' => 'Storico del riesame',

    // Refus
    'errors' => [
        'forbidden' => 'Non avete il diritto di vistare un riesame del contratto.',
        'frozen' => 'Il riesame del contratto è vistato: non può più essere modificato.',
        'not_applicable' => 'Un ordine interno non ha riesame del contratto.',
        'invalid_decision' => 'Decisione sconosciuta.',
        'gaps_required_reject' => 'Descrivere gli scostamenti rispetto all\'offerta per respingere il riesame.',
        'gaps_required_unchecked' => 'Punti non spuntati (:items): descrivere gli scostamenti rispetto all\'offerta per approvare il riesame.',
    ],
];
