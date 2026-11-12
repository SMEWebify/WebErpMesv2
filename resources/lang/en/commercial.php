<?php

// Showcase screens for features reserved to the commercial edition (Nest2Prod).
return [
    'badge'   => 'Commercial edition',
    'contact' => 'Contact Nest2Prod',
    'press_brake' => [
        'title' => 'Press brake tooling',
        'lead'  => 'The punch designer and the manufacturer tooling catalog are available in the commercial edition of the ERP, published by Nest2Prod.',
        'designer_title' => 'Custom punches',
        'designer_text'  => 'Parametric profile or taken from manufacturer libraries.',
        'simulation_title' => 'Bend simulation',
        'simulation_text'  => 'Check the bent part and its collisions with the tool.',
        'catalog_title' => 'Manufacturer catalog',
        'catalog_text'  => 'Browse and filter punches, dies and adapters.',
    ],
    'nesting' => [
        'title' => 'Sheet nesting',
        'lead'  => 'True-shape nesting and the resulting material requirement are available in the commercial edition of the ERP, published by Nest2Prod.',
        'shape_title' => 'True shape',
        'shape_text'  => 'Parts placed on their real DXF / SVG contour, with rotations and cutting clearance.',
        'sheets_title' => 'Sheets ready to cut',
        'sheets_text'  => 'Preview of every sheet and DXF export of the layout.',
        'stock_title' => 'Material requirement',
        'stock_text'  => 'Sheet and bar counts checked against stock and pending purchases.',
    ],
    'digital_twin' => [
        'title' => 'Shop floor digital twin',
        'lead'  => 'The shop floor model and the production flow simulation are available in the commercial edition of the ERP, published by Nest2Prod.',
        'video' => 'Digital twin presentation video',
        'flow_title' => 'Live flow',
        'flow_text'  => 'Work orders move between work centres at the pace of actual progress.',
        'simulation_title' => 'Scenario simulation',
        'simulation_text'  => 'Try a rush order, a breakdown or a shift change before applying it to the schedule.',
        'bottleneck_title' => 'Bottlenecks',
        'bottleneck_text'  => 'Queues and load rate per work centre to see where the flow gets stuck.',
    ],
];
