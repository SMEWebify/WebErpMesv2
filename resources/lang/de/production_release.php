<?php

return [
    'short' => 'FFG',
    'label' => 'Fertigungsfreigabe',
    'release' => 'Fertigungsfreigabe',
    'revoke' => 'Fertigungsfreigabe zurücknehmen',
    'released' => 'FFG',
    'not_released' => 'Ohne FFG',
    'released_on' => 'FFG am :date von :user',
    'revoke_confirm' => 'Fertigungsfreigabe dieser Position zurücknehmen?',
    'bulk_action' => 'Fertigungsfreigabe',
    'bulk_result' => ':count Position(en) für die Fertigung freigegeben.',
    'bulk_refused' => 'Nicht freigegeben:',
    'bulk_none' => 'Die ausgewählten Positionen haben bereits ihre Fertigungsfreigabe.',
    'released_ok' => 'Fertigungsfreigabe erteilt.',
    'revoked_ok' => 'Fertigungsfreigabe zurückgenommen.',

    // Refus
    'blocked_start' => 'Die Auftragsposition :line hat keine Fertigungsfreigabe der Konstruktion: der Arbeitsgang kann nicht starten.',
    'blocked_kanban' => 'Arbeitsgänge ohne Fertigungsfreigabe, Verschieben abgebrochen: :tasks.',
    'not_article' => 'Eine Gliederungsposition wird nicht gefertigt.',
    'already_released' => 'Diese Position hat bereits die Fertigungsfreigabe.',
    'not_released_yet' => 'Diese Position hat keine Fertigungsfreigabe.',
    'review_rejected' => 'Die Vertragsprüfung des Auftrags ist abgelehnt: keine Fertigungsfreigabe.',
    'change_pending' => 'Eine Änderungsanfrage zum Auftrag wartet auf Genehmigung: keine Fertigungsfreigabe vor ihrer Genehmigung.',
    'amendment_pending' => 'Ein Nachtrag wartet auf die Freigabe der Vertragsprüfung: keine Fertigungsfreigabe vor dieser Freigabe.',
    'already_started' => 'Ein Arbeitsgang dieser Position ist bereits gestartet: die Fertigungsfreigabe kann nicht mehr zurückgenommen werden.',

    // Écrans
    'planning_unreleased' => ':count ohne FFG',
    'gantt_unreleased' => '(ohne FFG)',
];
