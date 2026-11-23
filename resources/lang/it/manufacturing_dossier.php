<?php

return [
    'label' => 'Dossier di fine produzione',
    'short' => 'Dossier',
    'validated' => 'Dossier convalidato',
    'not_validated' => 'Dossier da convalidare',
    'pending_title' => 'Dossier di fine produzione richiesto e non convalidato: DDT e fattura saranno rifiutati.',
    'validated_on' => 'Dossier convalidato il :date da :user',
    'source_siteflow' => 'Siteflow',

    // Refus à la création d'un BL ou d'une facture
    'blocked' => '{1} Dossier di fine produzione non convalidato per la riga: :lines. Né DDT né fattura finché non è convalidato.|[2,*] Dossier di fine produzione non convalidato per le righe: :lines. Né DDT né fattura finché non è convalidato.',

    // Refus sur la ligne
    'not_article' => 'Una riga di presentazione non ha dossier di fine produzione.',
    'already_validated' => 'Il dossier di questa riga è già convalidato.',
    'not_validated_yet' => 'Il dossier di questa riga non è convalidato.',
    'already_shipped' => 'La riga è già consegnata o fatturata, anche in parte: la convalida del dossier non si può più annullare.',

    // Actions groupées de la fiche commande
    'actions' => 'Dossier',
    'action_validate' => 'Convalida il dossier',
    'action_revoke' => 'Annulla la convalida',
    'action_require' => 'Richiedi il dossier',
    'action_waive' => 'Non richiedere più il dossier',
    'revoke_confirm' => 'Annullare la convalida del dossier delle righe selezionate?',
    'waive_confirm' => 'Non richiedere più il dossier di fine produzione sulle righe selezionate? Potranno essere consegnate e fatturate senza di esso.',
    'bulk_none' => 'Nessuna riga selezionata.',
    'bulk_refused' => 'Non elaborata/e:',
    'bulk_result' => [
        'validate' => ':count riga/righe con dossier convalidato.',
        'revoke' => ':count convalida/e annullata/e.',
        'require' => ':count riga/righe richiedono ora il dossier.',
        'waive' => ':count riga/righe non richiedono più il dossier.',
    ],
];
