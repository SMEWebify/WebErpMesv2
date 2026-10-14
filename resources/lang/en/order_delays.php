<?php

return [
    'fields' => [
        'delivery_date' => 'Delivery date',
        'internal_delay' => 'Internal date',
    ],
    'origins' => [
        'manual' => 'Manual entry',
        'order' => 'Order date',
        'import' => 'Import',
        'api' => 'API',
        'pre_order' => 'Pre-order',
        'system' => 'System',
    ],

    'reason_label' => 'Reason for the change',
    'reason_placeholder' => 'Optional: supplier delay, customer request…',
    'promised_label' => 'Promised on',
    'postponed' => 'Postponed',
    'history_title' => 'Date changes',
    'no_history' => 'No date change.',
    'loading' => 'Loading…',
    'load_error' => 'Unable to load the history.',
    'by' => 'by',

    // Service rate (dashboard OTD widget)
    'otd_current' => 'Current date',
    'otd_promised' => 'Promised date',
    'otd_promised_help' => 'On-time deliveries against the date promised to the customer, postponements included.',
    'otd_fallback' => ':count line(s) without a promised date: current date used',
];
