<?php

return [

    'credit' => [
        // Contrôle à la création d'une commande ou d'un BL
        'warning'                  => 'Esposizione del cliente oltre il fido: :projected per un fido di :limit.',
        'blocked_title'            => 'Fido superato',
        'blocked'                  => 'Questo cliente è bloccato: la sua esposizione raggiungerebbe :projected per un fido di :limit (esposizione attuale :outstanding).',
        'no_permission'            => 'Solo un utente autorizzato può derogare al fido.',
        'override'                 => 'Forza per questo documento',
        'override_reason'          => 'Motivo del superamento',
        'override_reason_help'     => 'Obbligatorio. Conservato con il documento, il suo autore e la data.',
        'this_document'            => 'Questo documento',
        'projected'                => 'Esposizione dopo questo documento',
        'override_reason_required' => 'Il motivo del superamento è obbligatorio.',

        // Fiche société
        'card_title'        => 'Esposizione del cliente',
        'limit'             => 'Fido (IVA inclusa)',
        'limit_help'        => 'Vuoto = nessun fido.',
        'block_level'       => 'Oltre il fido',
        'level_none'        => 'Nessun controllo',
        'level_warn'        => 'Avviso, senza bloccare',
        'level_block'       => 'Blocco di ordini e DDT',
        'payment_defaults_help' => 'Proposti alla creazione di preventivi e ordini di questo cliente. Vuoto = valori predefiniti dell\'istanza.',
        'read_only'         => 'Modificabile dagli utenti autorizzati a derogare al fido.',
        'outstanding'       => 'Esposizione',
        'invoices_due'      => 'Fatture non saldate',
        'open_orders'       => 'Ordini non fatturati',
        'no_limit'          => 'Nessun fido',
        'of_limit'          => 'del fido',
        'overrides_title'   => 'Ultime deroghe',
        'override_by'       => 'da',
    ],

    'reminders' => [
        'title'              => 'Fatture da sollecitare',
        'subtitle'           => 'Fatture scadute non saldate. Nessun invio automatico: ogni sollecito parte dalla vostra conferma.',
        'to_remind_only'     => 'Solo da sollecitare',
        'all_overdue'        => 'Tutte le fatture scadute',
        'search'             => 'Cerca un cliente o una fattura',
        'company'            => 'Cliente',
        'invoice'            => 'Fattura',
        'due_date'           => 'Scadenza',
        'days_late'          => 'Ritardo (gg)',
        'amount_ttc'         => 'Importo IVA inclusa',
        'balance'            => 'Residuo dovuto',
        'current_level'      => 'Ultimo sollecito',
        'proposed_level'     => 'Livello proposto',
        'up_to_date'         => 'Già sollecitata',
        'not_yet'            => 'Non ancora',
        'selected'           => 'selezionata/e',
        'remind_by_email'    => 'Sollecita via e-mail',
        'log_manual'         => 'Registra una telefonata / lettera',
        'no_permission'      => 'L\'invio dei solleciti è riservato agli utenti autorizzati.',
        'empty'              => 'Nessuna fattura scaduta non saldata.',
        'no_levels'          => 'Nessun livello di sollecito configurato.',
        'levels_link'        => 'Livelli di sollecito',
        'total_due'          => 'Totale dovuto',
        'invoices_count'     => 'fattura/e',

        // Fenêtre d'envoi
        'send_title'         => 'Sollecito a :client',
        'to'                 => 'Destinatario',
        'subject'            => 'Oggetto',
        'message'            => 'Messaggio',
        'edit_html'          => 'Modifica il testo (HTML)',
        'attachments'        => 'Allegati',
        'send'               => 'Invia il sollecito',
        'sending'            => 'Invio…',
        'cancel'             => 'Annulla',
        'channel'            => 'Canale',
        'note'               => 'Nota',
        'note_placeholder'   => 'Cosa è stato detto o inviato, promessa di pagamento…',
        'save_log'           => 'Registra il sollecito',

        'sent_ok'            => 'Sollecito inviato a :to.',
        'send_failed'        => 'L\'e-mail non è stata inviata: :error',
        'logged_ok'          => 'Sollecito registrato su :count fattura/e.',
        'pdf_failed'         => 'Non è stato possibile generare il PDF della fattura :code: :error',
        'not_overdue'        => 'La selezione contiene una fattura che non è più scaduta e dovuta (pagata nel frattempo?). Ricaricate l\'elenco.',

        'default_subject'    => 'Sollecito: fattura/e non pagata/e',
        'default_content'    => '<p>Buongiorno,</p><p>Salvo errori da parte nostra, le seguenti fatture risultano non pagate:</p><div>{factures}</div><p>Importo residuo dovuto: <strong>{total_du}</strong>.</p><p>Cordiali saluti,<br>{entreprise}</p>',

        'channels' => [
            'email'  => 'E-mail',
            'phone'  => 'Telefono',
            'letter' => 'Lettera',
        ],

        'table' => [
            'invoice'   => 'Fattura',
            'date'      => 'Data',
            'due_date'  => 'Scadenza',
            'days_late' => 'Giorni di ritardo',
            'balance'   => 'Residuo dovuto',
        ],

        // Historique sur la fiche facture
        'history_title'      => 'Solleciti',
        'history_empty'      => 'Nessun sollecito.',
        'email_failed'       => 'invio non riuscito',

        // Paramétrage des niveaux
        'levels_title'       => 'Livelli di sollecito',
        'levels_help'        => 'Un livello viene proposto non appena il ritardo raggiunge il suo termine. I modelli di e-mail si modificano in Amministrazione → Modelli di e-mail (tipo « Sollecito di fattura »). Variabili: {client}, {factures}, {total_du}, {retard}, {entreprise}.',
        'level'              => 'Livello',
        'label'              => 'Descrizione',
        'delay_days'         => 'Termine dopo la scadenza (giorni)',
        'email_template'     => 'Modello di e-mail',
        'color'              => 'Colore',
        'no_template'        => '— Testo predefinito —',
        'add_level'          => 'Aggiungi un livello',
        'save'               => 'Salva',
        'delete'             => 'Elimina',
        'level_saved'        => 'Livello salvato.',
        'level_deleted'      => 'Livello eliminato.',
        'template_type'      => 'Sollecito di fattura',
    ],

];
