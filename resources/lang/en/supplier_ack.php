<?php

return [
    // "Without acknowledgement" list
    'menu'                => 'Orders without ack.',
    'missing_title'       => 'Purchase orders without supplier acknowledgement',
    'missing_info'        => 'Orders placed at least :days day(s) ago with a line still lacking the supplier\'s order acknowledgement. Record it from the purchase order (lines tab, "Acknowledgement received" button).',
    'missing_none'        => 'No purchase order awaiting acknowledgement.',
    'ordered_at'          => 'Ordered on',
    'waiting_days'        => 'Waiting',
    'days'                => '{0} today|{1} :count day|[2,*] :count days',
    'lines_without_ack'   => 'Lines without ack.',
    'next_requested_date' => 'Earliest requested date',

    'confirmed_date_hint' => 'Date confirmed by the supplier (acknowledgement)',

    // Purchase order page (PurchaseLinesPage, SupplierAckModal)
    'screen' => [
        'action'          => 'Acknowledgement received',
        'title'           => 'Supplier order acknowledgement',
        'ack_at'          => 'Acknowledgement date',
        'reference'       => 'Acknowledgement reference',
        'file'            => 'Document (optional)',
        'hint'            => 'Tick the confirmed lines and enter the delivery date announced by the supplier (requested date by default). It becomes the reference date for delays. The document is filed in the Documents tab, role "Supplier acknowledgement".',
        'all_lines'       => 'All lines',
        'line'            => 'Line',
        'requested'       => 'Requested date',
        'confirmed'       => 'Confirmed date',
        'gap'             => 'Gap',
        'already'         => 'Already acknowledged',
        'day_unit'        => 'd',
        'save'            => 'Save acknowledgement',
        'saving'          => 'Saving…',
        'cancel'          => 'Cancel',
        'saved'           => 'Acknowledgement saved',
        'error'           => 'Error while saving the acknowledgement',
        'file_error'      => 'Acknowledgement saved, but the document could not be uploaded:',
        'no_line'         => 'Tick at least one line.',
        'missing'         => 'No ack.',
        'ack_short'       => 'Ack.',
        'requested_short' => 'requested',
        'confirmed_title' => 'Date confirmed by the supplier (reference date)',
        'requested_title' => 'Requested date (not confirmed yet)',
    ],

    // Entry
    'not_ordered'         => 'An acknowledgement can only be recorded on a placed order (neither in progress nor cancelled).',
    'foreign_line'        => 'One of the lines does not belong to this order.',
];
