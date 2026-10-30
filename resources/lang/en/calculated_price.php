<?php

return [
    'enable' => 'Enable calculated price',
    'disable' => 'Disable calculated price',
    'disable_title' => 'The current calculated price will be saved as the line selling price.',
    'reason_title' => 'Validated order: the price must stay frozen. Reason for re-enabling the calculated price?',
    'reason_placeholder' => 'Reason (required)',
    'confirm' => 'Enable',
    'cancel' => 'Cancel',

    'errors' => [
        'not_article' => 'A presentation line has no calculated price.',
        'shipped' => 'The line has already been delivered or invoiced, even partly: its price can no longer be recalculated.',
        'order_locked' => 'The order is no longer in preparation (status beyond "Open" or contract review approved): the price is frozen.',
        'reason_required' => 'A reason is required to re-enable the calculated price on a validated order.',
    ],
];
