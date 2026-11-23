<?php

return [
    'saved'    => 'Lieferantenbewertung gespeichert.',
    'no_lines' => 'Wareneingang ohne Position: keine Bestellung, der die Bewertung zugeordnet werden kann.',

    // Fenêtre de notation rapide (fiche réception)
    'window' => [
        'card_title'      => 'Lieferantenbewertung',
        'title'           => 'Lieferanten bewerten',
        'intro'           => 'Optional: wenige Sekunden, um die Qualität Ihrer Lieferanten zu verfolgen.',
        'conformity'      => 'Konformität',
        'conformity_help' => 'Teile entsprechen der Bestellung',
        'delay'           => 'Termin',
        'delay_help'      => 'Einhaltung des zugesagten Termins',
        'documents'       => 'Dokumente',
        'documents_help'  => 'Lieferschein, Zeugnisse, Kennzeichnung',
        'comment'         => 'Kommentar',
        'later'           => 'Später',
        'save'            => 'Speichern',
        'saving'          => 'Wird gespeichert…',
        'rate'            => 'Bewerten',
        'edit'            => 'Bearbeiten',
        'not_rated'       => 'Wareneingang nicht bewertet.',
        'no_lines'        => 'Fügen Sie eine Position hinzu, um den Lieferanten bewerten zu können.',
        'days_late'       => ':days T. Verzug gegenüber dem zugesagten Termin',
        'on_time'         => 'Zum zugesagten Termin geliefert',
        'no_promised'     => 'Kein zugesagter Termin auf den Bestellpositionen',
        'suggested'       => 'vorgeschlagen',
        'has_nc'          => 'Abweichung bei diesem Wareneingang: Die Qualitätsnote zählt als :score/5.',
        'rolling'         => 'Gleitende Bewertung :months Monate',
        'rolling_detail'  => ':count Bewertung(en), :nc nicht konforme(r) Wareneingang/Wareneingänge',
        'no_rolling'      => 'Keine Bewertung in :months Monaten',
        'error'           => 'Fehler beim Speichern',
    ],

    // Fiche fournisseur
    'company' => [
        'rolling'        => 'Gleitende Bewertung (:months Monate)',
        'since'          => 'seit dem :date',
        'quality'        => 'Qualität',
        'logistics'      => 'Termin',
        'service'        => 'Dokumente / Service',
        'count'          => ':count Bewertung(en)',
        'nc_receipts'    => ':count Wareneingang/Wareneingänge mit NC',
        'none'           => 'Keine Bewertung und kein nicht konformer Wareneingang im Zeitraum.',
        'receipt'        => 'Wareneingang',
    ],

    // Taux de livraison à l'heure (App\Services\Purchases\SupplierOnTimeDelivery)
    'on_time' => [
        'title'     => 'Termintreue Lieferungen (:months Monate)',
        'global'    => 'Alle Lieferanten',
        'rate'      => 'Termingerecht',
        'lines'     => 'Positionen',
        'detail'    => ':on_time von :measured Position(en) termingerecht',
        'undated'   => ':count Position(en) ohne Wunsch- oder bestätigten Termin, nicht gezählt',
        'tolerance' => 'Toleranz: :days T.',
        'rule'      => 'Vollständig eingegangene Position, zum Datum ihres letzten Wareneingangs, gegenüber dem bestätigten (sonst gewünschten) Termin.',
        'none'      => 'Keine eingegangene und terminierte Position im Zeitraum.',
        'worst'     => 'Am wenigsten termintreu',
    ],
];
