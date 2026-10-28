<?php

return [

    /*
     * Journal des consultations (traçabilité nucléaire) : qui a ouvert quelle fiche
     * sensible (opportunité, devis, commande, ligne de commande, fichier de la GED, lot).
     * Les modifications sont déjà tracées par spatie/laravel-activitylog ; ce journal
     * couvre les lectures. Désactivé par défaut : aucune écriture tant que l'option
     * n'est pas posée.
     */
    'log_reads' => (bool) env('AUDIT_LOG_READS', false),

    /*
     * Une consultation du même sujet par le même utilisateur n'est retenue qu'une fois
     * par fenêtre (minutes), pour ne pas gonfler activity_log à chaque rechargement.
     */
    'dedup_minutes' => (int) env('AUDIT_LOG_READS_DEDUP_MINUTES', 10),

    /*
     * log_name des entrées dans activity_log. Purgées avec le reste par
     * activitylog:clean (rétention de config/activitylog.php).
     */
    'log_name' => 'access',
];
