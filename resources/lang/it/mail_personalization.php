<?php

return [
    'tab' => 'E-mail',
    'signature' => 'Firma delle e-mail',
    'signature_hint' => "Aggiunta sotto il messaggio di ogni e-mail inviata dall'ERP (preventivi, ordini, DDT, fatture, acquisti, solleciti). Compare nell'editor prima dell'invio: puoi ritoccarla per un singolo invio.",
    'signature_image_hint' => "Un logo incollato nell'editor viene spesso bloccato da Gmail e Outlook: preferisci un'immagine inserita tramite il suo indirizzo web (https://…).",
    'signature_saved' => 'Firma salvata.',
    'variables' => 'Variabili',
    'variables_hint' => "Sostituite all'apertura della schermata di invio, nell'oggetto e nel messaggio.",
    'reminder_variables_hint' => 'Solleciti di fatture: {client}, {factures}, {total_du}, {retard}, {entreprise}, {utilisateur} e {signature}.',
    'variable' => [
        'code' => 'Numero del documento',
        'libelle' => 'Descrizione del documento',
        'client' => 'Azienda (cliente o fornitore)',
        'contact' => 'Contatto del documento',
        'utilisateur' => 'Nome del mittente',
        'entreprise' => 'La tua azienda',
        'signature' => 'Firma del mittente (altrimenti aggiunta alla fine)',
    ],
];
