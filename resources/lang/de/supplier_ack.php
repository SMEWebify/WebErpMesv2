<?php

return [
    // Liste « sans AR »
    'menu'                => 'Bestellungen ohne AB',
    'missing_title'       => 'Bestellungen ohne Lieferanten-Auftragsbestätigung',
    'missing_info'        => 'Seit mindestens :days Tag(en) aufgegebene Bestellungen, bei denen eine Position noch keine Auftragsbestätigung des Lieferanten hat. Erfassen Sie die AB in der Bestellung (Registerkarte Positionen, Schaltfläche „AB erhalten“).',
    'missing_none'        => 'Keine Bestellung wartet auf eine Auftragsbestätigung.',
    'ordered_at'          => 'Bestellt am',
    'waiting_days'        => 'Wartezeit',
    'days'                => '{0} heute|{1} :count Tag|[2,*] :count Tage',
    'lines_without_ack'   => 'Positionen ohne AB',
    'next_requested_date' => 'Erster Wunschtermin',

    'confirmed_date_hint' => 'Vom Lieferanten bestätigter Termin (AB)',

    // Fiche commande d'achat (PurchaseLinesPage, SupplierAckModal)
    'screen' => [
        'action'          => 'AB erhalten',
        'title'           => 'Lieferanten-Auftragsbestätigung',
        'ack_at'          => 'Datum der AB',
        'reference'       => 'Referenz der AB',
        'file'            => 'Dokument (optional)',
        'hint'            => 'Markieren Sie die bestätigten Positionen und geben Sie den vom Lieferanten genannten Liefertermin ein (standardmäßig der Wunschtermin). Er wird zum Referenztermin für Verzögerungen. Das Dokument wird in der Registerkarte Dokumente mit der Rolle „Lieferanten-AB“ abgelegt.',
        'all_lines'       => 'Alle Positionen',
        'line'            => 'Position',
        'requested'       => 'Wunschtermin',
        'confirmed'       => 'Bestätigter Termin',
        'gap'             => 'Abweichung',
        'already'         => 'AB bereits erhalten',
        'day_unit'        => 'T.',
        'save'            => 'AB speichern',
        'saving'          => 'Wird gespeichert…',
        'cancel'          => 'Abbrechen',
        'saved'           => 'Auftragsbestätigung gespeichert',
        'error'           => 'Fehler beim Speichern der AB',
        'file_error'      => 'AB gespeichert, aber das Dokument konnte nicht abgelegt werden:',
        'no_line'         => 'Markieren Sie mindestens eine Position.',
        'missing'         => 'Ohne AB',
        'ack_short'       => 'AB vom',
        'requested_short' => 'gewünscht',
        'confirmed_title' => 'Vom Lieferanten bestätigter Termin (Referenztermin)',
        'requested_title' => 'Wunschtermin (noch nicht bestätigt)',
    ],

    // Saisie
    'not_ordered'         => 'Die Auftragsbestätigung wird auf einer aufgegebenen Bestellung erfasst (weder in Bearbeitung noch storniert).',
    'foreign_line'        => 'Eine der Positionen gehört nicht zu dieser Bestellung.',
];
