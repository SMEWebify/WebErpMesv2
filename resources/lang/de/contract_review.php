<?php

return [
    'tab' => 'Vertragsprüfung',
    'quote_tab' => 'Prüfung',
    'title' => 'Vertragsprüfung',
    'intro' => 'Prüfen Sie vor dem Start des Auftrags, ob er dem Angebot entspricht und ob das Unternehmen ihn erfüllen kann. Die Freigabe friert die Prüfung ein.',

    // Indicateur (index, onglet)
    'status' => [
        'done' => 'Prüfung erfolgt',
        'rejected' => 'Prüfung abgelehnt',
        'todo' => 'Prüfung ausstehend',
    ],
    'column' => 'Prüfung',

    'checklist_title' => 'Geprüfte Punkte',
    'checklist' => [
        'requirements_understood' => 'Kundenanforderungen verstanden und angebotskonform',
        'lead_time_achievable' => 'Gewünschter Liefertermin einhaltbar',
        'contract_documents_received' => 'Vertragsunterlagen erhalten',
        'quality_requirements_identified' => 'Qualitätsanforderungen und anwendbare Regelwerke identifiziert',
    ],
    'checklist_removed' => 'Punkt aus der Konfiguration entfernt',

    'gaps' => 'Abweichungen vom Angebot',
    'gaps_help' => 'Unterschiede zwischen Auftrag und Angebot (Preise, Mengen, Termin, Anforderungen) und wie sie ausgeräumt werden. Pflichtangabe zum Ablehnen oder zum Freigeben mit einem nicht abgehakten Punkt.',
    'no_gaps' => 'Keine Abweichung angegeben.',

    'documents' => 'Vertragsunterlagen',
    'documents_help' => 'Dokumente im DMS des Auftrags vom Typ „Vertragsdokument“.',
    'no_documents' => 'Kein Vertragsdokument im DMS des Auftrags.',
    'open_documents' => 'Dokumente öffnen',

    'save' => 'Vorbereitung speichern',
    'saved' => 'Vorbereitung gespeichert.',
    'approve' => 'Abzeichnen: Prüfung freigegeben',
    'reject' => 'Abzeichnen: Prüfung abgelehnt',
    'approve_confirm' => 'Vertragsprüfung freigeben? Sie wird eingefroren und die Auftragsbestätigung wird vorbereitet.',
    'reject_confirm' => 'Vertragsprüfung ablehnen? Sie wird eingefroren und der Auftrag kann keine Fertigungsfreigabe mehr erhalten.',
    'visa_reserved' => 'Die Freigabe ist berechtigten Personen vorbehalten (Berechtigung „contract-review-approve“).',
    'visa' => 'Abgezeichnet am :date von :user',
    'frozen' => 'Prüfung abgezeichnet: Sie kann nicht mehr geändert werden.',
    'unchecked_warning' => 'Nicht abgehakte Punkte: Beschreiben Sie die Abweichungen vor der Freigabe.',
    'saving' => 'Wird gespeichert…',
    'error' => 'Speichern fehlgeschlagen.',

    // Avertissement de la fiche commande (jamais bloquant)
    'warning_todo' => 'Die Vertragsprüfung dieses Auftrags ist nicht abgezeichnet.',
    'warning_rejected' => 'Die Vertragsprüfung dieses Auftrags wurde abgelehnt.',
    'warning_link' => 'Prüfung öffnen',
    'risk_register' => [
        'warning' => 'Die Projektkategorie verlangt ein Risikoregister, und es ist kein Risiko erfasst. Die Freigabe bleibt möglich.',
        'link' => 'Risiken des Projekts erfassen',
        'no_affair' => 'Der Auftrag ist keinem Projekt (Verkaufschance) zugeordnet: Risiken werden bei der Verkaufschance erfasst.',
    ],

    // Demande de changement
    'change' => [
        'title' => 'Änderungsantrag',
        'help' => 'Nach der Prüfung beantragte Änderung (Index, Menge, Termin…). Solange sie nicht genehmigt ist, wird keine Fertigungsfreigabe erteilt.',
        'pending' => 'Änderung wartet auf Genehmigung',
        'save' => 'Änderung speichern',
        'saved' => 'Änderungsantrag gespeichert.',
    ],

    // Avenants (remplacent la demande de changement)
    'amendment' => [
        'title' => 'Nachträge',
        'help' => 'Ein Nachtrag (Index, Menge, Termin, Preis…) öffnet die Vertragsprüfung erneut: Der Auftrag wechselt wieder auf „Prüfung ausstehend“, und bis zur Freigabe der Prüfung wird keine Fertigungsfreigabe erteilt. Das ursprüngliche Angebot wird nicht geändert.',
        'number' => 'Nachtrag Nr. :number',
        'new' => 'Neuer Nachtrag',
        'reason' => 'Grund',
        'requested_by' => 'Antragsteller',
        'date' => 'Datum',
        'create' => 'Nachtrag anlegen und Prüfung wieder öffnen',
        'create_confirm' => 'Nachtrag anlegen? Die Vertragsprüfung wird bis zu ihrer Freigabe wieder geöffnet.',
        'created' => 'Nachtrag angelegt: Die Vertragsprüfung ist wieder geöffnet.',
        'cancel' => 'Nachtrag stornieren',
        'cancel_confirm' => 'Diesen Nachtrag stornieren? Die Vertragsprüfung kehrt in ihren Zustand vor dem Nachtrag zurück.',
        'cancelled_ok' => 'Nachtrag storniert: Die vorherige Prüfung ist wiederhergestellt.',
        'amount_before' => 'Brutto vorher',
        'amount_after' => 'Brutto nachher',
        'amount_pending' => 'bei Freigabe',
        'decision' => 'Freigabe',
        'none' => 'Kein Nachtrag.',
        'pending_exists' => 'Ein Nachtrag ist bereits offen: Zeichnen Sie ihn ab (Vertragsprüfung) oder stornieren Sie ihn, bevor Sie einen weiteren anlegen.',
        'reopened' => 'Prüfung durch Nachtrag Nr. :number wieder geöffnet: Ihre Freigabe zeichnet den Nachtrag ab.',
        'warning' => 'Nachtrag Nr. :number wartet auf die Freigabe der Vertragsprüfung.',
        'status' => [
            'pending' => 'Wartet auf Freigabe',
            'approved' => 'Freigegeben',
            'rejected' => 'Abgelehnt',
            'cancelled' => 'Storniert',
        ],
        'legacy' => 'Früherer Änderungsantrag',
        'legacy_help' => 'Vor Einführung der Nachträge erfasst, für die Historie aufbewahrt.',
        'legacy_approved_at' => 'Genehmigt am',
        'errors' => [
            'not_applicable' => 'Ein interner Auftrag hat keine Nachträge.',
            'already_pending' => 'Für diesen Auftrag wartet bereits ein Nachtrag auf Freigabe.',
            'closed' => 'Dieser Nachtrag ist bereits abgeschlossen.',
        ],
    ],

    'history' => 'Historie der Prüfung',

    // Refus
    'errors' => [
        'forbidden' => 'Sie sind nicht berechtigt, eine Vertragsprüfung abzuzeichnen.',
        'frozen' => 'Die Vertragsprüfung ist abgezeichnet: Sie kann nicht mehr geändert werden.',
        'not_applicable' => 'Ein interner Auftrag hat keine Vertragsprüfung.',
        'invalid_decision' => 'Unbekannte Entscheidung.',
        'gaps_required_reject' => 'Beschreiben Sie die Abweichungen vom Angebot, um die Prüfung abzulehnen.',
        'gaps_required_unchecked' => 'Nicht abgehakte Punkte (:items): Beschreiben Sie die Abweichungen vom Angebot, um die Prüfung freizugeben.',
    ],
];
