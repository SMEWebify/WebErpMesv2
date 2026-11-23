<?php

return [
    'title' => 'Freigabevermerk',
    'requires_release' => 'Freigabevermerk erforderlich',
    'requires_release_help' => 'Ein auf diesem Arbeitsplatz abgeschlossener Arbeitsgang wartet auf die Freigabe eines Verantwortlichen, bevor es mit dem nächsten weitergeht (nur Warnung, niemals Sperre).',

    // États
    'state_to_finish' => 'Freigabe zu erteilen, sobald der Arbeitsgang abgeschlossen ist',
    'state_awaiting' => 'Abgeschlossen, wartet auf Freigabe',
    'state_released' => 'Freigegeben',
    'badge_awaiting' => 'Freizugeben',
    'badge_released' => 'Freigegeben',
    'badge_to_finish' => 'Freigabe erforderlich',
    'badge_predecessor' => 'Vorgänger nicht freigegeben',
    'released_on' => 'Freigegeben am :date von :user',

    // Actions
    'comment' => 'Kommentar (optional)',
    'release' => 'Abzeichnen und freigeben',
    'revoke' => 'Freigabe zurücknehmen',
    'revoke_confirm' => 'Freigabevermerk dieses Arbeitsgangs zurücknehmen?',
    'released_ok' => 'Arbeitsgang freigegeben.',
    'revoked_ok' => 'Freigabe zurückgenommen.',
    'no_permission' => 'Die Freigabe wird von einem Inhaber der Berechtigung „task-release“ erteilt.',

    // Avertissement sur la tâche suivante (jamais bloquant)
    'predecessor_warning' => 'Der vorherige Arbeitsgang :tasks hat keinen Freigabevermerk erhalten.|Die vorherigen Arbeitsgänge :tasks haben keinen Freigabevermerk erhalten.',
    'start_confirm' => 'Trotzdem starten?',

    // Refus
    'forbidden' => 'Sie sind nicht berechtigt, einen Arbeitsgang freizugeben.',
    'not_required' => 'Der Arbeitsplatz dieses Arbeitsgangs erfordert keine Freigabe.',
    'already_released' => 'Dieser Arbeitsgang ist bereits freigegeben.',
    'not_finished' => 'Der Arbeitsgang muss abgeschlossen sein, bevor er freigegeben werden kann.',
    'not_released_yet' => 'Dieser Arbeitsgang hat keine Freigabe.',
];
