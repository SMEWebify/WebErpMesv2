<?php

return [
    'tab' => 'E-mail',
    'signature' => 'Signature des e-mails',
    'signature_hint' => "Ajoutée sous le message de chaque e-mail envoyé depuis l'ERP (devis, commandes, BL, factures, achats, relances). Elle apparaît dans l'éditeur avant l'envoi : vous pouvez la retoucher pour un envoi.",
    'signature_image_hint' => "Un logo collé dans l'éditeur est souvent bloqué par Gmail et Outlook : préférez une image insérée par son adresse web (https://…).",
    'signature_saved' => 'Signature enregistrée.',
    'variables' => 'Variables',
    'variables_hint' => "Remplacées à l'ouverture de l'écran d'envoi, dans l'objet et dans le message.",
    'reminder_variables_hint' => 'Relances de factures : {client}, {factures}, {total_du}, {retard}, {entreprise}, {utilisateur} et {signature}.',
    'variable' => [
        'code' => 'Numéro du document',
        'libelle' => 'Libellé du document',
        'client' => 'Société (client ou fournisseur)',
        'contact' => 'Contact du document',
        'utilisateur' => "Nom de l'expéditeur",
        'entreprise' => 'Votre société',
        'signature' => "Signature de l'expéditeur (sinon ajoutée à la fin)",
    ],
];
