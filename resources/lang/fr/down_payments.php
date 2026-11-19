<?php

return [
    'document_title'  => "FACTURE D'ACOMPTE",
    'type_label'      => 'Acompte',
    'invoice_label'   => 'Acompte sur commande :order',
    'line_label'      => 'Acompte :percent % sur commande :order',
    'deduction_label' => 'Déduction acompte :code',

    'card' => [
        'title'          => 'Acomptes',
        'none'           => 'Aucune facture d\'acompte.',
        'order_total'    => 'Total commande HT',
        'invoice'        => 'Facture',
        'ht'             => 'HT',
        'paid'           => 'Payé TTC',
        'deducted'       => 'Déduit HT',
        'draft'          => 'Brouillon',
        'mode_percent'   => '%',
        'mode_amount'    => '€ HT',
        'value'          => 'Montant',
        'create'         => 'Facture d\'acompte',
        'help'           => 'L\'acompte ne change pas les quantités : il est déduit des factures suivantes au prorata de ce qui est facturé, à hauteur de ce qui est payé.',
    ],

    'invoice' => [
        'deducts'        => 'Déduit l\'acompte',
        'recompute'      => 'Recalculer la déduction d\'acompte',
        'recomputed'     => 'Déduction d\'acompte recalculée.',
        'is_down_payment'=> 'Facture d\'acompte de la commande :order : HT :ht, déjà déduit :deducted, reste à déduire :remaining.',
        'unpaid_warning' => 'Acompte :code non payé : il n\'est pas déduit de cette facture.',
    ],

    'created' => 'Facture d\'acompte :code créée en brouillon.',

    'refused' => [
        'internal_order'  => 'Une commande interne ne reçoit pas d\'acompte.',
        'canceled_order'  => 'Commande annulée : pas d\'acompte.',
        'empty_order'     => 'La commande n\'a aucun montant à facturer.',
        'amount'          => 'Le montant de l\'acompte doit être positif.',
        'over_total'      => 'Les acomptes dépasseraient le total de la commande (reste possible : :max € HT).',
        'not_draft'       => 'La facture n\'est plus en brouillon.',
        'not_a_deduction' => 'Cette ligne n\'est pas une déduction d\'acompte.',
        'deduction_max'   => 'Déduction impossible au-delà de la part payée et non déduite de l\'acompte (:max € HT).',
        'credit_note_on_deduction' => 'Une déduction d\'acompte ne se reprend pas par avoir : sélectionnez les lignes vendues.',
        'credit_note_on_deducted'  => 'L\'acompte :code est déjà déduit d\'une facture : faites l\'avoir sur la facture finale.',
    ],
];
