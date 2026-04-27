# WebErpMesv2 — Documentation API

> Base URL : `/api`  
> Format : JSON (`Content-Type: application/json`)  
> Authentification : Token API Laravel (`Authorization: Bearer {token}`) sauf exceptions notées

---

## Sommaire

1. [Authentification](#authentification)
2. [Clients](#clients)
3. [Companies](#companies)
4. [Devis](#devis)
5. [Commandes](#commandes)
6. [Tâches de production](#tâches-de-production)
7. [Statut de tâche (MES)](#statut-de-tâche-mes)
8. [KPI & Dashboard](#kpi--dashboard)
9. [Consommation énergie](#consommation-énergie)
10. [Export commandes](#export-commandes)
11. [Inspection qualité](#inspection-qualité)
12. [Collaboration — Tableaux blancs](#collaboration--tableaux-blancs)
13. [Intégration Qonto](#intégration-qonto)
14. [Objets de réponse](#objets-de-réponse)

---

## Authentification

Toutes les routes (sauf exceptions) requièrent le middleware `auth:api`.

```http
Authorization: Bearer {token}
```

Les routes publiques (sans token) sont :
- `GET /api/integrations/qonto/callback`
- `POST /api/integrations/qonto/webhook/invoice`

---

## Clients

### `GET /api/clients`

Retourne les clients actifs (`statu_customer = 2`) avec leur nom et leurs adresses.  
Utile pour les selects React (devis, commandes, livraisons).

**Query params**

| Param    | Type   | Requis | Description                          |
|----------|--------|--------|--------------------------------------|
| `search` | string | Non    | Filtre par `label` ou `code` (LIKE)  |

**Réponse `200`**

```json
[
  {
    "id": 12,
    "code": "CLI001",
    "label": "Acier Dupont SA",
    "addresses": [
      {
        "id": 5,
        "label": "Siège",
        "adress": "12 rue de la Forge",
        "zipcode": "57000",
        "city": "Metz",
        "country": "France",
        "default": true
      }
    ]
  }
]
```

---

## Companies

### `GET /api/companies`

Liste paginée de toutes les companies (clients + fournisseurs), avec contacts et adresses.

**Pagination** : 10 par page  
**Query params** : `?page=N`

**Réponse `200`** → collection de [CompanieResource](#companieresource)

---

### `GET /api/companies/{id}`

Détail d'une company.

**Réponse `200`** → [CompanieResource](#companieresource)

---

### `POST /api/companies`

Crée une nouvelle company.

**Body JSON**

| Champ                  | Type    | Requis | Règles                         |
|------------------------|---------|--------|--------------------------------|
| `code`                 | string  | Oui    | unique, max 50                 |
| `label`                | string  | Oui    | max 255                        |
| `siren`                | string  | Non    | max 14                         |
| `naf_code`             | string  | Non    | max 10                         |
| `intra_community_vat`  | string  | Non    | max 20                         |
| `website`              | string  | Non    | URL valide, max 255            |
| `longitude`            | string  | Non    | max 50                         |
| `latitude`             | string  | Non    | max 50                         |
| `user_id`              | integer | Non    | doit exister dans `users`      |

**Réponse `201`** → [CompanieResource](#companieresource)

---

## Devis

### `GET /api/quote`

Liste paginée des devis avec lignes, company, contact, adresse.

**Pagination** : 10 par page

**Réponse `200`** → collection de [QuoteResource](#quoteresource)

---

### `GET /api/quote/{id}`

Détail d'un devis.

**Réponse `200`** → [QuoteResource](#quoteresource)

---

## Commandes

### `GET /api/order`

Liste paginée des commandes avec lignes, company, contact, adresse.

**Pagination** : 10 par page

**Réponse `200`** → collection de [OrderResource](#orderresource)

---

### `GET /api/order/{id}`

Détail d'une commande.

**Réponse `200`** → [OrderResource](#orderresource)

---

## Tâches de production

### `GET /api/tasks`

Liste de toutes les tâches.

**Réponse `200`** → collection de [TaskResource](#taskresource)

---

### `GET /api/tasks/{id}`

Détail d'une tâche.

**Réponse `200`** → [TaskResource](#taskresource)

---

## Statut de tâche (MES)

Routes utilisées par les terminaux de production (tablettes/bornes MES).

### `GET /api/production/Task/Statu/Api/{id}`

Détail complet d'une tâche avec tout le contexte MES.

**Réponse `200`**

```json
{
  "id": 42,
  "label": "Découpe laser",
  "ordre": 1,
  "type": "machining",
  "status_id": 2,
  "methods_services_id": 3,
  "order_lines_id": 15,
  "component_id": 7,
  "seting_time": 30,
  "unit_time": 45,
  "qty": 10,
  "end_date": "2026-05-10",
  "not_recalculate": false,
  "total_log_time": 120,
  "total_log_good_qt": 8,
  "total_log_bad_qt": 1,
  "total_net_good_qt": 8,
  "trs": 85.5,
  "progress": 80,
  "formatted_end_date": "10/05/2026",
  "formatted_unit_cost": "12,50 €",
  "formatted_unit_price": "18,00 €",
  "margin": 44.0,
  "total_time": 450,
  "order_qty": 10,
  "status": { "id": 2, "title": "En cours" },
  "service": { "id": 3, "label": "Laser", "type": "machine", "picture": "...", "color": "#e74c3c" },
  "resources": [{ "id": 1, "label": "Laser 3015", "picture": "...", "userforced_ressource": false }],
  "order_lines": { "id": 15, "orders_id": 5, "label": "Pièce A", "qty": 10, "order": 1, "picture": "...", "product": {} },
  "component": { "id": 7, "code": "COMP-001", "label": "Tôle acier 3mm", "drawing_file": "..." },
  "previous_task": 41,
  "next_task": 43,
  "last_activity_type": "start",
  "service_resources": [],
  "selected_resource_id": 1,
  "userforced_resource": false,
  "stock_locations": [],
  "timeline": []
}
```

---

### `POST /api/production/Task/Statu/Api/{id}/start`

Démarre la tâche (enregistre l'activité, passe le statut à "En cours").

**Réponse `200`** → `{ "success": true }`

---

### `POST /api/production/Task/Statu/Api/{id}/pause`

Met la tâche en pause.

**Réponse `200`** → `{ "success": true }`

---

### `POST /api/production/Task/Statu/Api/{id}/finish`

Termine la tâche (statut → "Terminé").

**Réponse `200`** → `{ "success": true }`

---

### `POST /api/production/Task/Statu/Api/{id}/good-qty`

Déclare une quantité bonne.

**Body JSON**

| Champ | Type    | Requis | Règles  |
|-------|---------|--------|---------|
| `qty` | numeric | Oui    | min : 0 |

**Réponse `200`** → `{ "success": true }`

---

### `POST /api/production/Task/Statu/Api/{id}/good-qty-stock`

Déclare une quantité bonne et crée les mouvements de stock.

**Body JSON**

| Champ          | Type    | Requis |
|----------------|---------|--------|
| `qty`          | numeric | Oui    |
| `component_id` | integer | Oui    |

**Réponse `200`** → `{ "success": true, "negative_stock": false }`

---

### `POST /api/production/Task/Statu/Api/{id}/bad-qty`

Déclare une quantité mauvaise (rebut).

**Body JSON**

| Champ | Type    | Requis | Règles  |
|-------|---------|--------|---------|
| `qty` | numeric | Oui    | min : 0 |

**Réponse `200`** → `{ "success": true }`

---

### `PUT /api/production/Task/Statu/Api/{id}/date`

Met à jour la date de fin prévue.

**Body JSON**

| Champ              | Type    | Requis |
|--------------------|---------|--------|
| `end_date`         | date    | Oui    |
| `not_recalculate`  | boolean | Non    |

**Réponse `200`** → `{ "success": true }`

---

### `PUT /api/production/Task/Statu/Api/{id}/resource`

Assigne une ressource (opérateur/machine) à la tâche.

**Body JSON**

| Champ         | Type    | Requis | Règles                           |
|---------------|---------|--------|----------------------------------|
| `resource_id` | integer | Oui    | doit exister dans `methods_ressources` |

**Réponse `200`** → `{ "success": true }`

---

### `POST /api/production/Task/Statu/Api/{id}/nc`

Crée une non-conformité liée à la tâche.

**Réponse `200`** → `{ "success": true, "redirect_url": "/fr/non-conformities/42" }`

---

## KPI & Dashboard

> Middleware : `auth`, `verified`, `has.role`, `check.factory`

### `GET /api/kpi/recent/orders`

| Param   | Type    | Défaut | Max |
|---------|---------|--------|-----|
| `limit` | integer | 5      | 20  |

**Réponse `200`**
```json
[{ "id": 1, "code": "CMD-001", "statu": 2, "type": "standard", "companies_id": 12, "companie_label": "Acier Dupont", "formatted_total_price": "1 250,00 €", "validity_date": "2026-05-01" }]
```

---

### `GET /api/kpi/recent/quotes`

| Param   | Type    | Défaut | Max |
|---------|---------|--------|-----|
| `limit` | integer | 5      | 20  |

**Réponse `200`** — même structure que `recent/orders` avec `created_at_human` à la place de `validity_date`.

---

### `GET /api/kpi/recent/invoices`

| Param   | Type    | Défaut | Max |
|---------|---------|--------|-----|
| `limit` | integer | 8      | 20  |

---

### `GET /api/kpi/recent/deliveries`

| Param   | Type    | Défaut | Max |
|---------|---------|--------|-----|
| `limit` | integer | 8      | 20  |

---

### `GET /api/kpi/recent/purchases`

> Permission requise : `purchases-menu`

| Param   | Type    | Défaut | Max |
|---------|---------|--------|-----|
| `limit` | integer | 8      | 20  |

Retourne les achats en attente (statuts 1, 2 ou 3).

---

### `GET /api/kpi/delivery/board`

Tableau des livraisons à venir et en retard.

**Réponse `200`**
```json
{
  "incoming": [{ "orders_id": 5, "delivery_date": "2026-05-03", "order": { "id": 5, "code": "CMD-005" } }],
  "incoming_more": 0,
  "late": [],
  "late_more": 0
}
```

---

### `GET /api/kpi/quotes/rate`

Taux de transformation des devis par statut.

| Param        | Type    | Défaut         |
|--------------|---------|----------------|
| `year`       | integer | Année courante |
| `company_id` | integer | —              |

**Réponse `200`**
```json
{ "year": 2026, "data": [{ "statu": 2, "QuoteCountRate": 42 }] }
```

---

### `GET /api/kpi/orders/monthly`

Chiffres mensuels commandes / livraisons / factures / achats.

| Param  | Type    | Défaut             |
|--------|---------|--------------------|
| `year` | integer | Exercice en cours  |

**Réponse `200`**
```json
{
  "year": 2026,
  "fiscalYearStartMonth": 1,
  "orders": [{ "month": 1, "orderSum": 15000.0 }],
  "deliveries": [...],
  "invoices": [...],
  "purchases": [...],
  "target": { "amount1": 20000, "amount2": 20000, "...": "..." }
}
```

> `purchases` et `target` sont `null` si l'utilisateur n'a pas la permission `purchases-menu`.

---

### `GET /api/kpi/top-clients`

Top 5 clients par chiffre d'affaires (tous temps).

**Réponse `200`**
```json
[{ "companies_id": 12, "total_amount": 85000.0, "companie": { "label": "Acier Dupont SA" } }]
```

---

### `GET /api/kpi/nc-stats`

Statistiques qualité (non-conformités).

**Réponse `200`**
```json
{
  "internalNonConformityRate": 2.3,
  "externalNonConformityRate": 0.8
}
```

---

### `GET /api/kpi/supplier-delays`

Délai moyen de réception par fournisseur, trié par délai croissant.

> Permission requise : `purchases-menu`

---

### `GET /api/kpi/otd`

On-Time Delivery — taux de livraisons à l'heure.

**Réponse `200`**
```json
{ "rate": 91.5, "onTime": 183, "total": 200 }
```

---

### `GET /api/kpi/mood`

Humeur de l'équipe pour aujourd'hui.

**Réponse `200`**
```json
{
  "myMood": "happy",
  "date": "2026-04-27",
  "team": [{ "user_id": 3, "name": "Jean Martin", "mood": "neutral" }],
  "counts": { "happy": 3, "neutral": 1, "sad": 0 }
}
```

---

### `POST /api/kpi/mood`

Enregistre ou met à jour l'humeur de l'utilisateur connecté pour aujourd'hui.

**Body JSON**

| Champ  | Type   | Valeurs autorisées             |
|--------|--------|-------------------------------|
| `mood` | string | `happy`, `neutral`, `sad`     |

**Réponse `200`** → même structure que `GET /api/kpi/mood`

---

## Consommation énergie

### `GET /api/energy-consumptions`

Liste des consommations avec la ressource associée.

---

### `POST /api/energy-consumptions`

**Body JSON**

| Champ                  | Type    | Requis | Règles                              |
|------------------------|---------|--------|-------------------------------------|
| `methods_ressource_id` | integer | Oui    | doit exister dans `methods_ressources` |
| `kwh`                  | numeric | Oui    |                                     |
| `cost_per_kwh`         | numeric | Oui    |                                     |

**Réponse `201`** → objet créé avec relation `methodsRessource`

---

## Export commandes

### `GET /api/exports/sales-orders`

Export des commandes en JSON pour intégration comptable ou ERP externe.

**Query params**

| Param             | Type    | Requis | Description                             |
|-------------------|---------|--------|-----------------------------------------|
| `from`            | date    | Non    | Date de début                           |
| `to`              | date    | Non    | Date de fin (≥ `from`)                  |
| `status`          | integer | Non    | Filtre par statut                       |
| `date_format`     | string  | Non    | Format des dates (défaut `Y-m-d`)       |
| `datetime_format` | string  | Non    | Format des datetimes (défaut `Y-m-d H:i:s`) |
| `include_lines`   | boolean | Non    | Inclure les lignes de commande          |

**Réponse `200`**
```json
{
  "data": [ { "...": "..." } ],
  "meta": { "count": 42 }
}
```

---

## Inspection qualité

> Ces routes utilisent le middleware `auth:api`.

### `GET /api/inspection-projects/{id}`

Détail d'un projet d'inspection.

### `GET /api/inspection-sessions/{id}`

Détail d'une session de mesure.

### `POST /api/inspection-measures`

Crée une mesure.

### `PUT /api/inspection-measures/{id}`

Met à jour une mesure.

### `POST /api/inspection-nonconformities`

Crée une non-conformité liée à une inspection.

---

## Collaboration — Tableaux blancs

### `GET /api/collaboration/whiteboards`

Liste de tous les tableaux blancs (plus récent en premier).

---

### `POST /api/collaboration/whiteboards`

Crée un tableau blanc.

**Body JSON**

| Champ   | Type   | Requis | Règles        |
|---------|--------|--------|---------------|
| `title` | string | Non    | max 255, défaut : "Nouveau tableau" |
| `state` | any    | Non    | JSON libre    |

**Réponse `201`** → tableau blanc avec relations `snapshots` et `files`

---

### `GET /api/collaboration/whiteboards/{id}`

Détail avec snapshots et fichiers.

---

### `PUT /api/collaboration/whiteboards/{id}`

Met à jour le titre ou l'état (canvas).

**Body JSON** — mêmes champs que `POST`

---

### `GET /api/collaboration/whiteboards/{id}/snapshots`

Liste des snapshots (plus récent en premier).

---

### `POST /api/collaboration/whiteboards/{id}/snapshots`

Crée un snapshot.

**Body JSON**

| Champ   | Type | Requis |
|---------|------|--------|
| `state` | any  | Oui    |

**Réponse `201`**

---

### `GET /api/collaboration/whiteboards/{id}/files`

Liste des fichiers attachés.

---

### `POST /api/collaboration/whiteboards/{id}/files`

Upload de fichiers (multipart/form-data).

**Form-data**

| Champ     | Type  | Règles                   |
|-----------|-------|--------------------------|
| `files[]` | file  | max 10 Mo par fichier    |

**Réponse `201`** — tableau d'objets fichiers :
```json
[{ "original_name": "plan.pdf", "path": "whiteboards/...", "mime_type": "application/pdf", "size": 204800, "uploaded_by": 3 }]
```

---

## Intégration Qonto

### `GET /api/integrations/qonto/status`

État de la connexion Qonto.

**Réponse `200`**
```json
{
  "feature_enabled": true,
  "connected": true,
  "last_sync_at": "2026-04-26T14:30:00Z",
  "import_bidirectionnel": false,
  "scope": "openid offline_access ..."
}
```

---

### `GET /api/integrations/qonto/connect`

Lance le flux OAuth Qonto.

**Réponse `200`**
```json
{ "authorization_url": "https://oauth.qonto.com/...", "state": "abc123" }
```

---

### `GET /api/integrations/qonto/callback` _(public)_

Callback OAuth. Reçoit `code` + `state` en query params. Redirige vers les paramètres d'intégration.

---

### `POST /api/integrations/qonto/clients/sync`

Synchronise les clients WEM → Qonto (crée ceux absents dans Qonto).

**Body JSON (optionnel)**

| Champ                   | Type    |
|-------------------------|---------|
| `import_bidirectionnel` | boolean |

---

### `POST /api/integrations/qonto/clients/reconcile`

Réconcilie sans créer — identifie les doublons et conflits.

---

### `POST /api/integrations/qonto/clients/{wemClientId}/push`

Pousse un client individuel vers Qonto.

**Réponse `200`** → `{ "mapping": { "...": "..." } }`

---

### `POST /api/integrations/qonto/clients/{reviewId}/resolve`

Résout un conflit de synchronisation.

**Body JSON**

| Champ             | Type   | Requis | Valeurs         |
|-------------------|--------|--------|-----------------|
| `action`          | string | Oui    | `link`, `ignore` |
| `qonto_client_id` | string | Non    | requis si `action = link` |

---

### `POST /api/integrations/qonto/settings`

Met à jour les paramètres de l'intégration.

**Body JSON**

| Champ                   | Type    | Requis |
|-------------------------|---------|--------|
| `import_bidirectionnel` | boolean | Oui    |

---

### `POST /api/integrations/qonto/disconnect`

Déconnecte et supprime les tokens Qonto.

**Réponse `200`** → `{ "disconnected": true }`

---

### `POST /api/integrations/qonto/invoices/{invoiceId}/submit`

Soumet une facture à Qonto.

> Uniquement pour les factures de type 1 (pas les avoirs). Erreur `422` sinon.

---

### `POST /api/integrations/qonto/invoices/{invoiceId}/poll`

Interroge Qonto pour le statut mis à jour d'une facture.

---

### `POST /api/integrations/qonto/webhook/invoice` _(public — HMAC vérifié)_

Reçoit les mises à jour de statut de facture depuis Qonto.

---

## Objets de réponse

### `CompanieResource`

```json
{
  "id": 12,
  "name": null,
  "code": "CLI001",
  "label": "Acier Dupont SA",
  "siren": "123456789",
  "naf_code": "2511Z",
  "intra_community_vat": "FR12123456789",
  "website": "https://example.com",
  "longitude": "6.1757",
  "latitude": "49.1193",
  "created_at": "01/01/2026",
  "contacts": [{ "id": 3, "first_name": "Jean", "name": "Martin", "mail": "j.martin@example.com", "number": "0387000000" }],
  "addresses": [{ "id": 5, "adress": "12 rue de la Forge", "zipcode": "57000", "city": "Metz", "province": null, "country": "France" }]
}
```

### `ContactResource`

```json
{ "civility": "M.", "first_name": "Jean", "name": "Martin", "number": "0387000000", "mobile": "0612345678", "mail": "j.martin@example.com" }
```

### `AdresseResource`

```json
{ "adress": "12 rue de la Forge", "zipcode": "57000", "city": "Metz", "province": null, "country": "France", "number": "0387000000", "mail": "contact@example.com" }
```

### `QuoteResource` / `OrderResource`

```json
{
  "id": 7,
  "uuid": "550e8400-...",
  "code": "DEV-2026-007",
  "label": "Châssis acier",
  "customer_reference": "REF-CLIENT-42",
  "companies_id": { "...": "CompanieResource" },
  "companies_contacts_id": { "...": "ContactResource" },
  "companies_addresses_id": { "...": "AdresseResource" },
  "accounting_payment_conditions_id": { "label": "30 jours net" },
  "accounting_payment_methods_id": { "label": "Virement" },
  "accounting_deliveries_id": { "label": "Franco" },
  "validity_date": "2026-05-31",
  "statu": 1,
  "comment": "Urgent",
  "created_at": "01/04/2026",
  "updated_at": "15/04/2026",
  "quote_lines": [],
  "order_lines": []
}
```

### `TaskResource`

```json
{
  "id": 42, "name": "OP10", "label": "Découpe laser",
  "ordre": 1, "quote_lines_id": null, "order_lines_id": 15,
  "products_id": 7, "sub_assembly_id": null,
  "methods_services_id": 3,
  "setting_time": 30, "unit_time": 45, "remaining_time": 90,
  "status_id": 2, "type": "machining", "delay": 0,
  "qty": 10, "qty_init": 10, "qty_aviable": 10,
  "unit_cost": 12.5, "unit_price": 18.0, "methods_units_id": 1,
  "x_size": 500, "y_size": 300, "z_size": 3,
  "x_oversize": 10, "y_oversize": 10, "z_oversize": 0,
  "diameter": null, "diameter_oversize": null,
  "to_schedule": true, "end_date": "2026-05-10", "not_recalculate": false,
  "material": "Acier S235", "thickness": 3.0, "weight": 4.5
}
```

---

*Dernière mise à jour : 2026-04-27*
