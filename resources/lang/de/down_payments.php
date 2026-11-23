<?php

return [
    'document_title'  => "ANZAHLUNGSRECHNUNG",
    'type_label'      => 'Anzahlung',
    'invoice_label'   => 'Anzahlung auf Auftrag :order',
    'line_label'      => 'Anzahlung :percent % auf Auftrag :order',
    'deduction_label' => 'Abzug Anzahlung :code',

    'card' => [
        'title'          => 'Anzahlungen',
        'none'           => 'Keine Anzahlungsrechnung.',
        'order_total'    => 'Auftragssumme netto',
        'invoice'        => 'Rechnung',
        'ht'             => 'Netto',
        'paid'           => 'Bezahlt brutto',
        'deducted'       => 'Abgezogen netto',
        'draft'          => 'Entwurf',
        'mode_percent'   => '%',
        'mode_amount'    => '€ netto',
        'value'          => 'Betrag',
        'create'         => 'Anzahlungsrechnung',
        'help'           => 'Die Anzahlung ändert die Mengen nicht: Sie wird von den folgenden Rechnungen anteilig zum Abgerechneten abgezogen, bis zur Höhe des bezahlten Betrags.',
    ],

    'invoice' => [
        'deducts'        => 'Zieht die Anzahlung ab',
        'recompute'      => 'Anzahlungsabzug neu berechnen',
        'recomputed'     => 'Anzahlungsabzug neu berechnet.',
        'is_down_payment'=> 'Anzahlungsrechnung des Auftrags :order: netto :ht, bereits abgezogen :deducted, noch abzuziehen :remaining.',
        'unpaid_warning' => 'Anzahlung :code nicht bezahlt: Sie wird von dieser Rechnung nicht abgezogen.',
    ],

    'created' => 'Anzahlungsrechnung :code als Entwurf angelegt.',

    'refused' => [
        'internal_order'  => 'Ein interner Auftrag erhält keine Anzahlung.',
        'canceled_order'  => 'Auftrag storniert: keine Anzahlung.',
        'empty_order'     => 'Der Auftrag hat keinen abzurechnenden Betrag.',
        'amount'          => 'Der Anzahlungsbetrag muss positiv sein.',
        'over_total'      => 'Die Anzahlungen würden die Auftragssumme überschreiten (noch möglich: :max € netto).',
        'not_draft'       => 'Die Rechnung ist kein Entwurf mehr.',
        'not_a_deduction' => 'Diese Position ist kein Anzahlungsabzug.',
        'deduction_max'   => 'Abzug über den bezahlten und noch nicht abgezogenen Anteil der Anzahlung hinaus nicht möglich (:max € netto).',
        'credit_note_on_deduction' => 'Ein Anzahlungsabzug wird nicht per Gutschrift zurückgenommen: Wählen Sie die verkauften Positionen.',
        'credit_note_on_deducted'  => 'Die Anzahlung :code ist bereits von einer Rechnung abgezogen: Erstellen Sie die Gutschrift auf die Schlussrechnung.',
    ],
];
