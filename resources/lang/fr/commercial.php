<?php

// Écrans de présentation des fonctionnalités réservées à la version commerciale (Nest2Prod).
return [
    'badge'   => 'Version commerciale',
    'contact' => 'Prendre contact avec Nest2Prod',
    'press_brake' => [
        'title' => 'Outillage de presse plieuse',
        'lead'  => 'Le configurateur de poinçon et le catalogue d\'outillage constructeurs sont disponibles dans la version commerciale de l\'ERP, éditée par Nest2Prod.',
        'designer_title' => 'Poinçons sur mesure',
        'designer_text'  => 'Profil paramétrique ou issu des bibliothèques constructeurs.',
        'simulation_title' => 'Simulation du pli',
        'simulation_text'  => 'Vérification de la pièce pliée et des collisions avec l\'outil.',
        'catalog_title' => 'Catalogue constructeurs',
        'catalog_text'  => 'Poinçons, matrices et adaptateurs consultables et filtrables.',
        'screenshot_alt'     => 'Simulation du pliage dans NEST2BEND : pièce, outillage, séquence et détection de collision',
        'screenshot_caption' => 'Simulation du pli 2 dans NEST2BEND : la collision avec le poinçon est détectée avant d\'aller à la presse.',
    ],
    'nesting' => [
        'title' => 'Imbrication des pièces sur tôle',
        'lead'  => 'Le calcul d\'imbrication en forme exacte et le besoin matière qui en découle sont disponibles dans la version commerciale de l\'ERP, éditée par Nest2Prod.',
        'shape_title' => 'Forme exacte',
        'shape_text'  => 'Placement sur le contour réel des pièces DXF / SVG, avec rotations et espacement de coupe.',
        'sheets_title' => 'Tôles prêtes à découper',
        'sheets_text'  => 'Aperçu de chaque tôle et export DXF du placement.',
        'stock_title' => 'Besoin matière',
        'stock_text'  => 'Nombre de tôles et de barres croisé avec le stock et les achats en attente.',
    ],
    'digital_twin' => [
        'title' => 'Jumeau numérique de l\'atelier',
        'lead'  => 'La représentation de l\'atelier et la simulation des flux de production sont disponibles dans la version commerciale de l\'ERP, éditée par Nest2Prod.',
        'video' => 'Vidéo de présentation du jumeau numérique',
        'flow_title' => 'Flux en direct',
        'flow_text'  => 'Les ordres de fabrication circulent entre les postes de charge, au rythme de l\'avancement réel.',
        'simulation_title' => 'Simulation de scénarios',
        'simulation_text'  => 'Tester une commande urgente, une panne ou un changement d\'équipe avant de l\'appliquer au planning.',
        'bottleneck_title' => 'Goulets d\'étranglement',
        'bottleneck_text'  => 'Files d\'attente et taux de charge par poste pour repérer où le flux se bloque.',
    ],
];
