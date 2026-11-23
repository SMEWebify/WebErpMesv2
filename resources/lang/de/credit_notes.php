<?php

return [
    'form' => [
        'title'            => 'Gutschrift erstellen',
        'help'             => 'Markieren Sie die Positionen und passen Sie dann Menge oder Preis für eine Teilgutschrift an (Ausschussteile, Teilrücksendung, Kulanz).',
        'line'             => 'Position',
        'invoiced'         => 'Fakturiert',
        'creditable'       => 'Noch gutschreibbar',
        'qty'              => 'Gutzuschreibende Menge',
        'unit_price'       => 'Gutgeschriebener Stückpreis netto',
        'fully_credited'   => 'Bereits gutgeschrieben',
        'deduction'        => 'Anzahlungsabzug',
        'restore'          => 'Diese Mengen wieder in die zu fakturierende Restmenge aufnehmen (zurückgesandte oder nachgefertigte Teile, erneut zu fakturieren)',
        'restore_help'     => 'Deaktivieren Sie dies für eine Kulanz oder einen Ausschuss, der nicht nachgefertigt wird: der Auftrag bleibt fakturiert.',
        'submit'           => 'Gutschrift erstellen',
    ],

    'line' => [
        'restored'     => 'Wieder zu fakturieren',
        'not_restored' => 'Nicht wieder zu fakturieren',
    ],

    'refused' => [
        'no_line'            => 'Bitte wählen Sie mindestens eine Rechnungsposition aus.',
        'several_invoices'   => 'Eine Gutschrift bezieht sich auf eine einzige Rechnung.',
        'fully_credited'     => 'Die Position :line ist bereits vollständig gutgeschrieben.',
        'qty'                => 'Position :line: die Menge muss positiv sein und darf :max nicht überschreiten.',
        'price'              => 'Position :line: der gutgeschriebene Preis darf den fakturierten Preis (:max €) nicht überschreiten.',
        'amount'             => 'Position :line: der gutgeschriebene Betrag übersteigt den noch gutschreibbaren Betrag (:max € netto).',
        'already_reinvoiced' => 'Die Mengen der Position :line wurden inzwischen erneut fakturiert: die Gutschrift kann nicht mehr abgelehnt werden.',
    ],
];
