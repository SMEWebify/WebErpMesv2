<?php

return [
    'tab' => 'Contract review',
    'title' => 'Contract review',
    'intro' => 'Before launching the order, check that it matches the offer and that the company can deliver it. The sign-off freezes the review.',

    // Indicator (index, tab)
    'status' => [
        'done' => 'Review done',
        'rejected' => 'Review rejected',
        'todo' => 'Review to do',
    ],
    'column' => 'Review',

    'checklist_title' => 'Items reviewed',
    'checklist' => [
        'requirements_understood' => 'Customer requirements understood and consistent with the offer',
        'lead_time_achievable' => 'Requested lead time achievable',
        'contract_documents_received' => 'Contract documents received',
        'quality_requirements_identified' => 'Quality requirements and applicable codes identified',
    ],
    'checklist_removed' => 'item removed from the configuration',

    'gaps' => 'Deviations from the offer',
    'gaps_help' => 'Differences between the order and the offer (price, quantities, lead time, requirements) and how they are resolved. Required to reject, or to approve with an unchecked item.',
    'no_gaps' => 'No deviation reported.',

    'documents' => 'Contract documents',
    'documents_help' => 'Files of the order document manager with the "Contract document" type.',
    'no_documents' => 'No contract document in the order document manager.',
    'open_documents' => 'Open documents',

    'save' => 'Save preparation',
    'saved' => 'Preparation saved.',
    'approve' => 'Sign off: review approved',
    'reject' => 'Sign off: review rejected',
    'approve_confirm' => 'Approve the contract review? It will be frozen and the order acknowledgement will be prepared.',
    'reject_confirm' => 'Reject the contract review? It will be frozen and the order will no longer receive a production release.',
    'visa_reserved' => 'The sign-off is restricted to authorised people ("contract-review-approve" permission).',
    'visa' => 'Signed off on :date by :user',
    'frozen' => 'Review signed off: it can no longer be changed.',
    'unchecked_warning' => 'Unchecked item(s): describe the deviations before approving.',
    'saving' => 'Saving…',
    'error' => 'Saving failed.',

    // Order page warning (never blocking)
    'warning_todo' => 'The contract review of this order has not been signed off.',
    'warning_rejected' => 'The contract review of this order was rejected.',
    'warning_link' => 'Open the review',

    // Change request
    'change' => [
        'title' => 'Change request',
        'help' => 'Change requested after the review (revision, quantity, lead time…). Until it is approved, no production release is given.',
        'pending' => 'Change awaiting approval',
        'save' => 'Save change',
        'saved' => 'Change request saved.',
    ],

    'history' => 'Review history',

    // Refusals
    'errors' => [
        'forbidden' => 'You are not allowed to sign off a contract review.',
        'frozen' => 'The contract review is signed off: it can no longer be changed.',
        'not_applicable' => 'An internal order has no contract review.',
        'invalid_decision' => 'Unknown decision.',
        'gaps_required_reject' => 'Describe the deviations from the offer to reject the review.',
        'gaps_required_unchecked' => 'Unchecked items (:items): describe the deviations from the offer to approve the review.',
    ],
];
