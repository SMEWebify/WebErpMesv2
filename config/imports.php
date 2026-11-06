<?php

/*
|--------------------------------------------------------------------------
| Imports CSV / Excel
|--------------------------------------------------------------------------
|
| Champs proposés par l'assistant d'import (Votre société › Imports/Exports)
| et par les anciens formulaires « numéro de colonne ». Pour chaque champ :
| libellé (clé de traduction), obligatoire ou non, et noms d'en-tête qui le
| désignent dans un fichier, pour proposer la correspondance automatiquement.
|
| Les noms d'en-tête sont comparés sans casse, sans accents ni ponctuation
| (« N° TVA intra. » = « n tva intra ») : d'abord à l'identique, puis en début
| d'en-tête (« Épaisseur (mm) » commence par « epaisseur »). Le libellé du
| champ et sa clé technique comptent aussi comme noms reconnus.
|
*/

$comment  = ['label' => 'general_content.comment_trans_key', 'synonyms' => ['commentaire', 'comment', 'remarque', 'remarques', 'observation', 'observations', 'note', 'notes']];
$docCode  = ['label' => 'general_content.external_id_trans_key', 'required' => true, 'synonyms' => ['code', 'numero', 'n', 'no', 'num', 'reference', 'ref', 'code devis', 'n devis', 'numero devis', 'code commande', 'n commande', 'numero commande', 'piece', 'n piece']];
$docLabel = ['label' => 'general_content.label_trans_key', 'required' => true, 'synonyms' => ['label', 'libelle', 'designation', 'objet', 'titre', 'intitule', 'description']];
$document = [
    'code'               => $docCode,
    'label'              => $docLabel,
    'customer_reference' => ['label' => 'general_content.customer_reference_trans_key', 'synonyms' => ['reference client', 'ref client', 'votre reference', 'vos references', 'customer reference', 'chantier', 'affaire']],
    'companies_id'       => ['label' => 'general_content.id_customer_trans_key', 'required' => true, 'synonyms' => ['client', 'code client', 'code tiers', 'tiers', 'customer', 'customer code', 'compte client']],
    'validity_date'      => ['label' => 'general_content.validity_date_trans_key', 'synonyms' => ['validite', 'date de validite', 'date validite', 'valable jusqu au', 'echeance', 'date']],
    'comment'            => $comment,
];
$line = [
    'code'          => ['label' => 'general_content.external_id_trans_key', 'required' => true, 'synonyms' => ['code', 'reference', 'ref', 'code article', 'article', 'part number', 'pn']],
    'label'         => ['label' => 'general_content.label_trans_key', 'required' => true, 'synonyms' => ['label', 'libelle', 'designation', 'description', 'intitule']],
    'qty'           => ['label' => 'general_content.qty_trans_key', 'required' => true, 'synonyms' => ['qte', 'quantite', 'qty', 'quantity', 'qt', 'nombre', 'nb']],
    'selling_price' => ['label' => 'general_content.price_trans_key', 'required' => true, 'synonyms' => ['prix', 'prix unitaire', 'pu', 'pu ht', 'prix ht', 'prix unitaire ht', 'price', 'unit price']],
    'discount'      => ['label' => 'general_content.discount_trans_key', 'synonyms' => ['remise', 'remise %', 'discount', 'rabais']],
    'delivery_date' => ['label' => 'general_content.delivery_date_trans_key', 'synonyms' => ['date livraison', 'date de livraison', 'livraison', 'delai', 'date', 'delivery date']],
];

return [
    // Lignes affichées dans l'aperçu de l'assistant.
    'preview_rows' => 20,

    // Fichier déposé, gardé le temps de l'assistant (stockage privé) puis supprimé.
    'temporary_hours' => 24,

    'types' => [
        'companies' => [
            'code'                => ['label' => 'general_content.external_id_trans_key', 'required' => true, 'synonyms' => ['code', 'code tiers', 'code client', 'id', 'identifiant', 'reference', 'ref', 'numero client', 'n client', 'compte', 'compte client']],
            'label'               => ['label' => 'general_content.name_company_trans_key', 'required' => true, 'synonyms' => ['label', 'raison sociale', 'nom', 'societe', 'name', 'company', 'libelle', 'denomination', 'entreprise']],
            'website'             => ['label' => 'general_content.web_link_trans_key', 'synonyms' => ['site', 'site web', 'site internet', 'website', 'url', 'web']],
            'fbsite'              => ['label' => 'Facebook', 'synonyms' => ['facebook']],
            'twittersite'         => ['label' => 'X', 'synonyms' => ['twitter', 'x']],
            'lkdsite'             => ['label' => 'Linkedin', 'synonyms' => ['linkedin']],
            'siren'               => ['label' => 'Siren', 'synonyms' => ['siren', 'siret', 'n siren', 'numero siren']],
            'naf_code'            => ['label' => 'Naf Code', 'synonyms' => ['naf', 'code naf', 'ape', 'code ape', 'naf code']],
            'intra_community_vat' => ['label' => 'general_content.vat_number_trans_key', 'synonyms' => ['tva', 'n tva', 'tva intra', 'numero tva', 'tva intracommunautaire', 'n tva intracommunautaire', 'numero de tva', 'vat', 'vat number']],
            'discount'            => ['label' => 'general_content.discount_trans_key', 'synonyms' => ['remise', 'remise %', 'discount', 'escompte']],
        ],

        'products' => [
            'code'              => ['label' => 'general_content.external_id_trans_key', 'required' => true, 'synonyms' => ['code', 'reference', 'ref', 'code article', 'article', 'part number', 'pn', 'reference article']],
            'label'             => ['label' => 'general_content.label_trans_key', 'required' => true, 'synonyms' => ['label', 'designation', 'libelle', 'description', 'nom', 'intitule']],
            'ind'               => ['label' => 'general_content.index_trans_key', 'synonyms' => ['ind', 'indice', 'revision', 'rev']],
            'purchased_price'   => ['label' => 'general_content.purchased_price_trans_key', 'synonyms' => ['prix achat', 'prix d achat', 'cout', 'cout achat', 'purchase price', 'pa', 'pa ht']],
            'selling_price'     => ['label' => 'general_content.price_trans_key', 'synonyms' => ['prix vente', 'prix de vente', 'prix', 'pv', 'pv ht', 'selling price', 'price', 'tarif']],
            'material'          => ['label' => 'general_content.material_trans_key', 'synonyms' => ['matiere', 'material', 'nuance', 'materiau']],
            'thickness'         => ['label' => 'general_content.thickness_trans_key', 'synonyms' => ['epaisseur', 'ep', 'thickness']],
            'weight'            => ['label' => 'general_content.weight_trans_key', 'synonyms' => ['poids', 'weight', 'masse']],
            'finishing'         => ['label' => 'general_content.finishing_trans_key', 'synonyms' => ['finition', 'finishing', 'traitement', 'revetement', 'traitement de surface']],
            'x_size'            => ['label' => 'general_content.x_size_trans_key', 'synonyms' => ['long x', 'longueur', 'dim x', 'x', 'long', 'length']],
            'y_size'            => ['label' => 'general_content.y_size_trans_key', 'synonyms' => ['larg y', 'largeur', 'dim y', 'y', 'larg', 'width']],
            'z_size'            => ['label' => 'general_content.z_size_trans_key', 'synonyms' => ['haut z', 'hauteur', 'dim z', 'z', 'height']],
            'x_oversize'        => ['label' => 'general_content.x_oversize_trans_key', 'synonyms' => ['surlongueur x', 'surcote x']],
            'y_oversize'        => ['label' => 'general_content.y_oversize_trans_key', 'synonyms' => ['surlargeur y', 'surcote y']],
            'z_oversize'        => ['label' => 'general_content.z_oversize_trans_key', 'synonyms' => ['surhauteur z', 'surcote z']],
            'comment'           => $comment,
            'qty_eco_min'       => ['label' => 'general_content.quantite_eco_min_trans_key', 'synonyms' => ['qte eco min', 'quantite economique min', 'qte mini']],
            'qty_eco_max'       => ['label' => 'general_content.quantite_eco_max_trans_key', 'synonyms' => ['qte eco max', 'quantite economique max', 'qte maxi']],
            'diameter'          => ['label' => 'general_content.diameter_trans_key', 'synonyms' => ['diametre', 'diameter', 'o']],
            'diameter_oversize' => ['label' => 'general_content.diameter_oversize_trans_key', 'synonyms' => ['surcote diametre']],
            'section_size'      => ['label' => 'general_content.section_size_trans_key', 'synonyms' => ['section']],
        ],

        'quotes' => $document,
        'orders' => $document,

        'quote-lines' => $line,
        'order-lines' => $line + [
            'ind' => ['label' => 'general_content.index_trans_key', 'synonyms' => ['ind', 'indice', 'revision', 'rev']],
        ],
    ],
];
