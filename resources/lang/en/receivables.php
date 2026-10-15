<?php

return [

    'credit' => [
        // Check when an order or a delivery note is created
        'warning'                  => 'Customer outstanding above its credit limit: :projected for a limit of :limit.',
        'blocked_title'            => 'Credit limit exceeded',
        'blocked'                  => 'This customer is on hold: its outstanding would reach :projected for a limit of :limit (current outstanding :outstanding).',
        'no_permission'            => 'Only an authorised user can override the credit limit.',
        'override'                 => 'Override for this document',
        'override_reason'          => 'Reason for the override',
        'override_reason_help'     => 'Required. Kept with the document, its author and the date.',
        'this_document'            => 'This document',
        'projected'                => 'Outstanding after this document',
        'override_reason_required' => 'The reason for the override is required.',

        // Company page
        'card_title'        => 'Customer outstanding',
        'limit'             => 'Credit limit (incl. VAT)',
        'limit_help'        => 'Empty = no limit.',
        'block_level'       => 'Above the limit',
        'level_none'        => 'No check',
        'level_warn'        => 'Warn, do not block',
        'level_block'       => 'Block orders and delivery notes',
        'read_only'         => 'Editable by users allowed to override the credit limit.',
        'outstanding'       => 'Outstanding',
        'invoices_due'      => 'Unsettled invoices',
        'open_orders'       => 'Orders not invoiced',
        'no_limit'          => 'No limit',
        'of_limit'          => 'of the limit',
        'overrides_title'   => 'Latest overrides',
        'override_by'       => 'by',
    ],

    'reminders' => [
        'title'              => 'Invoices to chase',
        'subtitle'           => 'Overdue unpaid invoices. Nothing is sent automatically: every reminder starts from your confirmation.',
        'to_remind_only'     => 'To chase only',
        'all_overdue'        => 'All overdue invoices',
        'search'             => 'Search a customer or an invoice',
        'company'            => 'Customer',
        'invoice'            => 'Invoice',
        'due_date'           => 'Due date',
        'days_late'          => 'Days late',
        'amount_ttc'         => 'Amount incl. VAT',
        'balance'            => 'Amount due',
        'current_level'      => 'Last reminder',
        'proposed_level'     => 'Suggested level',
        'up_to_date'         => 'Already chased',
        'not_yet'            => 'Not yet',
        'selected'           => 'selected',
        'remind_by_email'    => 'Send a reminder e-mail',
        'log_manual'         => 'Log a call / letter',
        'no_permission'      => 'Sending reminders is restricted to authorised users.',
        'empty'              => 'No overdue unpaid invoice.',
        'no_levels'          => 'No reminder level is set up.',
        'levels_link'        => 'Reminder levels',
        'total_due'          => 'Total due',
        'invoices_count'     => 'invoice(s)',

        // Send dialog
        'send_title'         => 'Reminder to :client',
        'to'                 => 'Recipient',
        'subject'            => 'Subject',
        'message'            => 'Message',
        'edit_html'          => 'Edit the text (HTML)',
        'attachments'        => 'Attachments',
        'send'               => 'Send the reminder',
        'sending'            => 'Sending…',
        'cancel'             => 'Cancel',
        'channel'            => 'Channel',
        'note'               => 'Note',
        'note_placeholder'   => 'What was said or sent, payment promise…',
        'save_log'           => 'Save the reminder',

        'sent_ok'            => 'Reminder sent to :to.',
        'send_failed'        => 'The e-mail was not sent: :error',
        'logged_ok'          => 'Reminder saved on :count invoice(s).',
        'pdf_failed'         => 'The PDF of invoice :code could not be generated: :error',
        'not_overdue'        => 'The selection contains an invoice that is no longer overdue and due (paid in the meantime?). Reload the list.',

        'default_subject'    => 'Reminder: unpaid invoice(s)',
        'default_content'    => '<p>Hello,</p><p>Unless we are mistaken, the following invoices remain unpaid:</p><div>{factures}</div><p>Amount due: <strong>{total_du}</strong>.</p><p>Best regards,<br>{entreprise}</p>',

        'channels' => [
            'email'  => 'E-mail',
            'phone'  => 'Phone',
            'letter' => 'Letter',
        ],

        'table' => [
            'invoice'   => 'Invoice',
            'date'      => 'Date',
            'due_date'  => 'Due date',
            'days_late' => 'Days late',
            'balance'   => 'Amount due',
        ],

        // History on the invoice page
        'history_title'      => 'Reminders',
        'history_empty'      => 'No reminder.',
        'email_failed'       => 'sending failed',

        // Level settings
        'levels_title'       => 'Reminder levels',
        'levels_help'        => 'A level is suggested once the delay is reached. E-mail templates are edited in Administration → E-mail templates (type "Invoice reminder"). Variables: {client}, {factures}, {total_du}, {retard}, {entreprise}.',
        'level'              => 'Level',
        'label'              => 'Label',
        'delay_days'         => 'Delay after due date (days)',
        'email_template'     => 'E-mail template',
        'color'              => 'Colour',
        'no_template'        => '— Default text —',
        'add_level'          => 'Add a level',
        'save'               => 'Save',
        'delete'             => 'Delete',
        'level_saved'        => 'Level saved.',
        'level_deleted'      => 'Level deleted.',
        'template_type'      => 'Invoice reminder',
    ],

];
