<?php

return [
    'short' => 'BPF',
    'label' => 'Bon pour fabrication',
    'release' => 'Bon pour fabrication',
    'revoke' => 'Annuler le BPF',
    'released' => 'BPF',
    'not_released' => 'Sans BPF',
    'released_on' => 'BPF le :date par :user',
    'revoke_confirm' => 'Annuler le bon pour fabrication de cette ligne ?',
    'released_ok' => 'Bon pour fabrication donné.',
    'revoked_ok' => 'Bon pour fabrication annulé.',

    // Refus
    'blocked_start' => 'La ligne de commande :line n\'a pas le bon pour fabrication du bureau d\'études : la tâche ne peut pas démarrer.',
    'blocked_kanban' => 'Tâches sans bon pour fabrication, déplacement annulé : :tasks.',
    'not_article' => 'Une ligne de présentation ne se fabrique pas.',
    'already_released' => 'Cette ligne a déjà le bon pour fabrication.',
    'not_released_yet' => 'Cette ligne n\'a pas de bon pour fabrication.',
    'review_rejected' => 'La revue de la commande est rejetée : pas de bon pour fabrication.',
    'change_pending' => 'Une demande de changement est en attente d\'approbation sur la commande : pas de bon pour fabrication avant son approbation.',
    'already_started' => 'Une tâche de cette ligne a déjà démarré : le bon pour fabrication ne peut plus être annulé.',

    // Écrans
    'planning_unreleased' => ':count sans BPF',
    'gantt_unreleased' => '(sans BPF)',
];
