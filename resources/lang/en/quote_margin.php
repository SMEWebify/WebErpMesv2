<?php

return [
    'title' => 'Margin (internal)',
    'internal' => 'Internal information: never printed on customer documents.',
    'cost_total' => 'Cost price',
    'sale_total' => 'Selling price excl. VAT',
    'margin' => 'Margin',
    'margin_rate' => 'Margin / cost',
    'markup_rate' => 'Margin / selling price',
    'column' => 'Margin',
    'column_toggle' => 'Show the margin column',
    'unit_cost' => 'Unit cost',
    'no_cost' => 'No cost price (no costed operation, bill of materials or sub-assembly)',
    'below_threshold' => 'Margin under the :threshold % threshold',
    'lines_without_cost' => '{1} :count line without cost price: counted at its selling price, without cost.|[2,*] :count lines without cost price: counted at their selling price, without cost.',
    'cost_help' => 'Cost = operations (subcontracting included) + bill of materials + sub-assemblies. Section, subtotal and text lines ignored.',

    'apply_title' => 'Apply an overall margin',
    'apply_rate' => 'Margin rate on cost (%)',
    'apply_button' => 'Recalculate prices',
    'apply_help' => 'Selling price of each line = cost × (1 + rate), the line discount being offset. The price calculated from the routing is turned off. Lines without cost are left unchanged.',
    'apply_confirm' => 'Recalculate the selling price of every line from its cost?',
    'applied' => '{0} No price recalculated.|{1} :count price recalculated.|[2,*] :count prices recalculated.',
    'applied_skipped' => ':count line(s) without cost kept at their price.',
    'refused_converted' => 'This quote has been converted into an order: its prices are frozen, the overall margin can no longer be applied.',
    'refused_status' => 'Only an open quote can be recalculated.',
    'locked_converted' => 'Quote converted into an order: prices frozen.',
];
