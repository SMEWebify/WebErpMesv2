<?php

return [
    // Liens entre opérations d'une gamme
    'dependency_short' => [
        'FS' => 'FS',
        'SS' => 'SS',
        'FF' => 'FF',
    ],
    'dependency_types' => [
        'FS' => 'Ende-Anfang',
        'SS' => 'Anfang-Anfang',
        'FF' => 'Ende-Ende',
    ],
    'dependency_implicit'          => 'nach :ordre',
    'notation_unreadable'          => 'Verknüpfung nicht lesbar: „:item“ (Beispiele: 20FS, 30SS+2h, 20SS+10pc).',
    'notation_unknown_operation'   => 'Kein Arbeitsgang :ordre in diesem Arbeitsplan.',
    'notation_unknown_type'        => 'Unbekannter Verknüpfungstyp in „:item“: FS, SS oder FF.',
    'notation_unknown_unit'        => 'Unbekannte Einheit in „:item“: h oder pc.',
    'dependency_self'              => 'Ein Arbeitsgang kann nicht von sich selbst abhängen.',
    'dependency_other_routing'     => 'Der Arbeitsgang :ordre gehört nicht zum selben Arbeitsplan.',
    'dependency_not_schedulable'   => 'Nur Fertigungs- und Fremdfertigungsarbeitsgänge können verknüpft werden.',
    'dependency_piece_lag'         => 'Ein Versatz in Stück ist nur bei Anfang-Anfang oder Ende-Ende möglich.',
    'dependency_lag_too_large'     => 'Versatz zu groß: höchstens :max.',
    'dependency_duplicate'         =>'Die Verknüpfung :link ist doppelt erfasst.',
    'dependency_cycle'             => 'Zirkuläre Verknüpfungen: :cycle.',
];
