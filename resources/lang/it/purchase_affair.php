<?php

return [
    // Rattachement d'un achat à une affaire (en-tête)
    'link'                => 'Commessa collegata',
    'none'                => 'Nessuna',
    'help'                => 'Per un acquisto senza operazione (spese, utensileria dedicata): compare nel dossier della commessa.',
    'invalid'             => 'Il documento collegato è introvabile.',
    'group_orders'        => 'Ordini',
    'group_quotes'        => 'Preventivi',
    'group_opportunities' => 'Opportunità',

    // Demande d'achat depuis un devis
    'source'                  => 'Operazioni di',
    'source_orders'           => 'Ordini confermati',
    'source_quotes'           => 'Preventivi in corso',
    'source_quotes_help'      => 'Operazioni dei preventivi non ancora ordinati: solo richiesta d\'offerta, per quotare il preventivo.',
    'quote'                   => 'Preventivo',
    'order_needs_firm_order'  => 'Un ordine d\'acquisto può riguardare solo operazioni di ordini confermati. Per un preventivo, avviate una richiesta d\'offerta.',
    'quotation_tasks_invalid' => 'Alcune operazioni non provengono né da un ordine confermato né da un preventivo in corso.',
    'quote_not_orderable'     => 'Riga di un preventivo non ancora ordinato: mantenete il prezzo per il preventivo, l\'ordine d\'acquisto si farà una volta convertito il preventivo in ordine.',

    // Réponse fournisseur retenue pour le devis
    'retain'         => 'Mantieni per il preventivo',
    'retain_help'    => 'Il prezzo di questa riga diventa il costo dell\'operazione del preventivo (margine interno).',
    'retained'       => 'Prezzo mantenuto per il preventivo: :price.',
    'retained_badge' => 'Mantenuto',
    'retain_refused' => [
        'not_quote_task' => 'Questa riga non riguarda un\'operazione di preventivo.',
        'converted'      => 'Il preventivo è già stato convertito in ordine.',
        'quote_closed'   => 'Il preventivo non è più aperto né inviato.',
        'superseded'     => 'Questa offerta è stata sostituita da una revisione successiva.',
    ],
];
