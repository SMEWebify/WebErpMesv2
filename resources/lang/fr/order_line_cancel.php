<?php

return [
    'errors' => [
        'reason_required' => 'Indiquez le motif de l\'annulation (3 caractères au moins).',
        'not_article' => 'Une ligne de présentation ne s\'annule pas.',
        'already_cancelled' => 'Cette ligne est déjà annulée.',
        'fully_delivered' => 'Tout est déjà livré : rien à annuler. Pour une pièce rendue, faites un retour client ; pour un montant, un avoir.',
        'invoiced_beyond_delivered' => 'Cette ligne est facturée au-delà de ce qui est livré : faites d\'abord un avoir.',
        'delete_used' => 'Cette ligne est déjà livrée ou facturée : elle ne peut plus être supprimée. Utilisez « Annuler la ligne » pour annuler le reste non livré.',
        'delivery_invoiced' => 'Cette ligne de BL est facturée : faites un avoir.',
        'delivery_returned' => 'Cette ligne de BL a un retour client : elle ne peut plus être annulée.',
        'delivery_packaged' => 'Cette ligne de BL est rangée dans un colis : retirez-la d\'abord du colis (onglet Packaging).',
        'delivery_nc' => 'Une non-conformité cite cette ligne de BL : elle ne peut pas être annulée.',
    ],
    'ui' => [
        'action' => 'Annuler la ligne',
        'title' => 'Annuler la ligne de commande',
        'message' => 'Le reste non livré (:qty) est annulé : la quantité commandée tombe à la quantité livrée (:delivered) et les tâches encore ouvertes sont fermées. La ligne reste visible, marquée « Annulée ».',
        'reason' => 'Motif',
        'reason_help' => 'Par exemple : ligne annulée par le client, mail du 16/11.',
        'confirm' => 'Annuler la ligne',
        'close' => 'Fermer',
        'badge' => 'Annulée',
        'badge_title' => 'Annulée le :at — :qty annulée(s) — :reason',
        'done' => 'Ligne annulée',
        'delivery_action' => 'Annuler cette ligne de BL',
        'delivery_title' => 'Annuler la ligne de BL',
        'delivery_message' => 'La ligne disparaît du BL et sa quantité revient dans le reste à livrer de la commande. Si le BL avait sorti la pièce du stock, elle y est remise.',
        'delivery_done' => 'Ligne de BL annulée',
        'delivery_stock_restored' => ':qty remise(s) en stock.',
    ],
];
