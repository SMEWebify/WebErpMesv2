<?php

/*
|--------------------------------------------------------------------------
| Import DPGF (décomposition du prix global et forfaitaire)
|--------------------------------------------------------------------------
|
| Lecture d'un DPGF ou d'un BPU / DQE d'appel d'offres en lignes de devis
| (App\Services\Quotes\DpgfImporter). Les noms sont comparés comme ceux de
| l'assistant d'import (ImportCsvService::normalizeHeader : sans casse, sans
| accents ni ponctuation), d'abord à l'identique, puis en début de texte pour
| les noms de 3 caractères et plus.
|
*/

return [
    // Bouton « Importer un DPGF » des lignes de devis. Inactif : ni bouton, routes en 404.
    'enabled' => (bool) env('QUOTES_DPGF_IMPORT', false),

    // Lignes lues au plus dans une feuille (en-tête compris).
    'max_rows' => 2000,

    // En-tête cherché dans les premières lignes non vides de la feuille.
    'header_scan_rows' => 40,

    'columns' => [
        'code'   => ['n', 'no', 'num', 'numero', 'n poste', 'n article', 'n prix', 'poste', 'art', 'article', 'repere', 'ref', 'reference', 'item', 'code', 'index'],
        'label'  => ['designation', 'designations', 'designation des ouvrages', 'libelle', 'description', 'nature des ouvrages', 'nature des travaux', 'ouvrage', 'ouvrages', 'intitule', 'prestations', 'libelle des prestations'],
        'unit'   => ['u', 'un', 'unite', 'unites', 'unit'],
        'qty'    => ['q', 'qt', 'qte', 'qtes', 'quantite', 'quantites', 'quantity', 'qte entreprise', 'quantite entreprise'],
        'price'  => ['pu', 'p u', 'pu ht', 'p u ht', 'prix unitaire', 'prix unitaire ht', 'prix u', 'prix', 'pu en euros', 'pu euros', 'unit price'],
        'amount' => ['montant', 'montant ht', 'montants', 'total', 'total ht', 'prix total', 'prix total ht', 'pt', 'p t', 'pt ht', 'montant total', 'amount'],
    ],

    // Unités d'un DPGF : chaque groupe désigne la même unité. Une unité de
    // l'ERP dont le code ou le libellé est dans un groupe reconnaît tout le groupe.
    'units' => [
        ['u', 'un', 'unite', 'unites', 'pce', 'pc', 'pcs', 'piece', 'pieces', 'nb', 'nbre', 'nombre', 'unit'],
        ['ens', 'ensemble', 'ensembles', 'e', 'en'],
        ['ft', 'f', 'fft', 'forfait', 'forf', 'for', 'fg', 'gf'],
        ['m2', 'metre carre', 'metres carres', 'mc'],
        ['m3', 'metre cube', 'metres cubes'],
        ['ml', 'm l', 'metre lineaire', 'metres lineaires', 'mlin'],
        ['m', 'metre', 'metres'],
        ['kg', 'kilo', 'kilogramme', 'kilogrammes'],
        ['t', 'tonne', 'tonnes'],
        ['h', 'heure', 'heures', 'hr'],
        ['j', 'jour', 'jours', 'jr'],
    ],

    // Unités pour lesquelles une quantité absente vaut 1 (un forfait se chiffre une fois).
    'lump_sum_units' => ['ens', 'ft'],

    // Mentions qui remplacent une quantité ou un prix : le poste devient un texte.
    'mentions' => ['pm', 'p m', 'pour memoire', 'inclus', 'compris', 'sans objet', 'so', 's o', 'nc', 'offert', 'non chiffre', 'non prevu', 'np', 'sans', 'neant'],
];
