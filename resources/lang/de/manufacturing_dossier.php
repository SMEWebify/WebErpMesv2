<?php

return [
    'label' => 'Fertigungsdokumentation',
    'short' => 'Dokumentation',
    'validated' => 'Dokumentation freigegeben',
    'not_validated' => 'Dokumentation freizugeben',
    'pending_title' => 'Fertigungsdokumentation erforderlich und nicht freigegeben: Lieferschein und Rechnung werden abgelehnt.',
    'validated_on' => 'Dokumentation freigegeben am :date von :user',
    'source_siteflow' => 'Siteflow',

    // Refus à la création d'un BL ou d'une facture
    'blocked' => '{1} Fertigungsdokumentation nicht freigegeben für die Position: :lines. Weder Lieferschein noch Rechnung, solange sie nicht freigegeben ist.|[2,*] Fertigungsdokumentation nicht freigegeben für die Positionen: :lines. Weder Lieferschein noch Rechnung, solange sie nicht freigegeben ist.',

    // Refus sur la ligne
    'not_article' => 'Eine Gliederungsposition hat keine Fertigungsdokumentation.',
    'already_validated' => 'Die Dokumentation dieser Position ist bereits freigegeben.',
    'not_validated_yet' => 'Die Dokumentation dieser Position ist nicht freigegeben.',
    'already_shipped' => 'Die Position ist bereits geliefert oder fakturiert, auch teilweise: die Freigabe der Dokumentation kann nicht mehr zurückgenommen werden.',

    // Actions groupées de la fiche commande
    'actions' => 'Dokumentation',
    'action_validate' => 'Dokumentation freigeben',
    'action_revoke' => 'Freigabe zurücknehmen',
    'action_require' => 'Dokumentation verlangen',
    'action_waive' => 'Dokumentation nicht mehr verlangen',
    'revoke_confirm' => 'Freigabe der Dokumentation für die ausgewählten Positionen zurücknehmen?',
    'waive_confirm' => 'Für die ausgewählten Positionen keine Fertigungsdokumentation mehr verlangen? Sie können dann ohne sie geliefert und fakturiert werden.',
    'bulk_none' => 'Keine Position ausgewählt.',
    'bulk_refused' => 'Nicht verarbeitet:',
    'bulk_result' => [
        'validate' => ':count Position(en) mit freigegebener Dokumentation.',
        'revoke' => ':count Freigabe(n) zurückgenommen.',
        'require' => ':count Position(en) verlangen nun die Dokumentation.',
        'waive' => ':count Position(en) verlangen die Dokumentation nicht mehr.',
    ],
];
