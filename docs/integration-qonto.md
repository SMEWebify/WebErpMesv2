# Intégration Qonto

## Vue d'ensemble

L'intégration Qonto couvre deux périmètres :
1. **Synchronisation des clients** — mise en correspondance des tiers WEM ↔ Qonto
2. **Facturation commerciale** — dépôt des factures WEM dans Qonto (`/v2/client_invoices`) et suivi de leur statut

> **Périmètre — à lire avant toute évolution.**
> Cette intégration s'appuie sur la **Business API** de Qonto, c'est-à-dire son outil de
> facturation commerciale. Ce n'est **pas** le canal Plateforme Agréée (ex-PDP) : elle ne
> réalise donc pas, en l'état, l'émission conforme au sens de la réforme (réception
> obligatoire au 1er sept. 2026, émission PME 2027).
>
> Qonto est bien immatriculé Plateforme Agréée, mais son API de dépôt PDP n'est pas
> documentée publiquement. Le champ `einvoicing_status` renvoyé par `/client_invoices`
> est le point de raccordement naturel le jour où ce canal sera ouvert aux éditeurs :
> il est conservé dans la réponse brute et journalisé, mais volontairement **non
> interprété** par le driver.

**Sens de la synchronisation clients :**
- **WEM → Qonto** : les clients WEM sont créés/liés dans Qonto (toujours actif)
- **Qonto → WEM** : les tiers Qonto non trouvés dans WEM créent des entreprises/contacts (optionnel, toggle)

**Sens de la synchronisation factures :**
- **WEM → Qonto** : dépôt des **données structurées** de la facture (manuel depuis la fiche facture)
- **Qonto → WEM** : mise à jour du statut WEM par polling (« Actualiser le statut »)

### Qui produit quoi

WEM reste la source de vérité : numérotation, prix, remises, TVA, écritures comptables et
export FEC. On envoie à Qonto le **JSON structuré** de la facture, et c'est Qonto qui génère
le document. Notre PDF Factur-X (`PrintController::getInvoiceFactureX`) reste utilisé pour
l'archivage et l'envoi direct au client, mais n'est **pas** transmis à Qonto : envoyer un PDF
ferait reposer la conformité du document sur nous plutôt que sur la plateforme.

Corollaire : un bon de livraison n'est jamais envoyé à Qonto. Une facture WEM agrège des
lignes issues de plusieurs BL (`invoice_lines.delivery_line_id`), et couvre aussi les cas
sans BL (acomptes, prestations, avoirs).

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

`QONTO_WEBHOOK_SECRET` est le secret que **nous** choisissons et transmettons à Qonto lors de
l'abonnement (`POST /data_api/webhooks`). Voir la section Webhook pour la limite actuelle.

### Scopes OAuth requis

```
offline_access  organization.read  client.read  client.write  client_invoices.read  client_invoice.write
```

Définis une seule fois dans `QontoIntegrationController::OAUTH_SCOPES`, réutilisés par le
contrôleur web. Le pluriel de `client_invoices.read` face au singulier de
`client_invoice.write` n'est pas une coquille : ce sont les noms exacts des scopes Qonto.

`organization.read` sert à découvrir l'IBAN du compte (`GET /v2/organization`), obligatoire
dans `payment_methods.iban` à la création d'une facture. Il est résolu une fois puis mis en
cache chiffré dans `qonto_connections.iban`.

> ⚠️ **Les connexions établies avant cette liste de scopes doivent être refaites.** Un token
> obtenu avec les anciens scopes (`offline_access client.read client.write`) sera refusé en
> 403 sur `/client_invoices` et `/organization` : le refresh ne réélargit pas les droits.
> Déconnecter puis reconnecter le compte depuis `/admin/integrations/qonto`.

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
| `app/Services/Integrations/Pdp/Contracts/PdpGateway.php` | Contrat PDP (driver) — agnostique fournisseur |
| `app/Services/Integrations/Pdp/Drivers/QontoGateway.php` | Driver Qonto : I/O HTTP `/client_invoices`, mapping de statuts, webhooks |
| `app/Services/Integrations/Pdp/PdpInvoiceService.php` | Orchestration agnostique (persistance + cycle de vie) |
| `app/Services/Integrations/Pdp/PdpManager.php` | Registre/résolveur des drivers PDP |
| `app/Services/Integrations/Pdp/Enums/PdpLifecycle.php` | Statuts canoniques + mapping WEM |
| `app/Models/Integrations/QontoConnection.php` | Connexion OAuth par tenant |
| `app/Models/Integrations/QontoClientMapping.php` | Correspondances WEM ↔ Qonto (clients) |
| `app/Models/Integrations/QontoSyncReview.php` | Matchs ambigus en attente de révision |
| `app/Models/Integrations/PdpInvoiceSubmission.php` | Suivi du cycle de vie des factures déposées (toute PDP) |
| `resources/views/integrations/qonto-settings.blade.php` | Page de surveillance clients |
| `resources/views/integrations/partials/qonto-invoice-card.blade.php` | Bloc Factur-X sur fiche facture |
| `config/services.php` | Clés `qonto` + `pdp.default` |

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

## Facturation

### Dépôt d'une facture

`POST /v2/client_invoices`, corps **JSON** (jamais de PDF en multipart).

| Champ Qonto | Source WEM |
|---|---|
| `client_id` | `qonto_client_mappings.qonto_client_id` (obligatoire — dépôt refusé sans mapping) |
| `number` | `invoices.code`, tronqué à 40 caractères |
| `issue_date` | `invoices.created_at` |
| `due_date` | `invoices.due_date` (obligatoire — jamais inventée, erreur explicite si absente) |
| `currency` | `Factory.curency`, défaut `EUR` |
| `status` | `unpaid` — la facture est déjà validée dans WEM, inutile de passer par `draft` puis `POST /finalize` |
| `payment_methods.iban` | `qonto_connections.iban` (découvert via `GET /v2/organization`) |
| `purchase_order` | `invoices.customer_reference` si renseignée |
| `items[]` | `InvoiceCalculatorService::getNormalizedLines()` |

Les lignes viennent du **snapshot de prix figé sur `invoice_lines`** — même source que le PDF
et le Factur-X internes, donc pas de divergence possible entre les deux documents.

| Champ item | Construction |
|---|---|
| `title` | `label` de la ligne (repli sur `code`), tronqué à 40 |
| `description` | `code — label` si différent du titre |
| `quantity` | décimal, 4 chiffres |
| `unit` | code unité (`methods_units`), tronqué à 20 |
| `unit_price.value` | **2 décimales** — Qonto stocke au cent (`unit_price_cents`) |
| `vat_rate` | **ratio** (`0.2000`), alors que WEM stocke un pourcentage (`20`) |
| `discount` | `{type: percentage, value}` — transmise séparément plutôt que fondue dans le prix net, pour que le document Qonto affiche la même chose que le nôtre |

> **Limite connue** : un prix unitaire à plus de 2 décimales (petites pièces facturées au
> millième d'euro) est arrondi au cent lors du dépôt. Le total Qonto peut alors différer de
> quelques centimes du total WEM sur de grandes quantités.

Erreurs traduites en message utilisateur (HTTP 422 sur `/submit`) :

| Situation | Message |
|---|---|
| Pas de connexion Qonto | « Aucune connexion Qonto active… » |
| Entreprise non mappée | « Aucun client Qonto associé à l'entreprise #X… » |
| `due_date` absente | « …n'a pas de date d'échéance… » |
| Facture sans ligne | « …n'a aucune ligne… » |
| 409 Qonto (numéro déjà pris) | « …utilisez Actualiser le statut plutôt qu'un nouveau dépôt » |
| 400/422 Qonto | détail des `errors[].detail` renvoyés par l'API |

### Table `pdp_invoice_submissions`

| Colonne | Type | Description |
|---|---|---|
| `tenant_id` | integer | ID tenant |
| `invoice_id` | integer | `invoices.id` |
| `provider` | string | Driver PDP (ex: `qonto`) |
| `external_id` | string | ID de la facture côté PDP après dépôt |
| `lifecycle_status` | string | Statut du cycle de vie (voir ci-dessous) |
| `rejection_reason` | string | Motif si rejeté ou refusé |
| `submitted_at` | datetime | Date de dépôt vers Qonto |
| `acknowledged_at` | datetime | Date d'accusé réception Qonto |

### Cycle de vie et mapping vers WEM

`PdpLifecycle` est le vocabulaire **canonique**, indépendant de la plateforme. Le driver Qonto
n'alimente aujourd'hui que les quatre premières lignes ; les statuts `acknowledged`, `rejected`,
`refused` et `accepted` sont réservés à un futur canal Plateforme Agréée.

| Statut Qonto | PdpLifecycle | → `invoices.statu` WEM |
|---|---|---|
| `draft` | `pending` | aucun changement |
| `unpaid` | `submitted` | 2 (Envoyée) |
| `paid` | `paid` | 5 (Payée) |
| `canceled` | `canceled` | 1 (En cours) |
| — | `acknowledged` | 2 (Envoyée) |
| — | `rejected` | 1 (En cours) |
| — | `refused` | 4 (Impayée) |
| — | `accepted` | 3 (En attente) |

Un statut Qonto inconnu est journalisé (`warning`) et retombe sur `submitted` sans écraser
la facture WEM de façon silencieuse.

### Webhook

**URL** : `POST /api/integrations/qonto/webhook/invoice`  
**Auth** : Aucune — HMAC-SHA256 **hexadécimal** du corps brut, en-tête `Qonto-SHA256-Signature` (sans préfixe)  
**Secret** : `QONTO_WEBHOOK_SECRET`, à transmettre à Qonto via `POST /data_api/webhooks` (`callback_url` + `secret`)

> **Endpoint inerte à ce jour.** Qonto ne documente des webhooks que pour l'Onboarding API
> (`registrations.*`) ; aucun événement `client_invoice.*` n'est publié. La signature est
> vérifiée correctement et la correspondance d'événements est prête, mais **le mécanisme de
> suivi réellement supporté est le polling**. Aucun abonnement n'est créé automatiquement.

> Si `QONTO_WEBHOOK_SECRET` est absent, la signature n'est pas vérifiée hors production (log
> warning) ; en production le webhook est rejeté.

### API REST — factures

| Endpoint | Méthode | Description |
|---|---|---|
| `/api/integrations/qonto/invoices/{id}/submit` | POST | Crée la facture chez Qonto à partir des données structurées |
| `/api/integrations/qonto/invoices/{id}/poll` | POST | Interroge Qonto pour mettre à jour le statut |

### Interface facture

Le bloc **"Facturation Qonto"** apparaît sur la fiche facture uniquement si :
- `QONTO_CLIENT_ID` et `QONTO_CLIENT_SECRET` sont configurés
- La facture est de type 1 (Facture, pas avoir / proforma)

Il affiche le statut courant, la date de dépôt, le motif d'erreur éventuel, et les boutons
Déposer / Actualiser.

### Liste des factures

La colonne **"Qonto"** apparaît dans la liste des factures uniquement si l'intégration est activée (réponse `pdp_enabled: true` de l'API, statut par facture dans `pdp_status`). Elle affiche le badge lifecycle de chaque facture.

---

## Points d'attention

### Cohérence `wem_client_id`
Le champ `wem_client_id` dans `qonto_client_mappings` et `qonto_sync_reviews` référence **`companies.id`**, pas `customers.id`. Toute modification du code de sync doit respecter cette convention.

### Champ `registration_number`
L'API Qonto Partner v2 utilise `registration_number` pour le SIREN. Le champ `siren` n'est pas reconnu. Le scoring côté WEM accepte les deux variantes pour compatibilité.

### Données existantes
Si des mappings ont été créés avant la correction (`wem_client_id` = `customer->id`), une migration manuelle est nécessaire pour les convertir en `company->id`.

### Chiffrement de `qonto_connections`
`iban` et `organization_slug` sont écrits **en clair** dans le modèle : le cast `encrypted`
fait le chiffrement. Ne pas ajouter de `Crypt::encryptString()` par-dessus.

À l'inverse, `access_token` et `refresh_token` sont écrits déjà chiffrés **en plus** du cast
(double chiffrement, symétrique donc fonctionnel) — c'est incohérent mais volontairement
laissé en l'état pour ne pas casser les connexions existantes. Toute uniformisation impose
une migration de ré-encodage des lignes existantes.

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
| `test_submit_invoice_posts_structured_payload_to_client_invoices` | URL `/client_invoices`, JSON, IBAN, quantité/prix/TVA/remise au format Qonto |
| `test_submit_invoice_fails_cleanly_when_client_is_not_mapped` | 422 explicite, aucun appel HTTP, aucune ligne de suivi créée |
| `test_poll_maps_paid_status_to_wem_paid_status` | `paid` Qonto → `invoices.statu = 5` |

> Les appels Qonto sont mockés (`Http::fake`) : ces tests valident le **format** du payload
> face à la documentation, pas le comportement du service distant. Une validation en sandbox
> (`https://thirdparty-sandbox.staging.qonto.co/v2`) reste nécessaire avant mise en production.
