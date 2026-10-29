<?php

return [
    'title' => 'Release sign-off',
    'requires_release' => 'Release sign-off required',
    'requires_release_help' => 'A task finished on this work centre waits for a supervisor\'s sign-off before moving on to the next one (warning only, never blocking).',

    // States
    'state_to_finish' => 'Sign-off due once the task is finished',
    'state_awaiting' => 'Finished, awaiting sign-off',
    'state_released' => 'Released',
    'badge_awaiting' => 'To sign off',
    'badge_released' => 'Released',
    'badge_to_finish' => 'Sign-off required',
    'badge_predecessor' => 'Previous not released',
    'released_on' => 'Released on :date by :user',

    // Actions
    'comment' => 'Comment (optional)',
    'release' => 'Sign off and release',
    'revoke' => 'Cancel sign-off',
    'revoke_confirm' => 'Cancel the release sign-off of this task?',
    'released_ok' => 'Phase released.',
    'revoked_ok' => 'Sign-off cancelled.',
    'no_permission' => 'Sign-off is given by a holder of the "task-release" permission.',

    // Warning on the next task (never blocking)
    'predecessor_warning' => 'The previous operation :tasks has not been signed off.|The previous operations :tasks have not been signed off.',
    'start_confirm' => 'Start anyway?',

    // Refusals
    'forbidden' => 'You are not allowed to sign off a phase.',
    'not_required' => 'The work centre of this task does not require a sign-off.',
    'already_released' => 'This task is already released.',
    'not_finished' => 'The task must be finished before it can be signed off.',
    'not_released_yet' => 'This task has no sign-off.',
];
