<?php

return [
    // Liens entre opérations d'une gamme
    // Codes courts FS/SS/FF : seuls FD/DD/FF et FS/SS/FF sont lus par DependencyNotation.
    'dependency_short' => [
        'FS' => 'FS',
        'SS' => 'SS',
        'FF' => 'FF',
    ],
    'dependency_types' => [
        'FS' => 'Fine-inizio',
        'SS' => 'Inizio-inizio',
        'FF' => 'Fine-fine',
    ],
    'dependency_implicit'          => 'dopo :ordre',
    'notation_unreadable'          => 'Collegamento illeggibile: « :item » (esempi: 20FS, 30SS+2h, 20SS+10pc).',
    'notation_unknown_operation'   => 'Nessuna operazione :ordre in questo ciclo di lavorazione.',
    'notation_unknown_type'        => 'Tipo di collegamento sconosciuto in « :item »: FS, SS o FF.',
    'notation_unknown_unit'        => 'Unità sconosciuta in « :item »: h o pc.',
    'dependency_self'              => 'Un\'operazione non può dipendere da se stessa.',
    'dependency_other_routing'     => 'L\'operazione :ordre non appartiene allo stesso ciclo di lavorazione.',
    'dependency_not_schedulable'   => 'Solo le operazioni di produzione e di conto lavoro possono essere collegate.',
    'dependency_piece_lag'         => 'Uno sfasamento in pezzi è possibile solo nei collegamenti inizio-inizio o fine-fine.',
    'dependency_lag_too_large'     => 'Sfasamento troppo grande: :max al massimo.',
    'dependency_duplicate'         =>'Il collegamento :link è inserito due volte.',
    'dependency_cycle'             => 'Collegamenti circolari: :cycle.',
];
