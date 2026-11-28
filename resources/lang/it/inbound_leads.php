<?php

// Demandes entrantes : pistes créées depuis un e-mail (dépôt .eml / .msg, API pour n8n).
return [
    'import_button'       => 'Carica un\'e-mail',
    'import_title'        => 'Nuova richiesta da un\'e-mail',
    'import_drop'         => "Trascina qui l'e-mail",
    'import_browse'       => 'oppure fai clic per scegliere il file',
    'import_wrong_type'   => 'Sono accettate solo e-mail .eml o .msg.',
    'import_hint'         => "Trascina qui il file .eml o .msg, oppure direttamente sull'elenco dei lead. Da Outlook: trascina prima l'e-mail sul desktop o in una cartella. Il mittente viene abbinato a un contatto o a un'azienda; in mancanza, viene creato un potenziale cliente.",
    'import_submit'       => 'Crea il lead',
    'file_label'          => 'E-mail',
    'subject'             => 'Oggetto',
    'received_at'         => 'Ricevuta il',
    'sender'              => 'Mittente',
    'new_quote'           => 'Crea il preventivo',
    'created'             => 'Richiesta registrata.',
    'already_received'    => 'Questa e-mail è già stata ricevuta: ecco il relativo lead.',
    'unreadable'          => 'E-mail illeggibile: carica il file .eml o .msg originale.',
    'no_sender'           => 'Indirizzo del mittente non trovato nell\'e-mail.',
    'quote_created'       => 'Preventivo creato dalla richiesta.',
    'address_to_complete' => 'Indirizzo da completare',
    'created_comment'     => 'Azienda creata da una richiesta ricevuta da :email, da completare.',
    'prospect_created_comment' => 'Prospect ricevuto tramite API, da completare.',
    'prospect_contact_required' => 'Indicare almeno il nome o l\'e-mail del contatto.',
    'prospect_reachable_required' => 'Indicare un e-mail o un telefono per contattare il referente.',
    'prospect_siren_invalid' => 'SIREN non valido: 9 cifre (o SIRET di 14 cifre).',
];
