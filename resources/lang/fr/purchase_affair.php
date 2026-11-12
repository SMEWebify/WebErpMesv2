<?php

return [
    // Rattachement d'un achat à une affaire (en-tête)
    'link'                => 'Affaire rattachée',
    'none'                => 'Aucune',
    'help'                => 'Pour un achat sans opération (frais, outillage dédié) : il apparaît dans le dossier d\'affaire.',
    'invalid'             => 'Le document rattaché est introuvable.',
    'group_orders'        => 'Commandes',
    'group_quotes'        => 'Devis',
    'group_opportunities' => 'Opportunités',

    // Demande d'achat depuis un devis
    'source'                  => 'Opérations de',
    'source_orders'           => 'Commandes fermes',
    'source_quotes'           => 'Devis en cours',
    'source_quotes_help'      => 'Opérations des devis pas encore commandés : demande de prix seulement, pour chiffrer le devis.',
    'quote'                   => 'Devis',
    'order_needs_firm_order'  => 'Une commande d\'achat ne peut porter que sur des opérations de commande ferme. Pour un devis, lancez une demande de prix.',
    'quotation_tasks_invalid' => 'Certaines opérations ne viennent ni d\'une commande ferme ni d\'un devis en cours.',
    'quote_not_orderable'     => 'Ligne d\'un devis pas encore commandé : retenez le prix pour le devis, la commande d\'achat se fera une fois le devis converti en commande.',

    // Réponse fournisseur retenue pour le devis
    'retain'         => 'Retenir pour le devis',
    'retain_help'    => 'Le prix de cette ligne devient le coût de l\'opération du devis (marge interne).',
    'retained'       => 'Prix retenu pour le devis : :price.',
    'retained_badge' => 'Retenu',
    'retain_refused' => [
        'not_quote_task' => 'Cette ligne ne porte pas sur une opération de devis.',
        'converted'      => 'Le devis a déjà été converti en commande.',
        'quote_closed'   => 'Le devis n\'est plus ouvert ni envoyé.',
    ],
];
