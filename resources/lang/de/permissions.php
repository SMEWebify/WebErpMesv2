<?php

/*
 * Description des droits, affichée sous le nom technique dans Votre société →
 * Rôles et permissions. Clé = nom de la permission (spatie). Un droit absent
 * de cette liste (créé à la main) s'affiche sans description.
 */
return [
    'descriptions' => [
        // Menus des modules
        'companies-menu' => 'Menü Unternehmen: Kunden, Lieferanten, Ansprechpartner und Adressen.',
        'leads-menu' => 'Menü Interessenten (Leads).',
        'opportunities-menu' => 'Menü Verkaufschancen; zeigt auch die Abschnitte zur Verkaufschance in der Projektakte.',
        'quotes-menu' => 'Menü Angebote; zeigt auch Angebote, Beträge und Blockaden in der Projektakte.',
        'orders-menu' => 'Menü Aufträge; zeigt auch die Aufträge in der Projektakte.',
        'order-confirmations-menu' => 'Eintrag Auftragsbestätigungen (AB) im Menü Aufträge.',
        'scheduling-menu' => 'Menü Planung: Kapazitätsplanung, Aufgabenliste und -status, Kanban, Gantt, Verschachtelung.',
        'deliverys-menu' => 'Menü Lieferscheine; zeigt auch die Lieferscheine in der Projektakte.',
        'returns-menu' => 'Eintrag Retouren (Menüs Lieferscheine und Qualität).',
        'invoices-menu' => 'Menü Rechnungen: Rechnungen, Proformarechnungen, Gutschriften, Mahnungen (Lesen).',
        'products-menu' => 'Lesezugriff auf den Artikelstamm: Liste, Artikelblatt, Arbeitsplan, Stückliste, Dokumente.',
        'products-edit' => 'Schreibzugriff auf den Artikelstamm: Anlegen, Bearbeiten, Duplizieren, Arbeitsplan, Stückliste, Lieferanten, Preise, Import, Dokumente des Artikels.',
        'products-merge' => 'Zwei Artikel zusammenführen (erfordert auch products-edit).',
        'stock-lot-serial-management' => 'Lager: Lagerplätze, Bewegungen, Inventuren, Fehlbestände, Chargen (Schmelze, Werkszeugnis) und Seriennummern.',
        'purchases-menu' => 'Menü Einkauf: Bedarfsanforderungen, Preisanfragen, Bestellungen, Wareneingänge, Lieferantenrechnungen.',
        'quality-menu' => 'Menü Qualität: Dashboard, Maßnahmen, Sonderfreigaben, Abweichungen, Qualitätspläne, FMEA, Prozesse.',
        'audit-menu' => 'Interne Audits (Planung und Durchführung).',
        'settings-time-menu' => 'Menü Zeiten: Übersicht über Zeiten und Teams (Schichten).',
        'methods-menu' => 'Menü Arbeitsvorbereitung: Arbeitsplätze, Ressourcen, Einheiten, Projektkategorien, Verlustgründe…',
        'accounting-menu' => 'Menü Buchhaltung: Zahlungsarten, MwSt., Kontierungen, Versandarten; Export von Rechnungen und FEC.',
        'human-resources-menu' => 'Personalwesen: Mitarbeiter, Spesenabrechnungen, Anwesenheit, Urlaubssalden, Lohnexport, Genehmigung von Abwesenheiten, vertrauliche Personalakte.',
        'osh-menu' => 'Menü Arbeitsschutz: Vorfälle, Risiken, Schulungen, Qualifikationsmatrix, Konformitäten; Verwaltung der Qualifikationsarten.',
        'documents-menu' => 'Menü Dokumente.',
        'reports-menu' => 'Menü Berichte (Dashboard, Buchhaltungsberichte).',
        'spreadsheet-menu' => 'Menü Tabellen.',
        'your-company-menu' => 'Administration: Unternehmenseinstellungen, Rollen und Berechtigungen, Benutzer, Importe / Exporte, Integrationen.',
        'asset_manager' => 'Modul Instandhaltung (CMMS): Dashboard, Anlagen, Wartungspläne, Arbeitsaufträge.',
        'asset-menu' => 'Eintrag Anlagen (Anlagevermögen) in den Menüs Buchhaltung und Instandhaltung.',

        // Droits d'action
        'accounting-manage' => 'MwSt.-Regelungen und -Arten, MwSt.-Matrizen, Sperren von Buchungsperioden.',
        'credit-limit-override' => 'Das Kreditlimit eines Unternehmens ändern und darüber hinaus einen Auftrag oder Lieferschein anlegen (Begründung erforderlich).',
        'invoice-reminders-send' => 'Mahnungen für Rechnungen versenden oder erfassen und die Mahnstufen einstellen.',
        'inspection-plans-edit' => 'Prüfpläne anlegen und beschriften (Ballooning); einen Erstmusterprüfbericht als „geprüft“ unterzeichnen.',
        'inspection-plans-approve' => 'Einen Prüfplan freigeben; einen Erstmusterprüfbericht genehmigen (und einfrieren).',
        'required-operations-override' => 'Ein Angebot in einen Auftrag umwandeln, obwohl ein von der Projektkategorie geforderter Arbeitsgang im Arbeitsplan fehlt (Begründung erforderlich).',
        'audit-access-log' => 'Das Zugriffsprotokoll einsehen und exportieren (wer welches Datenblatt geöffnet hat).',
        'quality-derogation-validate' => 'Eine Sonderfreigabe auf „Freigegeben“ setzen und eine freigegebene Sonderfreigabe bearbeiten.',
        'production-release' => 'Die Fertigungsfreigabe der Auftragspositionen erteilen oder zurücknehmen.',
        'purchase-quality-clauses-manage' => 'Den Katalog der Qualitätsklauseln für Bestellungen und deren Regeln verwalten.',
        'task-release' => 'Die Freigabe eines abgeschlossenen Arbeitsgangs abzeichnen (Arbeitsplätze mit „Freigabe erforderlich“).',
        'dossier-validate' => 'Die Fertigungsdokumentation einer Auftragsposition freigeben, zurücknehmen, verlangen oder aufheben (gibt Lieferschein und Rechnung frei).',
        'reception-document-check' => 'Die Dokumentenkonformität (Zeugnisse) der Wareneingangspositionen abzeichnen.',
        'unqualified-supplier-override' => 'Eine Bestellung bei einem nicht qualifizierten Lieferanten anlegen oder freigeben (Begründung erforderlich).',
        'contract-review-approve' => 'Die Vertragsprüfung eines Auftrags und seine Nachträge abzeichnen (freigeben oder ablehnen); einen Nachtrag stornieren.',
        'order-price-recalculate' => 'Den berechneten Preis einer Auftragsposition aktivieren, deren Auftrag die Vorbereitung verlassen hat (Begründung erforderlich).',
        'affair-risk-close' => 'Ein kritisches Risiko im Risikoregister eines Projekts schließen.',
        'quality-rework-order' => 'Aus einer Abweichung einen Nacharbeitsauftrag starten (Aufgaben auf der Auftragsposition) und den Liefertermin der Position verschieben.',
        'workshop-kiosk' => 'Gemeinsames Werkstatt-Terminalkonto: öffnet nur den Scan-Bildschirm, und jede Zeiterfassung erfordert den Ausweis eines Bedieners. Nur einer eigenen Rolle „Werkstatt-Terminal“ zuweisen.',
    ],
];
