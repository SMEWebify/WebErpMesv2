<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Open orders in the outstanding amount
    |--------------------------------------------------------------------------
    |
    | When true, the customer outstanding also counts what is ordered but not
    | invoiced yet (order lines' invoiced_remaining_qty, VAT included). This is
    | the exposure a credit manager usually watches: a delivery made today is
    | an invoice tomorrow. Set to false to only count issued invoices.
    |
    */

    'include_open_orders' => (bool) env('RECEIVABLES_INCLUDE_OPEN_ORDERS', true),

    /*
    |--------------------------------------------------------------------------
    | Order statuses left out of the open orders
    |--------------------------------------------------------------------------
    |
    | 0 = awaiting payment (proforma, paid before anything is made),
    | 5 = stopped, 6 = canceled. Internal orders (type 2) are never counted.
    |
    */

    'excluded_order_statuses' => [0, 5, 6],

    /*
    |--------------------------------------------------------------------------
    | Credit note statuses deducted from their invoice
    |--------------------------------------------------------------------------
    |
    | A credit note reduces what is due on the invoice it corrects.
    | 1 = awaiting approval, 2 = approved, 3 = refused. The approval step is
    | not used in practice, so everything but a refused credit note counts.
    |
    */

    'deducted_credit_note_statuses' => [1, 2],

];
