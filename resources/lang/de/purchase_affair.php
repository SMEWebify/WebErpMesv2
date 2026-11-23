<?php

return [
    // Rattachement d'un achat à une affaire (en-tête)
    'link'                => 'Zugeordnetes Projekt',
    'none'                => 'Keines',
    'help'                => 'Für einen Einkauf ohne Arbeitsgang (Nebenkosten, Sonderwerkzeug): er erscheint in der Projektakte.',
    'invalid'             => 'Das zugeordnete Dokument wurde nicht gefunden.',
    'group_orders'        => 'Aufträge',
    'group_quotes'        => 'Angebote',
    'group_opportunities' => 'Verkaufschancen',

    // Demande d'achat depuis un devis
    'source'                  => 'Arbeitsgänge aus',
    'source_orders'           => 'Feste Aufträge',
    'source_quotes'           => 'Offene Angebote',
    'source_quotes_help'      => 'Arbeitsgänge noch nicht beauftragter Angebote: nur Preisanfrage, um das Angebot zu kalkulieren.',
    'quote'                   => 'Angebot',
    'order_needs_firm_order'  => 'Eine Bestellung kann nur Arbeitsgänge fester Aufträge betreffen. Für ein Angebot starten Sie eine Preisanfrage.',
    'quotation_tasks_invalid' => 'Einige Arbeitsgänge stammen weder aus einem festen Auftrag noch aus einem offenen Angebot.',
    'quote_not_orderable'     => 'Position eines noch nicht beauftragten Angebots: übernehmen Sie den Preis für das Angebot, die Bestellung erfolgt, sobald das Angebot in einen Auftrag umgewandelt ist.',

    // Réponse fournisseur retenue pour le devis
    'retain'         => 'Für das Angebot übernehmen',
    'retain_help'    => 'Der Preis dieser Position wird zu den Kosten des Arbeitsgangs im Angebot (interne Marge).',
    'retained'       => 'Für das Angebot übernommener Preis: :price.',
    'retained_badge' => 'Übernommen',
    'retain_refused' => [
        'not_quote_task' => 'Diese Position betrifft keinen Arbeitsgang eines Angebots.',
        'converted'      => 'Das Angebot wurde bereits in einen Auftrag umgewandelt.',
        'quote_closed'   => 'Das Angebot ist weder offen noch versendet.',
        'superseded'     => 'Dieses Angebot wurde durch einen neueren Index ersetzt.',
    ],
];
