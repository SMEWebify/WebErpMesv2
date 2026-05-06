<?php

return [
    'exception_message' => "Message de l'exception : :message",
    'exception_trace' => "Trace de l'exception : :trace",
    'exception_message_title' => "Message de l'exception",
    'exception_trace_title' => "Trace de l'exception",

    'backup_failed_subject' => '[ERREUR] Sauvegarde échouée — :application_name',
    'backup_failed_body' => 'Important : Une erreur est survenue lors de la sauvegarde de :application_name',

    'backup_successful_subject' => '[OK] Sauvegarde réussie — :application_name',
    'backup_successful_subject_title' => 'Sauvegarde créée avec succès !',
    'backup_successful_body' => 'Une nouvelle sauvegarde de :application_name a été créée avec succès sur le disque :disk_name.',

    'cleanup_failed_subject' => '[ERREUR] Nettoyage des sauvegardes échoué — :application_name',
    'cleanup_failed_body' => 'Une erreur est survenue lors du nettoyage des sauvegardes de :application_name',

    'cleanup_successful_subject' => '[OK] Nettoyage des sauvegardes réussi — :application_name',
    'cleanup_successful_subject_title' => 'Sauvegardes nettoyées avec succès !',
    'cleanup_successful_body' => 'Le nettoyage des sauvegardes de :application_name sur le disque :disk_name a été effectué avec succès.',

    'healthy_backup_found_subject' => '[OK] Sauvegardes saines — :application_name (:disk_name)',
    'healthy_backup_found_subject_title' => 'Les sauvegardes sont saines',
    'healthy_backup_found_body' => 'Les sauvegardes pour :application_name sont en bonne santé.',

    'unhealthy_backup_found_subject' => '[ALERTE] Sauvegardes corrompues — :application_name',
    'unhealthy_backup_found_subject_title' => '[ALERTE] Sauvegardes corrompues — :application_name. :problem',
    'unhealthy_backup_found_body' => 'Les sauvegardes pour :application_name sur le disque :disk_name sont corrompues.',
    'unhealthy_backup_found_not_reachable' => "La destination de la sauvegarde n'est pas accessible. :error",
    'unhealthy_backup_found_empty' => "Il n'y a aucune sauvegarde pour cette application.",
    'unhealthy_backup_found_old' => 'La dernière sauvegarde du :date est considérée trop vieille.',
    'unhealthy_backup_found_unknown' => 'Désolé, une raison exacte ne peut être déterminée.',
    'unhealthy_backup_found_full' => 'Les sauvegardes utilisent trop d\'espace disque. L\'utilisation actuelle est de :disk_usage alors que la limite autorisée est de :disk_limit.',

    'no_backups_info' => 'Aucune sauvegarde n\'a encore été effectuée',
    'application_name' => "Nom de l'application",
    'backup_name' => 'Nom de la sauvegarde',
    'disk' => 'Disque',
    'newest_backup_size' => 'Taille de la sauvegarde la plus récente',
    'number_of_backups' => 'Nombre de sauvegardes',
    'total_storage_used' => 'Stockage total utilisé',
    'newest_backup_date' => 'Date de la sauvegarde la plus récente',
    'oldest_backup_date' => 'Date de la sauvegarde la plus ancienne',
];
