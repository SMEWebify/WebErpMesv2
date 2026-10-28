<?php

return [
    'label' => 'Loss reason',
    'plural' => 'Loss reasons',
    'none' => 'Not specified',
    'inactive' => 'inactive',
    'info' => 'Reasons offered when a quote is set to Lost or an opportunity to Closed lost. A reason is required as soon as at least one reason is active; documents lost before then remain valid without one. A reason in use cannot be deleted: once deactivated it is no longer offered but stays on the documents that carry it.',
    'new' => 'New reason',
    'edit' => 'Edit :label',
    'code' => 'Code',
    'order' => 'Order',
    'active' => 'Active',
    'usage' => 'Usage',
    'usage_detail' => ':quotes quote(s), :opportunities opportunity(ies)',
    'created' => 'Loss reason created.',
    'updated' => 'Loss reason updated.',
    'code_locked' => 'The code cannot be changed.',

    'defaults' => [
        'PRICE' => 'Price',
        'DELAY' => 'Lead time',
        'TECHNICAL' => 'Technical',
        'COMPETITOR' => 'Competitor',
        'NO_FOLLOW_UP' => 'No follow-up',
    ],

    'dialog' => [
        'title_quote' => 'Quote lost: why?',
        'title_opportunity' => 'Opportunity lost: why?',
        'reason' => 'Reason',
        'choose' => '— Choose a reason —',
        'comment' => 'Comment',
        'comment_help' => 'Competitor chosen, price gap, customer feedback…',
        'cancel' => 'Cancel',
        'confirm' => 'Save',
        'edit' => 'Edit',
        'fill' => 'Specify',
        'error' => 'Error while saving the loss reason.',
    ],

    'required' => 'Please give the reason for the loss.',
    'not_lost' => 'A loss reason can only be set on a lost document.',

    'stats' => [
        'title' => 'Lost business',
        'by_reason' => 'By reason',
        'by_category' => 'By business category',
        'count' => 'Quotes',
        'amount' => 'Amount excl. tax',
        'share' => 'Share',
        'total' => 'Total',
        'no_category' => 'No category',
        'empty' => 'No lost quote over the period.',
        'period' => 'Period',
        'periods' => [
            '12m' => 'Last 12 months',
            'year' => 'Current year',
            'all' => 'All time',
        ],
        'period_help' => 'Lost quotes created over the period.',
    ],
];
