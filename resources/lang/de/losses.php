<?php

return [
    'label' => 'Verlustgrund',
    'plural' => 'Verlustgründe',
    'none' => 'Nicht angegeben',
    'inactive' => 'inaktiv',
    'info' => 'Gründe, die angeboten werden, wenn ein Angebot in den Status Verloren oder eine Verkaufschance in Verloren wechselt. Der Grund wird abgefragt, sobald mindestens ein Grund aktiv ist; vor seiner Einführung verlorene Dokumente bleiben ohne Grund gültig. Ein bereits verwendeter Grund kann nicht gelöscht werden: Deaktiviert wird er nicht mehr angeboten, bleibt aber auf den Dokumenten sichtbar, die ihn tragen.',
    'new' => 'Neuer Grund',
    'edit' => ':label bearbeiten',
    'code' => 'Code',
    'order' => 'Reihenfolge',
    'active' => 'Aktiv',
    'usage' => 'Verwendung',
    'usage_detail' => ':quotes Angebot(e), :opportunities Verkaufschance(n)',
    'created' => 'Verlustgrund angelegt.',
    'updated' => 'Verlustgrund aktualisiert.',
    'code_locked' => 'Der Code kann nicht geändert werden.',

    'defaults' => [
        'PRICE' => 'Preis',
        'DELAY' => 'Lieferzeit',
        'TECHNICAL' => 'Technisch',
        'COMPETITOR' => 'Wettbewerber',
        'NO_FOLLOW_UP' => 'Ohne Folge',
    ],

    'dialog' => [
        'title_quote' => 'Angebot verloren: warum?',
        'title_opportunity' => 'Verkaufschance verloren: warum?',
        'reason' => 'Grund',
        'choose' => '— Grund wählen —',
        'comment' => 'Kommentar',
        'comment_help' => 'Gewählter Wettbewerber, Preisabweichung, Angabe des Kunden…',
        'cancel' => 'Abbrechen',
        'confirm' => 'Speichern',
        'edit' => 'Bearbeiten',
        'fill' => 'Angeben',
        'error' => 'Fehler beim Speichern des Grundes.',
    ],

    'required' => 'Geben Sie den Verlustgrund an.',
    'not_lost' => 'Der Verlustgrund wird nur auf einem verlorenen Dokument erfasst.',

    'stats' => [
        'title' => 'Verlorene Geschäfte',
        'by_reason' => 'Nach Grund',
        'by_category' => 'Nach Projektkategorie',
        'count' => 'Angebote',
        'amount' => 'Betrag netto',
        'share' => 'Anteil',
        'total' => 'Gesamt',
        'no_category' => 'Ohne Kategorie',
        'empty' => 'Kein verlorenes Angebot im Zeitraum.',
        'period' => 'Zeitraum',
        'periods' => [
            '12m' => 'Letzte 12 Monate',
            'year' => 'Laufendes Jahr',
            'all' => 'Seit Beginn',
        ],
        'period_help' => 'Im Zeitraum angelegte, verlorene Angebote.',
    ],
];
