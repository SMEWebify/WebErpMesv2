<?php

return [
    // Liens entre opérations d'une gamme
    'dependency_short' => [
        'FS' => 'FD',
        'SS' => 'DD',
        'FF' => 'FF',
    ],
    'dependency_types' => [
        'FS' => 'Fin à début',
        'SS' => 'Début à début',
        'FF' => 'Fin à fin',
    ],
    'dependency_implicit'          => 'après :ordre',
    'notation_unreadable'          => 'Lien illisible : « :item » (exemples : 20FD, 30DD+2h, 20DD+10pc).',
    'notation_unknown_operation'   => 'Aucune opération :ordre dans cette gamme.',
    'notation_unknown_type'        => 'Type de lien inconnu dans « :item » : FD, DD ou FF.',
    'notation_unknown_unit'        => 'Unité inconnue dans « :item » : h ou pc.',
    'dependency_self'              => 'Une opération ne peut pas dépendre d\'elle-même.',
    'dependency_other_routing'     => 'L\'opération :ordre n\'appartient pas à la même gamme.',
    'dependency_not_schedulable'   => 'Seules les opérations de fabrication et de sous-traitance peuvent être liées.',
    'dependency_piece_lag'         => 'Un décalage en pièces n\'est possible qu\'en début-début ou fin-fin.',
    'dependency_duplicate'         => 'Le lien :link est saisi deux fois.',
    'dependency_cycle'             => 'Liens circulaires : :cycle.',
];
