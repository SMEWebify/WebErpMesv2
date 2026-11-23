<?php

return [
    'tab' => 'E-Mail',
    'signature' => 'E-Mail-Signatur',
    'signature_hint' => "Wird unter der Nachricht jeder aus dem ERP gesendeten E-Mail eingefügt (Angebote, Aufträge, Lieferscheine, Rechnungen, Einkäufe, Mahnungen). Sie erscheint vor dem Senden im Editor: Sie können sie für einen Versand anpassen.",
    'signature_image_hint' => "Ein in den Editor eingefügtes Logo wird von Gmail und Outlook oft blockiert: verwenden Sie besser ein über seine Webadresse eingefügtes Bild (https://…).",
    'signature_saved' => 'Signatur gespeichert.',
    'variables' => 'Variablen',
    'variables_hint' => "Werden beim Öffnen des Versandbildschirms im Betreff und in der Nachricht ersetzt.",
    'reminder_variables_hint' => 'Rechnungsmahnungen: {client}, {factures}, {total_du}, {retard}, {entreprise}, {utilisateur} und {signature}.',
    'variable' => [
        'code' => 'Dokumentnummer',
        'libelle' => 'Bezeichnung des Dokuments',
        'client' => 'Unternehmen (Kunde oder Lieferant)',
        'contact' => 'Ansprechpartner des Dokuments',
        'utilisateur' => "Name des Absenders",
        'entreprise' => 'Ihr Unternehmen',
        'signature' => "Signatur des Absenders (sonst am Ende angefügt)",
    ],
];
