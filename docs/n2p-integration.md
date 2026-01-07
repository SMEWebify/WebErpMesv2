# Nest2Prod (N2P) - Intégration WEM

## Paramètres Settings
- `n2p_enabled` : activer/désactiver l’envoi (bool).
- `n2p_base_url` : URL de base N2P (ex: `https://n2p.example.com`).
- `n2p_api_token` : token API (Bearer).
- `n2p_send_on_order_status_from` : statut source (ex: `OPEN`).
- `n2p_send_on_order_status_to` : statut cible (ex: `IN_PROGRESS`).
- `n2p_job_status_on_send` : statut job envoyé (par défaut `released`).
- `n2p_priority_default` : priorité par défaut 1..5 (par défaut `3`).
- `n2p_send_tasks` : envoyer les tâches liées aux lignes (bool).
- `n2p_verify_ssl` : vérifier le certificat SSL lors des appels API (bool, par défaut `true`).

## Happy path (envoi automatique)
1. Activer `n2p_enabled`, renseigner `n2p_base_url` et `n2p_api_token`.
2. Saisir les statuts source/cible (ex: OPEN -> IN_PROGRESS).
3. Passer une commande de `statu` 1 à `statu` 2 (OPEN -> IN_PROGRESS).
4. Vérifier le log `storage/logs/n2p.log` et les colonnes `n2p_last_push_*` sur la commande.

## Cas « N2P down »
- Forcer un échec (ex: arrêter N2P ou URL invalide), relancer le changement de statut.
- Vérifier que le job est ré-essayé (backoff progressif) et que `n2p_last_push_status = ERROR` contient le message.

## Queue worker
- Lancement standard : `php artisan queue:work`.
- Le job `PushOrderToN2P` est réessayé 5 fois avec backoff `[60, 300, 900, 1800, 3600]`.

## Commandes utiles
- Pousser une commande : `php artisan wem:n2p:push-order {orderId}`.
- En synchrone (sans queue) : `php artisan wem:n2p:push-order {orderId} --sync`.

## Exemple de payload envoyé
```json
{
  "jobs": [
    {
      "of_code": "ORD-1001",
      "line_ref": "55",
      "required_qty": 10,
      "status": "released",
      "priority": 2,
      "due_date": "2026-03-01",
      "customer_code": "CLI1",
      "customer_name": "Client One",
      "product_ref": "PART-001",
      "tasks": [
        {
          "operation_code": "CUT",
          "workcenter_code": "CNC01",
          "required_qty": 10,
          "planned_start_at": "2026-02-21 08:00:00",
          "planned_end_at": "2026-02-21 10:00:00",
          "planned_time_min": 90
        }
      ]
    }
  ]
}
```
