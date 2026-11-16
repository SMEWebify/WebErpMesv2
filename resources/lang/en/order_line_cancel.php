<?php

return [
    'errors' => [
        'reason_required' => 'Enter the reason for the cancellation (at least 3 characters).',
        'not_article' => 'A presentation line cannot be cancelled.',
        'already_cancelled' => 'This line is already cancelled.',
        'fully_delivered' => 'Everything is already delivered: nothing to cancel. For a returned part, create a customer return; for an amount, a credit note.',
        'invoiced_beyond_delivered' => 'This line is invoiced beyond what was delivered: create a credit note first.',
        'delete_used' => 'This line is already delivered or invoiced: it can no longer be deleted. Use "Cancel line" to cancel the undelivered remainder.',
        'delivery_invoiced' => 'This delivery note line is invoiced: create a credit note.',
        'delivery_returned' => 'This delivery note line has a customer return: it can no longer be cancelled.',
        'delivery_packaged' => 'This delivery note line is in a package: remove it from the package first (Packaging tab).',
        'delivery_nc' => 'A non-conformity refers to this delivery note line: it cannot be cancelled.',
    ],
    'ui' => [
        'action' => 'Cancel line',
        'title' => 'Cancel order line',
        'message' => 'The undelivered remainder (:qty) is cancelled: the ordered quantity drops to the delivered quantity (:delivered) and open tasks are closed. The line stays visible, marked "Cancelled".',
        'reason' => 'Reason',
        'reason_help' => 'For example: line cancelled by the customer, email of 16/11.',
        'confirm' => 'Cancel line',
        'close' => 'Close',
        'badge' => 'Cancelled',
        'badge_title' => 'Cancelled on :at — :qty cancelled — :reason',
        'done' => 'Line cancelled',
        'delivery_action' => 'Cancel this delivery note line',
        'delivery_title' => 'Cancel delivery note line',
        'delivery_message' => 'The line is removed from the delivery note and its quantity goes back to the order\'s quantity left to deliver. If the delivery note took the part out of stock, it is put back.',
        'delivery_done' => 'Delivery note line cancelled',
        'delivery_stock_restored' => ':qty put back in stock.',
    ],
];
