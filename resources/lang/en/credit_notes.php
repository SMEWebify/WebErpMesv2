<?php

return [
    'form' => [
        'title'            => 'Create a credit note',
        'help'             => 'Tick the lines, then adjust quantity or price for a partial credit note (scrapped parts, partial return, commercial gesture).',
        'line'             => 'Line',
        'invoiced'         => 'Invoiced',
        'creditable'       => 'Still creditable',
        'qty'              => 'Qty to credit',
        'unit_price'       => 'Credited unit price',
        'fully_credited'   => 'Already credited',
        'deduction'        => 'Down payment deduction',
        'restore'          => 'Put these quantities back into "left to invoice" (returned or remade parts, to be invoiced again)',
        'restore_help'     => 'Untick for a commercial gesture or scrap that will not be remade: the order stays invoiced.',
        'submit'           => 'Create the credit note',
    ],

    'line' => [
        'restored'     => 'Back to invoice',
        'not_restored' => 'Not back to invoice',
    ],

    'refused' => [
        'no_line'            => 'Please select at least one invoice line.',
        'several_invoices'   => 'A credit note covers a single invoice.',
        'fully_credited'     => 'Line :line is already fully credited.',
        'qty'                => 'Line :line: the quantity must be positive and not exceed :max.',
        'price'              => 'Line :line: the credited price cannot exceed the invoiced price (:max €).',
        'amount'             => 'Line :line: the credited amount exceeds what is still creditable (:max € excl. VAT).',
        'already_reinvoiced' => 'Quantities of line :line have been invoiced again since: the credit note can no longer be rejected.',
    ],
];
