<?php

return [
    // Liste « sans AR »
    'menu'                => 'Ordini senza conferma',
    'missing_title'       => 'Ordini d\'acquisto senza conferma d\'ordine del fornitore',
    'missing_info'        => 'Ordini emessi da almeno :days giorno/i di cui una riga non ha ancora la conferma d\'ordine del fornitore. Inserite la conferma dalla scheda dell\'ordine (scheda delle righe, pulsante «Conferma ricevuta»).',
    'missing_none'        => 'Nessun ordine in attesa di conferma d\'ordine.',
    'ordered_at'          => 'Ordinato il',
    'waiting_days'        => 'Attesa',
    'days'                => '{0} oggi|{1} :count giorno|[2,*] :count giorni',
    'lines_without_ack'   => 'Righe senza conferma',
    'next_requested_date' => 'Prima data richiesta',

    'confirmed_date_hint' => 'Data confermata dal fornitore (conferma d\'ordine)',

    // Fiche commande d'achat (PurchaseLinesPage, SupplierAckModal)
    'screen' => [
        'action'          => 'Conferma ricevuta',
        'title'           => 'Conferma d\'ordine del fornitore',
        'ack_at'          => 'Data della conferma',
        'reference'       => 'Riferimento della conferma',
        'file'            => 'Documento (facoltativo)',
        'hint'            => 'Spuntate le righe confermate e inserite la data di consegna annunciata dal fornitore (per impostazione predefinita la data richiesta). Diventa la data di riferimento per i ritardi. Il documento viene archiviato nella scheda Documenti, ruolo «Conferma fornitore».',
        'all_lines'       => 'Tutte le righe',
        'line'            => 'Riga',
        'requested'       => 'Data richiesta',
        'confirmed'       => 'Data confermata',
        'gap'             => 'Scostamento',
        'already'         => 'Conferma già ricevuta',
        'day_unit'        => 'gg',
        'save'            => 'Salva la conferma',
        'saving'          => 'Salvataggio…',
        'cancel'          => 'Annulla',
        'saved'           => 'Conferma d\'ordine salvata',
        'error'           => 'Errore durante il salvataggio della conferma',
        'file_error'      => 'Conferma salvata, ma non è stato possibile caricare il documento:',
        'no_line'         => 'Spuntate almeno una riga.',
        'missing'         => 'Senza conferma',
        'ack_short'       => 'Conferma del',
        'requested_short' => 'richiesta',
        'confirmed_title' => 'Data confermata dal fornitore (data di riferimento)',
        'requested_title' => 'Data richiesta (non ancora confermata)',
    ],

    // Saisie
    'not_ordered'         => 'La conferma d\'ordine si inserisce su un ordine emesso (né in corso, né annullato).',
    'foreign_line'        => 'Una delle righe non appartiene a questo ordine.',
];
