<?php

return [

    'credit' => [
        // Contrôle à la création d'une commande ou d'un BL
        'warning'                  => 'Offene Posten des Kunden über dem Kreditlimit: :projected bei einem Kreditlimit von :limit.',
        'blocked_title'            => 'Kreditlimit überschritten',
        'blocked'                  => 'Dieser Kunde ist gesperrt: Seine offenen Posten würden :projected bei einem Kreditlimit von :limit erreichen (aktuelle offene Posten :outstanding).',
        'no_permission'            => 'Nur ein berechtigter Benutzer kann das Kreditlimit übergehen.',
        'override'                 => 'Für dieses Dokument erzwingen',
        'override_reason'          => 'Grund der Überschreitung',
        'override_reason_help'     => 'Pflichtfeld. Wird mit dem Dokument, seinem Verfasser und dem Datum gespeichert.',
        'this_document'            => 'Dieses Dokument',
        'projected'                => 'Offene Posten nach diesem Dokument',
        'override_reason_required' => 'Der Grund der Überschreitung ist ein Pflichtfeld.',

        // Fiche société
        'card_title'        => 'Offene Posten des Kunden',
        'limit'             => 'Kreditlimit (brutto)',
        'limit_help'        => 'Leer = kein Kreditlimit.',
        'block_level'       => 'Bei Überschreitung des Kreditlimits',
        'level_none'        => 'Keine Prüfung',
        'level_warn'        => 'Warnung, ohne Sperre',
        'level_block'       => 'Sperre von Aufträgen und Lieferscheinen',
        'payment_defaults_help' => 'Werden beim Anlegen von Angeboten und Aufträgen dieses Kunden vorgeschlagen. Leer = Standardwerte der Instanz.',
        'read_only'         => 'Bearbeitbar durch Benutzer, die das Kreditlimit übergehen dürfen.',
        'outstanding'       => 'Offene Posten',
        'invoices_due'      => 'Nicht ausgeglichene Rechnungen',
        'open_orders'       => 'Nicht berechnete Aufträge',
        'no_limit'          => 'Kein Kreditlimit',
        'of_limit'          => 'des Kreditlimits',
        'overrides_title'   => 'Letzte Ausnahmen',
        'override_by'       => 'von',
    ],

    'reminders' => [
        'title'              => 'Zu mahnende Rechnungen',
        'subtitle'           => 'Fällige, nicht ausgeglichene Rechnungen. Kein automatischer Versand: Jede Mahnung geht erst nach Ihrer Bestätigung hinaus.',
        'to_remind_only'     => 'Nur zu mahnende',
        'all_overdue'        => 'Alle fälligen Rechnungen',
        'search'             => 'Kunde oder Rechnung suchen',
        'company'            => 'Kunde',
        'invoice'            => 'Rechnung',
        'due_date'           => 'Fälligkeit',
        'days_late'          => 'Verzug (T)',
        'amount_ttc'         => 'Betrag brutto',
        'balance'            => 'Offener Betrag',
        'current_level'      => 'Letzte Mahnung',
        'proposed_level'     => 'Vorgeschlagene Stufe',
        'up_to_date'         => 'Bereits gemahnt',
        'not_yet'            => 'Noch nicht',
        'selected'           => 'ausgewählt',
        'remind_by_email'    => 'Per E-Mail mahnen',
        'log_manual'         => 'Anruf / Brief erfassen',
        'no_permission'      => 'Der Versand von Mahnungen ist berechtigten Benutzern vorbehalten.',
        'empty'              => 'Keine fällige, nicht ausgeglichene Rechnung.',
        'no_levels'          => 'Es ist keine Mahnstufe eingerichtet.',
        'levels_link'        => 'Mahnstufen',
        'total_due'          => 'Fälliger Gesamtbetrag',
        'invoices_count'     => 'Rechnung(en)',

        // Fenêtre d'envoi
        'send_title'         => 'Mahnung an :client',
        'to'                 => 'Empfänger',
        'subject'            => 'Betreff',
        'message'            => 'Nachricht',
        'edit_html'          => 'Text bearbeiten (HTML)',
        'attachments'        => 'Anhänge',
        'send'               => 'Mahnung senden',
        'sending'            => 'Wird gesendet…',
        'cancel'             => 'Abbrechen',
        'channel'            => 'Kanal',
        'note'               => 'Notiz',
        'note_placeholder'   => 'Was gesagt oder gesendet wurde, Zahlungszusage…',
        'save_log'           => 'Mahnung speichern',

        'sent_ok'            => 'Mahnung an :to gesendet.',
        'send_failed'        => 'Die E-Mail wurde nicht versendet: :error',
        'logged_ok'          => 'Mahnung für :count Rechnung(en) erfasst.',
        'pdf_failed'         => 'Das PDF der Rechnung :code konnte nicht erzeugt werden: :error',
        'not_overdue'        => 'Die Auswahl enthält eine Rechnung, die nicht mehr fällig und offen ist (inzwischen bezahlt?). Laden Sie die Liste neu.',

        'default_subject'    => 'Mahnung: unbezahlte Rechnung(en)',
        'default_content'    => '<p>Sehr geehrte Damen und Herren,</p><p>sofern uns kein Fehler unterlaufen ist, sind folgende Rechnungen noch unbezahlt:</p><div>{factures}</div><p>Offener Betrag: <strong>{total_du}</strong>.</p><p>Mit freundlichen Grüßen<br>{entreprise}</p>',

        'channels' => [
            'email'  => 'E-Mail',
            'phone'  => 'Telefon',
            'letter' => 'Brief',
        ],

        'table' => [
            'invoice'   => 'Rechnung',
            'date'      => 'Datum',
            'due_date'  => 'Fälligkeit',
            'days_late' => 'Verzugstage',
            'balance'   => 'Offener Betrag',
        ],

        // Historique sur la fiche facture
        'history_title'      => 'Mahnungen',
        'history_empty'      => 'Keine Mahnung.',
        'email_failed'       => 'Versand fehlgeschlagen',

        // Paramétrage des niveaux
        'levels_title'       => 'Mahnstufen',
        'levels_help'        => 'Eine Stufe wird vorgeschlagen, sobald der Verzug ihre Frist erreicht. Die E-Mail-Vorlagen werden unter Administration → E-Mail-Vorlagen bearbeitet (Typ „Rechnungsmahnung“). Variablen: {client}, {factures}, {total_du}, {retard}, {entreprise}.',
        'level'              => 'Stufe',
        'label'              => 'Bezeichnung',
        'delay_days'         => 'Frist nach Fälligkeit (Tage)',
        'email_template'     => 'E-Mail-Vorlage',
        'color'              => 'Farbe',
        'no_template'        => '— Standardtext —',
        'add_level'          => 'Stufe hinzufügen',
        'save'               => 'Speichern',
        'delete'             => 'Löschen',
        'level_saved'        => 'Stufe gespeichert.',
        'level_deleted'      => 'Stufe gelöscht.',
        'template_type'      => 'Rechnungsmahnung',
    ],

];
