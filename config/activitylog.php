<?php

return [

    /*
     * If set to false, no activities will be saved to the database.
     */
    'enabled' => env('ACTIVITY_LOGGER_ENABLED', true),

    /*
     * Durée de la commande du paquet `activitylog:clean`, qui purge TOUS les journaux
     * sans distinction : elle n'est plus planifiée (remplacée par wem:activitylog:clean,
     * durées de `retention` ci-dessous). Valeur très haute pour qu'un lancement à la
     * main ne détruise pas le journal des modifications et des visas.
     */
    'delete_records_older_than_days' => 36500,

    /*
     * Conservation par log_name (ISO 19443), appliquée par wem:activitylog:clean.
     * Durées en jours ; null (ou variable vide) = jamais purgé.
     * - `log_names` : durée propre à un journal (consultations `access`).
     * - `default` : tous les autres (journal des modifications, visas, RGPD…),
     *   conservés pendant la vie de l'installation par défaut.
     */
    'retention' => [
        'default'   => env('ACTIVITYLOG_RETENTION_DAYS'),
        'log_names' => [
            'access' => env('ACTIVITYLOG_ACCESS_RETENTION_DAYS', 365),
        ],
    ],

    /*
     * If no log name is passed to the activity() helper
     * we use this default log name.
     */
    'default_log_name' => 'default',

    /*
     * You can specify an auth driver here that gets user models.
     * If this is null we'll use the current Laravel auth driver.
     */
    'default_auth_driver' => null,

    /*
     * If set to true, the subject returns soft deleted models.
     */
    'subject_returns_soft_deleted_models' => false,

    /*
     * This model will be used to log activity.
     * It should implement the Spatie\Activitylog\Contracts\Activity interface
     * and extend Illuminate\Database\Eloquent\Model.
     */
    'activity_model' => \Spatie\Activitylog\Models\Activity::class,

    /*
     * This is the name of the table that will be created by the migration and
     * used by the Activity model shipped with this package.
     */
    'table_name' => env('ACTIVITY_LOGGER_TABLE_NAME', 'activity_log'),

    /*
     * This is the database connection that will be used by the migration and
     * the Activity model shipped with this package. In case it's not set
     * Laravel's database.default will be used instead.
     */
    'database_connection' => env('ACTIVITY_LOGGER_DB_CONNECTION'),
];
