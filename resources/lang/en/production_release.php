<?php

return [
    'short' => 'RFP',
    'label' => 'Released for production',
    'release' => 'Release for production',
    'revoke' => 'Revoke release',
    'released' => 'Released',
    'not_released' => 'Not released',
    'released_on' => 'Released on :date by :user',
    'revoke_confirm' => 'Revoke the production release of this line?',
    'bulk_action' => 'Release for production',
    'bulk_result' => ':count line(s) released for production.',
    'bulk_refused' => 'Not released:',
    'bulk_none' => 'The selected lines are already released for production.',
    'released_ok' => 'Line released for production.',
    'revoked_ok' => 'Production release revoked.',

    // Refusals
    'blocked_start' => 'Order line :line has not been released for production by engineering: the task cannot start.',
    'blocked_kanban' => 'Tasks not released for production, move cancelled: :tasks.',
    'not_article' => 'A presentation line is not manufactured.',
    'already_released' => 'This line is already released for production.',
    'not_released_yet' => 'This line is not released for production.',
    'review_rejected' => 'The order review was rejected: the line cannot be released.',
    'change_pending' => 'A change request is awaiting approval on the order: the line cannot be released until it is approved.',
    'already_started' => 'A task of this line has already started: the release can no longer be revoked.',

    // Screens
    'planning_unreleased' => ':count not released',
    'gantt_unreleased' => '(not released)',
];
