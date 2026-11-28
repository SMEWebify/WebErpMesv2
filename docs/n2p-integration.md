# Nest2Prod (N2P) - Intégration WEM

> Document interne (classes, tables, journaux cités). Dernière mise à jour : 2026-11-27.

## Réglage : endpoints d'intégration

Depuis le 2026-08-12, la liaison n'est plus réglée par des clés `n2p_*` de la table `settings`
(migrées puis supprimées par `2026_08_12_120100_migrate_n2p_settings_to_endpoint_metadata`) :
tout se règle dans **Votre société → Intégrations → Endpoints** (`admin.integrations.endpoints.*`),
sur deux endpoints `system_code = n2p` créés par `2026_08_10_120002_seed_n2p_integration_endpoints`.

**Sortant** (`direction = outbound`, envoi des OF) :
- `url` : URL de base de N2P ; `auth_method` `bearer` (jeton chiffré) ou `hmac` ;
  `verify_ssl` (une URL `http://` est refusée tant que la vérification est active) ;
  `is_active` ; `retry_max` 5, `retry_backoff` `[60, 300, 900, 1800, 3600]`.
- Règles métier dans `metadata` (carte « Règles métier N2P (push OF) » de la fiche) :
  `status_transition_from` / `status_transition_to` (défauts `OPEN` → `IN_PROGRESS`, valeurs
  `OPEN`, `IN_PROGRESS`, `DELIVERED`, `PARTLY_DELIVERED`, `STOPPED`, `CANCELED`),
  `send_tasks` (vrai), `job_status_on_send` (`released`), `default_priority` (3).

**Entrant** (`direction = inbound`, retour du MES) : `auth_method = hmac`, secret généré,
en-têtes `X-N2P-Timestamp` / `X-N2P-Signature-256`, tolérance 300 s, **inactif** à la création.
URL exposée : `POST /api/integrations/n2p/inbound`.

## Happy path (envoi automatique)
1. Activer l'endpoint sortant, renseigner son URL et son jeton.
2. Régler la transition de statut (ex. `OPEN` → `IN_PROGRESS`).
3. Passer une commande de `statu` 1 à `statu` 2 (`OrdersObserver` déclenche `PushOrderToN2P`).
4. Vérifier le journal `storage/logs/n2p.log` et les colonnes `n2p_last_push_*` de la commande.

## Cas « N2P down »
- Forcer un échec (ex. arrêter N2P ou URL invalide), relancer le changement de statut.
- Vérifier que le job est réessayé (backoff progressif) et que `n2p_last_push_status = ERROR`
  porte le message.

## Queue worker
- Lancement standard : `php artisan queue:work`.
- Le job `PushOrderToN2P` est réessayé 5 fois avec backoff `[60, 300, 900, 1800, 3600]`.

## Commandes utiles
- Pousser une commande : `php artisan wem:n2p:push-order {orderId}`.
- En synchrone (sans queue) : `php artisan wem:n2p:push-order {orderId} --sync`.
- Renvoyer toutes les commandes déjà envoyées et non entièrement livrées :
  `php artisan wem:n2p:push-order --open [--dry-run] [--force]` (voir `docs/pointage-scan.md`).
- Rejouer les réceptions de tôle vers N2P : `php artisan wem:n2p:sync-sheet-stock [--days=30] [--sync]`.
- Lecture par N2P du stock d'un lot de tôle `MV-{id}` : `GET /api/n2p/sheet-lots/{ref}/stock`
  (jeton Sanctum, décrit dans `docs/API.md` § 7).

## Calcul de `part_type`
Le payload pousse toujours une valeur `part_type` (jamais `null`). Règle de résolution
appliquée par `N2PPayloadBuilder::resolvePartType()` :

1. `details.part_type` ou `product.part_type` si renseigné explicitement.
2. `Assembly` — la ligne ou le produit possède des sous-ensembles (`SubAssembly`).
3. `SymbolPart` — `cad_file_path` ou `cam_file_path` se termine par `.sym` (fichier symbole Radan).
4. `StandardPart` — produit marqué `purchased = 1` (pièce du commerce / quincaillerie).
5. `SymbolPart` — `thickness > 0` ou présence d’un `cad_file_path` / `cam_file_path` (indicateurs tôle).
6. `StandardPart` — fallback (aucun signal exploitable).

## Retour des imbrications réalisées (N2P → ERP)

Point d'entrée : `POST /api/integrations/n2p/inbound`, enveloppe habituelle
(`event_id` UUID, `event_type`, `occurred_at`, `source`, `data`), signature HMAC
`X-N2P-Timestamp` / `X-N2P-Signature-256`, dédoublonnage par `event_id`.

Deux événements se complètent :

| Événement | Porte | Émis quand |
|---|---|---|
| `stock.consumed` (existant) | la **sortie de tôle** : lot `MV-<id>`, quantité, `consumed_by.nest_id` / `nest_name` | à chaque tôle consommée (delta de `sheet_qty_done`) |
| `nest.updated` (nouveau) | l'**état complet de l'imbrication** : pièces par OF, chutes, format, utilisation | à chaque changement de statut, et au moins une fois au statut 6 (terminé) |

Côté ERP (`App\Services\Stock\NestRecorder`) :
- l'imbrication est identifiée par (`n2p`, `nest_id`) ; l'ordre d'arrivée des deux événements est indifférent ;
- `nest.updated` est **rejouable** : la liste des pièces est remplacée à chaque message ;
- les chutes ne sont écrites **qu'une fois**, quand l'imbrication est terminée (statut 6) **et**
  qu'au moins une sortie `stock.consumed` est arrivée, sur la ligne et le lot de cette sortie ;
- une chute impossible (plus grande que la tôle…) est ignorée et journalisée, le message est
  accepté (pas de nouvel essai en boucle) ;
- une pièce dont l'OF est inconnu est gardée « non rattachée ».

### `nest.updated` — `data`
```json
{
  "nest_id": 77,
  "nest_name": "NEST-2026-042",
  "status": 6,
  "finished_at": "2026-11-06T10:00:00+01:00",
  "machine_name": "Laser 1",
  "material": "S235",
  "thickness": 3,
  "sheet_x": 3000,
  "sheet_y": 1500,
  "sheet_qty": 2,
  "sheet_qty_done": 2,
  "sheet_usage": "72.5",
  "stock_lots": [
    { "external_ref": "MV-12", "qty_consumed": 2 }
  ],
  "parts": [
    {
      "of_code": "OF42",
      "line_ref": "42",
      "part_name": "SUPPORT-A",
      "external_part_number": "P-0012",
      "planned_qty": 10,
      "good_qty": 10,
      "bad_qty": 0,
      "area_mm2": 125000.5,
      "weight_kg": 2.95
    }
  ],
  "remnants": [
    { "width": 1500, "height": 1000, "quantity": 1, "name": "CH-77-1", "is_available": true }
  ]
}
```

### Correspondance avec le modèle Nest2Prod MES
| Champ | Source N2P |
|---|---|
| `nest_id`, `nest_name`, `status`, `finished_at`, `machine_name` | `nests.id`, `nest_name`, `statu` (1-7), `finished_at`, `machine_name` |
| `material`, `thickness`, `sheet_x`, `sheet_y` | `nests.*` |
| `sheet_qty`, `sheet_qty_done`, `sheet_usage` | `nests.*` (`sheet_usage` peut contenir « % ») |
| `stock_lots[]` | `nest_stock_lots` → `stock_lots.external_ref`, `qty_consumed` |
| `parts[]` | `nest_parts` : `OF` → `of_code`, `part_name`, `external_part_number`, `planned_part_qty` → `planned_qty`, `good_qty`, `bad_qty`, surface et poids **d'une** pièce |
| `remnants[]` | `nest_scraps` : `width`, `height`, `quantity`, `scrap_name` → `name`, `is_available` |

Statuts : 1, 2, 4, 5 → prévue ; 3 → en cours ; 6 → découpée ; 7 → annulée.
Répartition du coût matière entre lignes : surface des pièces si toutes l'ont, sinon poids,
sinon nombre de pièces.

## Exemple de payload envoyé
Extrait (`N2PPayloadBuilder::build`, champs nuls omis dans les tâches). `of_code` = `OF` + id
de la ligne de commande, `line_ref` = id de la ligne.
```json
{
  "jobs": [
    {
      "of_code": "OF55",
      "line_ref": "55",
      "status": "released",
      "priority": 3,
      "due_date": "2026-03-01",
      "alias_erp": "PART-001",
      "customer_code": "CLI1",
      "customer_name": "Client exemple",
      "order_ref": "CMD-1001",
      "label": "Support plié",
      "required_qty": 10,
      "product_ref": "PART-001",
      "material": "S235",
      "thickness": 3,
      "part_type": "SymbolPart",
      "production_released": true,
      "tasks": [
        {
          "operation_code": "CUT",
          "seq": 10,
          "workcenter_code": "CNC01",
          "planned_start_at": "2026-02-21 08:00:00",
          "planned_end_at": "2026-02-21 10:00:00",
          "status": "planned",
          "planned_time_min": 90,
          "required_qty": 10
        }
      ]
    }
  ]
}
```
`production_released` n'est présent que si l'option `PRODUCTION_REQUIRE_RELEASE` est active.
Autres champs du job : `cad_file_path`, `cam_file_path`, `picture_base64`, `bend_count`,
`dimension_x/y/z`, `weight`, `notes`, `planned_start_at`, `planned_end_at`.

Retour d'exécution des tâches (`task.*`, pointage par scan) : voir `docs/pointage-scan.md`.
