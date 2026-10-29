<?php

return [
    'status' => [
        'qualified'      => 'Qualified',
        'unqualified'    => 'Not qualified',
        'review_overdue' => 'Review overdue',
    ],

    'detail' => [
        'no_evaluation'  => 'no supplier evaluation.',
        'not_approved'   => 'latest evaluation status is ":status".',
        'review_overdue' => 'qualification review overdue since :date.',
    ],

    'blocked_title'           => 'Supplier not qualified',
    'blocked_create'          => 'Purchase order refused: supplier not qualified.',
    'blocked_validate'        => 'Order validation refused: supplier not qualified.',
    'blocked_supplier_change' => 'Supplier change refused on a validated order: supplier not qualified. Set the order back to "in progress" to change the supplier.',
    'no_permission'           => 'You are not allowed to order from a supplier that is not qualified. A request for quotation is still possible.',
    'override_reason'         => 'Override reason',
    'override_reason_help'    => 'Recorded on the order and shown on the supplier sheet.',
    'override_reason_required'=> 'Please give the reason for the override.',
    'override'                => 'Order anyway',

    // Company sheet and order sheet
    'label'           => 'Qualification',
    'overrides_title' => '"Supplier not qualified" overrides',
    'override_on'     => 'Override on :document',
    'context'         => [
        'create'          => 'creation',
        'validate'        => 'validation',
        'supplier_change' => 'supplier change',
    ],

    // Export
    'export'          => 'Qualified suppliers (CSV)',
    'export_columns'  => [
        'code'              => 'Code',
        'label'             => 'Company name',
        'qualification'     => 'Qualification',
        'evaluation_status' => 'Latest evaluation status',
        'evaluated_at'      => 'Evaluated on',
        'approved_at'       => 'Approved on',
        'next_review_at'    => 'Next review',
    ],
];
