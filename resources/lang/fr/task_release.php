<?php

return [
    'title' => 'Visa de libération',
    'requires_release' => 'Visa de libération requis',
    'requires_release_help' => 'Une tâche terminée sur ce poste attend le visa d\'un responsable avant de passer à la suivante (simple avertissement, jamais de blocage).',

    // États
    'state_to_finish' => 'Visa à donner une fois la tâche terminée',
    'state_awaiting' => 'Terminée, en attente du visa',
    'state_released' => 'Libérée',
    'badge_awaiting' => 'À viser',
    'badge_released' => 'Libérée',
    'badge_to_finish' => 'Visa requis',
    'badge_predecessor' => 'Précédente non libérée',
    'released_on' => 'Libérée le :date par :user',

    // Actions
    'comment' => 'Commentaire (facultatif)',
    'release' => 'Viser et libérer',
    'revoke' => 'Annuler le visa',
    'revoke_confirm' => 'Annuler le visa de libération de cette tâche ?',
    'released_ok' => 'Phase libérée.',
    'revoked_ok' => 'Visa annulé.',
    'no_permission' => 'Le visa est donné par un porteur de la permission « task-release ».',

    // Avertissement sur la tâche suivante (jamais bloquant)
    'predecessor_warning' => 'L\'opération précédente :tasks n\'a pas reçu son visa de libération.|Les opérations précédentes :tasks n\'ont pas reçu leur visa de libération.',
    'start_confirm' => 'Démarrer quand même ?',

    // Refus
    'forbidden' => 'Vous n\'avez pas la permission de viser une phase.',
    'not_required' => 'Le poste de charge de cette tâche ne demande pas de visa.',
    'already_released' => 'Cette tâche est déjà libérée.',
    'not_finished' => 'La tâche doit être terminée avant d\'être visée.',
    'not_released_yet' => 'Cette tâche n\'a pas de visa.',
];
