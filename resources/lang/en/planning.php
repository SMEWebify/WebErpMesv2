<?php

return [
    // Links between the operations of a routing
    'dependency_short' => [
        'FS' => 'FS',
        'SS' => 'SS',
        'FF' => 'FF',
    ],
    'dependency_types' => [
        'FS' => 'Finish to start',
        'SS' => 'Start to start',
        'FF' => 'Finish to finish',
    ],
    'dependency_implicit'          => 'after :ordre',
    'notation_unreadable'          => 'Unreadable link: ":item" (examples: 20FS, 30SS+2h, 20SS+10pc).',
    'notation_unknown_operation'   => 'No operation :ordre in this routing.',
    'notation_unknown_type'        => 'Unknown link type in ":item": FS, SS or FF.',
    'notation_unknown_unit'        => 'Unknown unit in ":item": h or pc.',
    'dependency_self'              => 'An operation cannot depend on itself.',
    'dependency_other_routing'     => 'Operation :ordre does not belong to the same routing.',
    'dependency_not_schedulable'   => 'Only production and subcontracting operations can be linked.',
    'dependency_piece_lag'         => 'A lag in pieces is only possible on start-to-start or finish-to-finish links.',
    'dependency_lag_too_large'     => 'Lag too large: :max at most.',
    'dependency_duplicate'         =>'Link :link is entered twice.',
    'dependency_cycle'             => 'Circular links: :cycle.',
];
