# Intégration Qonto

## Vue d'ensemble

L'intégration Qonto couvre deux périmètres :
1. **Synchronisation des clients** — mise en correspondance des tiers WEM ↔ Qonto
2. **Facturation électronique** — dépôt des factures au format Factur-X/EN16931 et suivi du cycle de vie, en conformité avec la réforme de facturation électronique obligatoire (sept. 2026 grandes entreprises / 2027 PME)

**Sens de la synchronisation clients :**
- **WEM → Qonto** : les clients WEM sont créés/liés dans Qonto (toujours actif)
- **Qonto → WEM** : les tiers Qonto non trouvés dans WEM créent des entreprises/contacts (optionnel, toggle)

**Sens de la synchronisation factures :**
- **WEM → Qonto** : dépôt du PDF Factur-X via l'API (manuel depuis la fiche facture)
- **Qonto → WEM** : mise à jour automatique du statut WEM via webhook

---

## Configuration

### Variables d'environnement

```env
QONTO_CLIENT_ID=your_client_id
QONTO_CLIENT_SECRET=your_client_secret
QONTO_OAUTH_BASE_URL=https://oauth.qonto.com        # défaut
QONTO_API_BASE_URL=https://thirdparty.qonto.com/v2  # défaut
QONTO_WEBHOOK_SECRET=your_webhook_secret             # HMAC-SHA256 signé par Qonto
```

Les variables `QONTO_CLIENT_ID` et `QONTO_CLIENT_SECRET` sont obligatoires pour activer l'intégration. Si elles sont absentes :
- L'interface affiche "Intégration désactivée"
- Le bloc Qonto n'apparaît pas sur la fiche facture
- La colonne statut Qonto n'apparaît pas dans la liste des factures

`QONTO_WEBHOOK_SECRET` est optionnel en dev/test mais **obligatoire en production** pour valider la signature des webhooks entrants.

### Scopes OAuth requis

```
offline_access  client.read  client.write
```

---

## Architecture

### Fichiers principaux

| Fichier | Rôle |
|---|---|
| `app/Http/Controllers/Integrations/QontoSettingsController.php` | Contrôleur web (UI admin) |
| `app/Http/Controllers/Api/Integrations/QontoIntegrationController.php` | Contrôleur API REST (clients + factures) |
| `app/Http/Controllers/Integrations/QontoWebhookController.php` | Réception des webhooks Qonto |
| `app/Services/Integrations/QontoConnectionService.php` | Gestion centralisée du token OAuth |
| `app/Services/Integrations/QontoClientSyncService.php` | Logique de réconciliation clients |
| `app/Services/Integrations/QontoInvoiceSyncService.php` | Dépôt factures + application des cycles de vie |
| `app/Models/Integrations/QontoConnection.php` | Connexion OAuth par tenant |
| `app/Models/Integrations/QontoClientMapping.php` | Correspondances WEM ↔ Qonto (clients) |
| `app/Models/Integrations/QontoSyncReview.php` | Matchs ambigus en attente de révision |
| `app/Models/Integrations/QontoInvoiceMapping.php` | Suivi lifecycle des factures soumises |
| `resources/views/integrations/qonto-settings.blade.php` | Page de surveillance clients |
| `resources/views/integrations/partials/qonto-invoice-card.blade.php` | Bloc Factur-X sur fiche facture |
| `config/services.php` | Clé `qonto` |

### Base de données

#### `qonto_connections`
Stocke la connexion OAuth par tenant.

| Colonne | Type | Description |
|---|---|---|
| `tenant_id` | integer | ID utilisateur (user.id) |
| `access_token` | text | Token chiffré (Crypt) |
| `refresh_token` | text | Refresh token chiffré (Crypt) |
| `access_token_expires_at` | datetime | Expiration du token |
| `scope` | string | Scopes accordés |
| `import_bidirectionnel` | boolean | Import Qonto → WEM activé |
| `last_sync_at` | datetime | Dernière synchronisation |

#### `qonto_client_mappings`
Correspondances entre entreprises WEM et tiers Qonto.

| Colonne | Type | Description |
|---|---|---|
| `tenant_id` | integer | ID tenant |
| `wem_client_id` | integer | `companies.id` (pas `customers.id`) |
| `qonto_client_id` | string | ID tiers côté Qonto |
| `sync_status` | enum | Voir statuts ci-dessous |
| `matching_score` | integer | Score de correspondance (0–100) |
| `last_sync_at` | datetime | Date du dernier sync |
| `error_message` | text | Erreur éventuelle |

**Statuts `sync_status` :**

| Valeur | Signification |
|---|---|
| `linked` | Correspondance automatique confirmée |
| `review_required` | Match ambigu, révision manuelle requise |
| `created_in_qonto` | Client créé dans Qonto (introuvable côté Qonto) |
| `imported_from_qonto` | Tiers Qonto importé dans WEM |

#### `qonto_sync_reviews`
File d'attente de révision manuelle pour les matchs ambigus.

| Colonne | Type | Description |
|---|---|---|
| `tenant_id` | integer | ID tenant |
| `wem_client_id` | integer | `companies.id` |
| `qonto_client_id` | string | Meilleur candidat Qonto |
| `matching_score` | integer | Score du meilleur candidat |
| `candidate_payload` | json | Détail des candidats (max 3) |
| `status` | enum | `pending`, `resolved`, `ignored` |
| `resolved_at` | datetime | Date de résolution |
| `resolved_by` | integer | ID utilisateur ayant résolu |

---

## Flow OAuth 2.0

```
1. Admin clique "Connecter Qonto"
   → GET /admin/integrations/qonto/connect
   → Génère un state aléatoire (40 chars)
   → Stocke dans le cache : "qonto.oauth.state.{state}" → tenant_id (TTL 10 min)
   → Redirige vers https://oauth.qonto.com/oauth2/auth?...

2. Qonto redirige le navigateur vers :
   GET /integrations/qonto/callback?code=...&state=...
   → Vérifie state dans le cache (pull = consommé une seule fois)
   → Récupère tenant_id depuis le cache
   → Échange le code contre les tokens (POST /oauth2/token)
   → Chiffre et stocke les tokens dans qonto_connections
   → Redirige vers /admin/integrations/qonto avec message de succès

3. Refresh automatique
   → Avant chaque appel API, vérifie access_token_expires_at
   → Si expiré : POST /oauth2/token avec grant_type=refresh_token
   → Met à jour les tokens en base
```

> **Important** : la route callback (`/integrations/qonto/callback`) est **sans authentification** car c'est une redirection navigateur depuis Qonto. Le tenant est identifié via le paramètre `state` (stocké en cache avant la redirection).

---

## Algorithme de synchronisation

### Étape 1 — Réconciliation (`QontoClientSyncService::reconcile`)

Pour chaque entreprise WEM :

1. Récupère les tiers Qonto non encore mappés comme candidats
2. Calcule un score de correspondance pour chaque candidat
3. Applique les règles de décision :

```
score = 0

Règles (chaque règle écrase si supérieure) :
  SIREN/SIRET/TVA identique   → score = 100  (match certain)
  Email identique             → score = 85   (très probable)
  Nom identique + code postal → score = 75   (probable)
  Nom identique + ville       → score = 65   (plausible)

Décision finale :
  Aucun candidat              → no_match         → sera créé dans Qonto
  score < 60                  → review_required  → révision manuelle
  2 candidats et écart < 15   → review_required  → révision manuelle
  score ≥ 60 et sans ambiguïté → linked          → mappé automatiquement
```

### Étape 2 — Création dans Qonto (`sync()`)

Après réconciliation, pour chaque entreprise WEM sans mapping (`no_match`) :
- POST `/clients` sur l'API Qonto
- Champ `registration_number` = SIREN (pas `siren`)
- Enregistre le mapping avec `sync_status = created_in_qonto`

### Étape 3 — Import bidirectionnel (optionnel)

Si `import_bidirectionnel = true`, pour chaque tiers Qonto non mappé :
- Crée une `Companies` + un `Customer` dans WEM
- `wem_client_id` dans le mapping = `company->id`

---

## Pagination Qonto

L'API Qonto est paginée. Le fetch boucle sur `meta.next_page` :

```php
do {
    $response = Http::withToken($token)
        ->get("{$baseUrl}/clients", ['page' => $nextPage, 'per_page' => 100])
        ->json();
    // merge clients...
    $nextPage = $response['meta']['next_page'] ?? null;
} while ($nextPage !== null);
```

---

## Interface d'administration

URL : `/admin/integrations/qonto`  
Route nommée : `admin.integrations.qonto.index`

### Blocs affichés

| Bloc | Condition d'affichage |
|---|---|
| Alerte "intégration désactivée" | `QONTO_CLIENT_ID` ou `QONTO_CLIENT_SECRET` absent |
| Statut connexion + bouton connecter/déconnecter | Toujours |
| 4 compteurs (liés, à réviser, créés, importés) | Si connecté |
| Bouton "Synchroniser maintenant" | Si connecté |
| Toggle import bidirectionnel | Si connecté |
| Tableau des revues en attente | Si connecté **et** revues en attente |

### Actions disponibles

| Action | Route | Méthode |
|---|---|---|
| Connecter | `admin.integrations.qonto.connect` | GET |
| Callback OAuth | `admin.integrations.qonto.callback` | GET (public) |
| Synchroniser | `admin.integrations.qonto.sync` | POST |
| Sauvegarder paramètres | `admin.integrations.qonto.settings` | POST |
| Déconnecter | `admin.integrations.qonto.disconnect` | POST |
| Résoudre une revue | `admin.integrations.qonto.resolve` | POST |

---

## API REST

Base : `/api/integrations/qonto/`  
Auth : `auth:api` (token bearer)

| Endpoint | Méthode | Description |
|---|---|---|
| `/status` | GET | Statut de connexion et métadonnées |
| `/connect` | GET | URL d'autorisation OAuth |
| `/callback` | GET | Callback OAuth (sans auth) |
| `/clients/sync` | POST | Sync complète |
| `/clients/reconcile` | POST | Réconciliation seule (sans création) |
| `/clients/{wemClientId}/push` | POST | Pousser un client spécifique |
| `/clients/{reviewId}/resolve` | POST | Résoudre une revue (`action: link|ignore`) |
| `/settings` | POST | Toggle `import_bidirectionnel` |
| `/disconnect` | POST | Supprimer la connexion |

---

## Facturation électronique

### Table `qonto_invoice_mappings`

| Colonne | Type | Description |
|---|---|---|
| `tenant_id` | integer | ID tenant |
| `invoice_id` | integer | `invoices.id` |
| `qonto_invoice_id` | string | ID de la facture côté Qonto après dépôt |
| `lifecycle_status` | string | Statut du cycle de vie (voir ci-dessous) |
| `rejection_reason` | string | Motif si rejeté ou refusé |
| `submitted_at` | datetime | Date de dépôt vers Qonto |
| `acknowledged_at` | datetime | Date d'accusé réception Qonto |

### Cycle de vie et mapping vers WEM

| Lifecycle Qonto | Signification | → `invoices.statu` WEM |
|---|---|---|
| `pending` | Non encore soumise | aucun changement |
| `submitted` | Déposée chez Qonto | 2 (Envoyée) |
| `acknowledged` | Accusé réception Qonto | 2 (Envoyée) |
| `rejected` | Rejetée (format / données) | 1 (En cours) |
| `refused` | Refusée par le destinataire | 4 (Impayée) |
| `accepted` | Acceptée par le destinataire | 3 (En attente) |
| `paid` | Payée côté Qonto | 5 (Payée) |

### Webhook

**URL** : `POST /api/integrations/qonto/webhook/invoice`  
**Auth** : Aucune — signature HMAC-SHA256 vérifiée via `X-Qonto-Signature-256`  
**Secret** : `QONTO_WEBHOOK_SECRET` dans `.env`  

Événements traités : `invoice.submitted`, `invoice.acknowledged`, `invoice.rejected`, `invoice.refused`, `invoice.accepted`, `invoice.paid`

> Si `QONTO_WEBHOOK_SECRET` est absent, la signature n'est pas vérifiée (log warning). À configurer impérativement en production.

### API REST — factures

| Endpoint | Méthode | Description |
|---|---|---|
| `/api/integrations/qonto/invoices/{id}/submit` | POST | Soumet la facture (PDF Factur-X) à Qonto |
| `/api/integrations/qonto/invoices/{id}/poll` | POST | Interroge Qonto pour mettre à jour le statut |

### Interface facture

Le bloc **"Facturation électronique — Qonto"** apparaît sur la fiche facture uniquement si :
- `QONTO_CLIENT_ID` et `QONTO_CLIENT_SECRET` sont configurés
- La facture est de type 1 (Facture, pas avoir / proforma)

Il affiche le statut lifecycle courant, la date de dépôt, le motif de rejet éventuel, et les boutons Soumettre / Actualiser.

### Liste des factures

La colonne **"Qonto"** apparaît dans la liste des factures uniquement si l'intégration est activée (réponse `qonto_enabled: true` de l'API). Elle affiche le badge lifecycle de chaque facture.

---

## Points d'attention

### Cohérence `wem_client_id`
Le champ `wem_client_id` dans `qonto_client_mappings` et `qonto_sync_reviews` référence **`companies.id`**, pas `customers.id`. Toute modification du code de sync doit respecter cette convention.

### Champ `registration_number`
L'API Qonto Partner v2 utilise `registration_number` pour le SIREN. Le champ `siren` n'est pas reconnu. Le scoring côté WEM accepte les deux variantes pour compatibilité.

### Données existantes
Si des mappings ont été créés avant la correction (`wem_client_id` = `customer->id`), une migration manuelle est nécessaire pour les convertir en `company->id`.

---

## Tests

Fichier : `tests/Feature/QontoIntegrationApiTest.php`

| Test | Ce qu'il vérifie |
|---|---|
| `test_status_reports_feature_disabled_when_missing_credentials` | `feature_enabled = false` sans credentials |
| `test_status_reports_connection_metadata_when_connected` | Métadonnées retournées si connecté |
| `test_connect_returns_authorization_url_and_state` | URL OAuth + state en cache (`qonto.oauth.state.{state}`) |
| `test_sync_creates_review_when_matching_is_ambiguous` | Match ambigu → `review_required` en base |
| `test_settings_persist_bidirectional_import_flag` | Toggle bidirectionnel persisté |
| `test_resolve_links_review_to_mapping` | Action `link` crée le mapping |
