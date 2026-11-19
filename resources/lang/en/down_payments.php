<?php

return [
    'document_title'  => 'DOWN PAYMENT INVOICE',
    'type_label'      => 'Down payment',
    'invoice_label'   => 'Down payment on order :order',
    'line_label'      => 'Down payment :percent % on order :order',
    'deduction_label' => 'Down payment deduction :code',

    'card' => [
        'title'          => 'Down payments',
        'none'           => 'No down payment invoice.',
        'order_total'    => 'Order total excl. VAT',
        'invoice'        => 'Invoice',
        'ht'             => 'Excl. VAT',
        'paid'           => 'Paid incl. VAT',
        'deducted'       => 'Deducted excl. VAT',
        'draft'          => 'Draft',
        'mode_percent'   => '%',
        'mode_amount'    => '€ excl. VAT',
        'value'          => 'Amount',
        'create'         => 'Down payment invoice',
        'help'           => 'A down payment does not change quantities: it is deducted from the following invoices in proportion to what is invoiced, up to the amount paid.',
    ],

    'invoice' => [
        'deducts'        => 'Deducts down payment',
        'recompute'      => 'Recompute down payment deduction',
        'recomputed'     => 'Down payment deduction recomputed.',
        'is_down_payment'=> 'Down payment invoice for order :order: excl. VAT :ht, already deducted :deducted, left to deduct :remaining.',
        'unpaid_warning' => 'Down payment :code is unpaid: it is not deducted from this invoice.',
    ],

    'created' => 'Down payment invoice :code created as a draft.',

    'refused' => [
        'internal_order'  => 'An internal order does not take a down payment.',
        'canceled_order'  => 'Canceled order: no down payment.',
        'empty_order'     => 'The order has no amount to invoice.',
        'amount'          => 'The down payment amount must be positive.',
        'over_total'      => 'Down payments would exceed the order total (still possible: :max € excl. VAT).',
        'not_draft'       => 'The invoice is no longer a draft.',
        'not_a_deduction' => 'This line is not a down payment deduction.',
        'deduction_max'   => 'Cannot deduct more than the paid and not yet deducted part of the down payment (:max € excl. VAT).',
        'credit_note_on_deduction' => 'A down payment deduction cannot be credited: select the sold lines.',
        'credit_note_on_deducted'  => 'Down payment :code is already deducted from an invoice: credit the final invoice instead.',
    ],
];
