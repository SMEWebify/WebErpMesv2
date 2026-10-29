<?php

return [
    'label' => 'End-of-manufacturing file',
    'short' => 'File',
    'validated' => 'File validated',
    'not_validated' => 'File to validate',
    'pending_title' => 'End-of-manufacturing file required and not validated: delivery note and invoice will be refused.',
    'validated_on' => 'File validated on :date by :user',
    'source_siteflow' => 'Siteflow',

    // Refusal when creating a delivery note or an invoice
    'blocked' => '{1} End-of-manufacturing file not validated for line: :lines. No delivery note or invoice until it is validated.|[2,*] End-of-manufacturing file not validated for lines: :lines. No delivery note or invoice until it is validated.',

    // Refusals on the line
    'not_article' => 'A layout line has no end-of-manufacturing file.',
    'already_validated' => 'The file of this line is already validated.',
    'not_validated_yet' => 'The file of this line is not validated.',
    'already_shipped' => 'The line is already delivered or invoiced, even partly: the file validation can no longer be cancelled.',

    // Bulk actions of the order page
    'actions' => 'File',
    'action_validate' => 'Validate the file',
    'action_revoke' => 'Cancel the validation',
    'action_require' => 'Require the file',
    'action_waive' => 'No longer require the file',
    'revoke_confirm' => 'Cancel the file validation of the selected lines?',
    'waive_confirm' => 'Stop requiring an end-of-manufacturing file on the selected lines? They can then be delivered and invoiced without it.',
    'bulk_none' => 'No line selected.',
    'bulk_refused' => 'Not processed:',
    'bulk_result' => [
        'validate' => ':count line(s) with a validated file.',
        'revoke' => ':count validation(s) cancelled.',
        'require' => ':count line(s) now require the file.',
        'waive' => ':count line(s) no longer require the file.',
    ],
];
