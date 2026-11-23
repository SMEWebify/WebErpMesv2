<?php

// Textes des briques React partagées (DataTable, Pagination, dépôt CAO), posés par le gabarit dans <meta name="ui-common"> (lib/i18n.js, commonText).
return [
    'show_column' => 'Spalte wieder einblenden',
    'hide_column' => 'Spalte ausblenden',
    'date_from' => 'Von',
    'date_to' => 'Bis',
    'results' => 'Ergebnisse',
    'result' => 'Ergebnis',
    'cad_no_file' => 'Keine erkannte Datei (:formats).',
    'cad_import_error' => 'Fehler beim Import.',
    'cad_network_error' => 'Netzwerkfehler beim Senden.',
    'cad_importing' => 'Import läuft…',
    'cad_drop' => 'Dateien ablegen',
    'cad_help' => 'Die abgelegte Zeichnung wird der Position in der Dokumentenverwaltung zugeordnet (außer .sym, .drg und .bvc, die beim Import vollständig eingelesen werden). Eine .geo-Datei erzeugt zusätzlich ein für die Verschachtelung nutzbares SVG. Eine RADAN-Verschachtelung (.drg) legt eine Position pro Teil an, Menge = Teile pro Blech × Anzahl Bleche. Ein BySoft-Job (.bvc) legt eine Position pro Teil mit seiner Kontur als SVG an, Menge = Menge des Jobs. Eine .pdf-Zeichnung wird von der KI gelesen (Integrationen → KI): Schriftfeld an der angelegten Position prüfen.',
    'cad_drop_help' => 'hier ablegen, um die Positionen automatisch anzulegen — oder klicken, um auszuwählen',
];
