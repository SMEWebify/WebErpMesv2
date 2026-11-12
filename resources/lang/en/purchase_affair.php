<?php

return [
    // Link between a purchase and a business case (header)
    'link'                => 'Linked business case',
    'none'                => 'None',
    'help'                => 'For a purchase without an operation (expenses, dedicated tooling): it shows in the business case file.',
    'invalid'             => 'The linked document cannot be found.',
    'group_orders'        => 'Orders',
    'group_quotes'        => 'Quotes',
    'group_opportunities' => 'Opportunities',

    // Purchase request from a quote
    'source'                  => 'Operations from',
    'source_orders'           => 'Firm orders',
    'source_quotes'           => 'Open quotes',
    'source_quotes_help'      => 'Operations of quotes not yet ordered: request for quotation only, to price the quote.',
    'quote'                   => 'Quote',
    'order_needs_firm_order'  => 'A purchase order can only cover operations of a firm order. For a quote, send a request for quotation.',
    'quotation_tasks_invalid' => 'Some operations come neither from a firm order nor from an open quote.',
    'quote_not_orderable'     => 'Line of a quote not yet ordered: keep the price for the quote; the purchase order will follow once the quote is converted into an order.',

    // Supplier answer kept for the quote
    'retain'         => 'Use for the quote',
    'retain_help'    => 'This line\'s price becomes the cost of the quote operation (internal margin).',
    'retained'       => 'Price kept for the quote: :price.',
    'retained_badge' => 'Kept',
    'retain_refused' => [
        'not_quote_task' => 'This line is not on a quote operation.',
        'converted'      => 'The quote has already been converted into an order.',
        'quote_closed'   => 'The quote is no longer open or sent.',
    ],
];
