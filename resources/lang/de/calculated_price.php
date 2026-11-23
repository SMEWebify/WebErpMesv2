<?php

return [
    'enable' => 'Berechneten Preis aktivieren',
    'disable' => 'Berechneten Preis deaktivieren',
    'disable_title' => 'Der aktuell berechnete Preis wird als Verkaufspreis der Position gespeichert.',
    'reason_title' => 'Auftrag freigegeben: der Preis muss festgeschrieben bleiben. Grund für die Reaktivierung des berechneten Preises?',
    'reason_placeholder' => 'Grund (Pflichtfeld)',
    'confirm' => 'Aktivieren',
    'cancel' => 'Abbrechen',

    'errors' => [
        'not_article' => 'Eine Gliederungsposition hat keinen berechneten Preis.',
        'shipped' => 'Die Position ist bereits geliefert oder fakturiert, auch teilweise: ihr Preis kann nicht mehr neu berechnet werden.',
        'order_locked' => 'Der Auftrag ist nicht mehr in Vorbereitung (Status über „Offen“ hinaus oder Vertragsprüfung freigegeben): der Preis ist festgeschrieben.',
        'reason_required' => 'Für die Reaktivierung des berechneten Preises bei einem freigegebenen Auftrag ist ein Grund erforderlich.',
    ],
];
