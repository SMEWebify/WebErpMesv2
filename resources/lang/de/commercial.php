<?php

// Écrans de présentation des fonctionnalités réservées à la version commerciale (Nest2Prod).
return [
    'badge'   => 'Kommerzielle Version',
    'contact' => 'Nest2Prod kontaktieren',
    'press_brake' => [
        'title' => 'Abkantpressen-Werkzeuge',
        'lead'  => 'Der Stempel-Konfigurator und der Werkzeugkatalog der Hersteller sind in der kommerziellen Version des ERP von Nest2Prod verfügbar.',
        'designer_title' => 'Maßgefertigte Stempel',
        'designer_text'  => 'Parametrisches Profil oder aus den Herstellerbibliotheken.',
        'simulation_title' => 'Biegesimulation',
        'simulation_text'  => 'Prüfung des gekanteten Teils und der Kollisionen mit dem Werkzeug.',
        'catalog_title' => 'Herstellerkatalog',
        'catalog_text'  => 'Stempel, Matrizen und Adapter zum Durchsuchen und Filtern.',
        'screenshot_alt'     => 'Abkantsimulation in NEST2BEND: Teil, Werkzeug, Biegefolge und Kollisionserkennung',
        'screenshot_caption' => 'Simulation der Biegung 2 in NEST2BEND: Die Kollision mit dem Stempel wird erkannt, bevor es an die Presse geht.',
    ],
    'nesting' => [
        'title' => 'Verschachtelung der Teile auf dem Blech',
        'lead'  => 'Die Verschachtelungsberechnung in exakter Kontur und der daraus folgende Materialbedarf sind in der kommerziellen Version des ERP von Nest2Prod verfügbar.',
        'shape_title' => 'Exakte Kontur',
        'shape_text'  => 'Platzierung entlang der tatsächlichen Kontur der DXF-/SVG-Teile, mit Drehungen und Schnittabstand.',
        'sheets_title' => 'Schneidfertige Bleche',
        'sheets_text'  => 'Vorschau jedes Blechs und DXF-Export der Platzierung.',
        'stock_title' => 'Materialbedarf',
        'stock_text'  => 'Anzahl der Bleche und Stangen, abgeglichen mit Lagerbestand und offenen Bestellungen.',
    ],
    'digital_twin' => [
        'title' => 'Digitaler Zwilling der Werkstatt',
        'lead'  => 'Die Darstellung der Werkstatt und die Simulation der Fertigungsflüsse sind in der kommerziellen Version des ERP von Nest2Prod verfügbar.',
        'video' => 'Präsentationsvideo des digitalen Zwillings',
        'flow_title' => 'Live-Fluss',
        'flow_text'  => 'Die Fertigungsaufträge laufen im Takt des tatsächlichen Fortschritts zwischen den Arbeitsplätzen.',
        'simulation_title' => 'Szenario-Simulation',
        'simulation_text'  => 'Einen Eilauftrag, einen Ausfall oder einen Schichtwechsel testen, bevor er in die Planung übernommen wird.',
        'bottleneck_title' => 'Engpässe',
        'bottleneck_text'  => 'Warteschlangen und Auslastung je Arbeitsplatz, um zu erkennen, wo der Fluss stockt.',
    ],
];
