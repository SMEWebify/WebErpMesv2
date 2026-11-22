<?php

return [
    'tab' => 'E-mail',
    'signature' => 'E-mail signature',
    'signature_hint' => 'Added under the message of every e-mail sent from the ERP (quotes, orders, delivery notes, invoices, purchases, reminders). It shows in the editor before sending, so you can adjust it for one e-mail.',
    'signature_image_hint' => 'A logo pasted into the editor is often blocked by Gmail and Outlook: prefer an image inserted by its web address (https://…).',
    'signature_saved' => 'Signature saved.',
    'variables' => 'Variables',
    'variables_hint' => 'Replaced when the sending screen opens, in the subject and in the message.',
    'reminder_variables_hint' => 'Invoice reminders: {client}, {factures}, {total_du}, {retard}, {entreprise}, {utilisateur} and {signature}.',
    'variable' => [
        'code' => 'Document number',
        'libelle' => 'Document label',
        'client' => 'Company (customer or supplier)',
        'contact' => 'Document contact',
        'utilisateur' => "Sender's name",
        'entreprise' => 'Your company',
        'signature' => "Sender's signature (otherwise added at the end)",
    ],
];
