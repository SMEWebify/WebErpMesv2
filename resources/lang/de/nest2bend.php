<?php

return [
    // Écran de configuration
    'title'          => 'NEST2BEND',
    'subtitle'       => 'Biegesimulation auf der Abkantpresse',
    'config'         => 'Anbindung an NEST2BEND',
    'base_url'       => 'URL von NEST2BEND',
    'base_url_hint'  => 'Zum Beispiel https://app.nest2bend.fr',
    'api_key'        => 'API-Schlüssel',
    'api_key_hint'   => 'In NEST2BEND erstellter n2b_…-Schlüssel. Er wird hier verschlüsselt gespeichert und nie wieder angezeigt.',
    'api_key_keep'   => 'leer lassen, um ihn beizubehalten',
    'clear_key'      => 'Schlüssel löschen (zurück zur Produktinformation)',
    'save'           => 'Speichern',
    'saved'          => 'NEST2BEND-Anbindung gespeichert.',
    'test_button'    => 'Verbindung testen',
    'test_running'   => 'Test läuft…',
    'test_ok'        => 'Verbindung erfolgreich: :societe.',
    'test_network_error' => 'Der Test ist fehlgeschlagen:',
    'open_app'       => 'NEST2BEND öffnen',
    'configure'      => 'Konfigurieren',
    'card_lead'      => 'Biegesimulation im iframe aus Angebotspositionen mit einer STEP-Datei.',
    'origin_title'   => 'In NEST2BEND anzugebender Ursprung',
    'origin_intro'   => 'NEST2BEND lässt sich nur in Ursprüngen anzeigen, die es kennt. Geben Sie diesen an (Schema, Host und Port, ohne Pfad):',
    'what_title'     => 'So funktioniert es',
    'what_1'         => 'Bei einer Angebotsposition, deren Dokumentenverwaltung eine STEP-Datei enthält, öffnet die Schaltfläche „Simulieren“ NEST2BEND im ERP.',
    'what_2'         => 'Die Datei wird vom ERP-Server gesendet; der API-Schlüssel verlässt den Server nie.',
    'what_3'         => 'Der Benutzer prüft das Teil und die Simulation in NEST2BEND, ohne sich dort anzumelden.',
    'what_4'         => 'Die übernommene Zeit (Rüstzeit und Stückzeit) wird dem internen Kommentar der Position hinzugefügt.',
    'checked_at'     => 'Letzte Prüfung',
    'never_checked'  => 'nie',
    'societe'        => 'Unternehmen',
    'etat'           => 'Abonnement',
    'echeance'       => 'Fälligkeit',
    'modules'        => 'Module',
    'presses_max'    => 'Abkantpressen',
    'last_error'     => 'Letzter Vorfall',

    'badge' => [
        'none'    => 'Nicht konfiguriert',
        'unknown' => 'Zu testen',
        'invalid' => 'Schlüssel abgelehnt',
        'expired' => 'Abonnement abgelaufen',
        'active'  => 'Aktiv',
    ],

    'state' => [
        'none'    => 'Es ist kein NEST2BEND-Schlüssel gespeichert.',
        'unknown' => 'Der Schlüssel konnte noch nicht geprüft werden: Testen Sie die Verbindung.',
        'invalid' => 'Ungültiger oder widerrufener Schlüssel.',
        'expired' => 'NEST2BEND-Abonnement abgelaufen.',
        'warning' => 'NEST2BEND-Abonnement im Status „:etat“ bis zum :echeance.',
    ],

    'etats' => [
        'actif'         => 'laufend',
        'essai'         => 'Testphase',
        'grace'         => 'Kulanzfrist',
        'lecture_seule' => 'nur Lesen',
        ''              => '—',
    ],

    // Bouton « Simuler » (ligne de devis)
    'button' => [
        'label'      => 'Abkanten simulieren',
        'starting'   => 'NEST2BEND wird geöffnet…',
        'saving'     => 'Zeit wird gespeichert…',
        'no_step'    => 'Fügen Sie die STEP-Datei des Teils zu den Dokumenten der Position hinzu.',
        'step_file'  => 'Gesendete Datei: :name',
        'close'      => 'Schließen',
        'modal_title'=> 'NEST2BEND — Biegesimulation',
        'remarks'    => 'Hinweise von NEST2BEND',
        'renew'      => 'Abonnement verlängern',
        'restart'    => 'Neu starten',
        'restart_hint' => 'Seite nicht gefunden oder Simulation verloren? Öffnet eine neue NEST2BEND-Sitzung.',
    ],

    'result_saved' => 'Biegezeit zum internen Kommentar hinzugefügt: :summary',

    // Fiche de réglage PDF (champ `fiche` du résultat), rangée dans la GED de la ligne
    'sheet' => [
        'comment'           => 'NEST2BEND-Einrichteblatt (Abkantpresse)',
        'stored'            => 'Einrichteblatt in den Dokumenten der Position abgelegt: :name',
        'pending'           => 'Einrichteblatt wird abgerufen…',
        'retry'             => 'Einrichteblatt abrufen',
        'retrying'          => 'Einrichteblatt wird abgerufen…',
        'open'              => 'Einrichteblatt öffnen',
        'none'              => 'Für diese Position ist kein Einrichteblatt abzurufen.',
        'error_404'         => 'Einrichteblatt nicht verfügbar: Teil nicht simuliert, kein Oberwerkzeug gewählt, oder ein Werkzeug bzw. die Presse wurde seit der Simulation aus der NEST2BEND-Werkstatt entfernt.',
        'error_key'         => 'Einrichteblatt nicht abgerufen: NEST2BEND-API-Schlüssel abgelehnt oder Abonnement abgelaufen.',
        'error_unavailable' => 'Einrichteblatt nicht abgerufen: NEST2BEND nicht verfügbar. Versuchen Sie es später erneut (das Ergebnis bleibt 30 Tage lesbar).',
        'error_other'       => 'Einrichteblatt nicht abgerufen (Status :status).',
    ],

    // Dernière simulation de la ligne (resultat.simulation)
    'last' => [
        'title'    => 'Letzte Simulation',
        'press'    => 'Presse',
        'tools'    => 'Werkzeuge',
        'sequence' => 'Biegereihenfolge',
        'sheet'    => 'Einrichteblatt',
        'punch'    => 'Oberwerkzeug :tool',
        'die'      => 'Unterwerkzeug :tool',
        'flipped'  => '(gewendet)',
    ],

    'comment' => [
        'entry'       => '[NEST2BEND :date] :summary',
        'preparation' => 'Rüsten :time',
        'unit'        => ':time/Teil (Los :qty)',
        'thickness'   => 'Dicke :t mm',
        'no_time'     => 'keine Zeit übernommen',
    ],

    'errors' => [
        'unauthorized'    => 'Ungültiger oder widerrufener NEST2BEND-API-Schlüssel.',
        'inactive'        => 'NEST2BEND-Abonnement abgelaufen.',
        'not_found'       => 'NEST2BEND-Ergebnis für diese Position nicht gefunden.',
        'invalid'         => 'NEST2BEND hat die Anfrage abgelehnt.',
        'unavailable'     => 'NEST2BEND vorübergehend nicht verfügbar, bitte erneut versuchen.',
        'rate_limited'    => 'Zu viele Anfragen an NEST2BEND, bitte in einer Minute erneut versuchen.',
        'unexpected'      => 'Unerwartete Antwort von NEST2BEND.',
        'step_unreadable' => 'Die STEP-Datei wurde auf dem Server nicht gefunden.',
        'step_too_large'  => 'Die STEP-Datei überschreitet 20 MB.',
        'origin_mismatch' => 'NEST2BEND hat von :received geantwortet, die im ERP eingetragene URL ist jedoch :configured. Beide müssen identisch sein (APP_URL von NEST2BEND).',
    ],

    // Écran presse plieuse (methods.tool.punch-designer) quand NEST2BEND est relié
    'panel' => [
        'title'     => 'NEST2BEND ist mit dem ERP verbunden',
        'lead'      => 'Die Biegesimulation wird aus einer Angebotsposition gestartet, deren Dokumente die STEP-Datei des Teils enthalten (Schaltfläche „Abkanten simulieren“).',
        'expired'   => 'Das NEST2BEND-Abonnement ist abgelaufen: Simulationen sind ausgesetzt.',
    ],

    // Postes de charge
    'is_bending' => 'Abkant-Arbeitsplatz (Abkantpresse)',
];
