<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Event handlers map
    |--------------------------------------------------------------------------
    |
    | Chaque event_type reçu sur /api/integrations/{system}/inbound est
    | résolu ici vers une classe implémentant IntegrationEventHandler.
    | Les entrées absentes sont marquées "no_handler" dans le journal des
    | livraisons, mais ne cassent pas la réception.
    |
    */

    'handlers' => [
        // Phase 1b — retour tasks depuis Nest2Prod
        'task.started'         => \App\Services\Integrations\Handlers\HandleTaskEvent::class,
        'task.paused'          => \App\Services\Integrations\Handlers\HandleTaskEvent::class,
        'task.resumed'         => \App\Services\Integrations\Handlers\HandleTaskEvent::class,
        'task.finished'        => \App\Services\Integrations\Handlers\HandleTaskEvent::class,
        'task.declared_good'   => \App\Services\Integrations\Handlers\HandleTaskEvent::class,
        'task.declared_bad'    => \App\Services\Integrations\Handlers\HandleTaskEvent::class,
        'task.commented'       => \App\Services\Integrations\Handlers\HandleTaskEvent::class,

        // Phase 2 — stock
        'stock.consumed'          => \App\Services\Integrations\Handlers\HandleStockEvent::class,
        // 'stock.produced' (V2 différé — mapping vers typ_move=12 sur produit fini)

        // Phase 3 — job status
        'job.status_changed'      => \App\Services\Integrations\Handlers\HandleJobStatusEvent::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Nombre de jours de conservation des lignes integration_deliveries avant
    | purge par la commande planifiée.
    |
    */

    'delivery_retention_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Payload log truncation
    |--------------------------------------------------------------------------
    |
    | Taille max (en octets) des colonnes payload/response_body stockées.
    | Au-delà, on tronque pour ne pas exploser la table de log.
    |
    */

    'payload_max_bytes' => 65_000,
];
