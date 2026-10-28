<?php

/**
 * Criticité des achats : valeurs proposées sur l'article, la ligne d'achat et
 * les règles des clauses qualité. Une valeur ajoutée ici devient saisissable
 * partout ; en retirer une ne touche pas aux lignes qui la portent déjà.
 */
return [

    // Documents de contrôle EN 10204
    'certificate_types' => ['2.1', '2.2', '3.1', '3.2'],

    // Codes de construction
    'construction_codes' => ['RCC-M', 'RCC-MX'],

    // Niveaux (NC = non classé)
    'nuclear_levels' => ['N1', 'N2', 'N3', 'NC'],

];
