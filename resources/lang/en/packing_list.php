<?php

return [
    // Delivery note print layout (print/layouts/packing-list)
    'layout'           => 'Packing list',
    'title'            => 'Packing list',
    'delivery_note'    => 'Delivery note',
    'tracking_number'  => 'Tracking number',
    'package'          => 'Package',
    'package_of'       => 'Package :number / :count',
    'packages_count'   => 'Number of packages',
    'dimensions'       => 'Dimensions (L × W × H)',
    'net_weight'       => 'Net weight',
    'gross_weight'     => 'Gross weight',
    'total_net_weight' => 'Total net weight',
    'total_gross_weight' => 'Total gross weight',
    'weight_unit'      => 'kg',
    'content'          => 'Content',
    'empty_package'    => 'No line packed in this package.',
    'no_package'       => 'No package recorded on this delivery note.',
    'unpacked'         => 'Not packed',
    'unpacked_help'    => 'Delivery note quantities not packed in any package.',
    'qty_in_package'   => 'Qty',

    // Content entry (delivery note, Packaging tab)
    'content_help'     => 'Quantity of each delivery note line packed in this package (empty = none).',
    'already_packed'   => 'already packed elsewhere: :qty',

    'errors' => [
        'foreign_line' => "This line does not belong to the package's delivery note.",
        'negative'     => 'The quantity cannot be negative.',
        'over_packed'  => 'Line :code: :qty exceeds the quantity left to pack (:available).',
    ],
];
