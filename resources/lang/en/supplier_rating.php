<?php

return [
    'saved'    => 'Supplier rating saved.',
    'no_lines' => 'Receipt without lines: no purchase order to attach the rating to.',

    // Quick rating window (receipt page)
    'window' => [
        'card_title'      => 'Supplier rating',
        'title'           => 'Rate the supplier',
        'intro'           => 'Optional: a few seconds to keep track of your suppliers\' quality.',
        'conformity'      => 'Conformity',
        'conformity_help' => 'Parts compliant with the order',
        'delay'           => 'Lead time',
        'delay_help'      => 'Promised date met',
        'documents'       => 'Documents',
        'documents_help'  => 'Delivery note, certificates, labelling',
        'comment'         => 'Comment',
        'later'           => 'Later',
        'save'            => 'Save',
        'saving'          => 'Saving…',
        'rate'            => 'Rate',
        'edit'            => 'Edit',
        'not_rated'       => 'Receipt not rated.',
        'no_lines'        => 'Add a line to be able to rate the supplier.',
        'days_late'       => ':days day(s) late against the promised date',
        'on_time'         => 'Delivered on the promised date',
        'no_promised'     => 'No promised date on the purchase lines',
        'suggested'       => 'suggested',
        'has_nc'          => 'Non-conformity on this receipt: the quality score counts as :score/5.',
        'rolling'         => ':months-month rolling score',
        'rolling_detail'  => ':count rating(s), :nc non-compliant receipt(s)',
        'no_rolling'      => 'No rating over :months months',
        'error'           => 'Error while saving',
    ],

    // Supplier page
    'company' => [
        'rolling'        => 'Rolling score (:months months)',
        'since'          => 'since :date',
        'quality'        => 'Quality',
        'logistics'      => 'Lead time',
        'service'        => 'Documents / service',
        'count'          => ':count rating(s)',
        'nc_receipts'    => ':count receipt(s) with an NC',
        'none'           => 'No rating and no non-compliant receipt over the period.',
        'receipt'        => 'Receipt',
    ],

    // On-time delivery rate (App\Services\Purchases\SupplierOnTimeDelivery)
    'on_time' => [
        'title'     => 'On-time deliveries (:months months)',
        'global'    => 'All suppliers',
        'rate'      => 'On time',
        'lines'     => 'Lines',
        'detail'    => ':on_time line(s) on time out of :measured',
        'undated'   => ':count line(s) with neither a requested nor a confirmed date, not counted',
        'tolerance' => 'Tolerance: :days d',
        'rule'      => 'Fully received line, at the date of its last receipt, against the confirmed date (otherwise the requested one).',
        'none'      => 'No received line with a date over the period.',
        'worst'     => 'Least punctual',
    ],
];
