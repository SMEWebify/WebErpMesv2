# Routes JSON internes — ne pas diffuser

> **Interne, ne pas diffuser.** Ce document liste les routes JSON qui ne font **pas** partie de
> l'API intégrateur (`docs/API.md`) : routes de session consommées par le front, routes du
> groupe `auth:api` inutilisables par jeton, intégrations Qonto / PDP, webhooks prestataires.
> Il cite des middlewares, guards, journaux et classes : rien de tout cela ne doit partir chez un
> tiers.
>
> Dernière mise à jour : 2026-11-27.

## Sommaire

1. [Carte de l'authentification](#1-carte-de-lauthentification)
2. [Routes `auth:api` (guard `token`) : inutilisables aujourd'hui](#2-routes-authapi-guard-token--inutilisables-aujourdhui)
3. [Intégrations Qonto et PDP](#3-intégrations-qonto-et-pdp)
4. [Webhooks prestataires et endpoint générique](#4-webhooks-prestataires-et-endpoint-générique)
5. [Routes de session consommées par le front](#5-routes-de-session-consommées-par-le-front)
6. [Notes sur les routes Sanctum documentées pour les tiers](#6-notes-sur-les-routes-sanctum-documentées-pour-les-tiers)
7. [Ce qui a disparu de l'ancienne `docs/API.md`](#7-ce-qui-a-disparu-de-lancienne-docsapimd)

---

## 1. Carte de l'authentification

| Groupe | Où | Authentification | Utilisable par un tiers |
|---|---|---|---|
| `auth:sanctum` | `routes/api.php:61` | jeton personnel Sanctum (`personal_access_tokens`), créé par `Admin\ApiTokenController::store` avec `['*']` | **oui** (`docs/API.md`) |
| `auth:api` | `routes/api.php:32`, `:90` | guard `token` (`config/auth.php`, driver `token`, colonne `users.api_token`) | **non** : la colonne `api_token` n'existe pas dans `users` (aucune migration ne la crée). Seuls les tests passent, par `actingAs($user, 'api')` (`tests/TestCase.php:70`) |
| sans auth + signature | `routes/api.php:36-58` | HMAC / bearer vérifiés par le contrôleur ou par `integration.inbound` | partenaires déclarés seulement |
| `web` (session + CSRF) | `routes/web.php` | session du navigateur | **non**, même quand l'URL contient `/api/` |

Toutes les routes de `routes/api.php` passent par le groupe `api` du Kernel
(`app/Http/Kernel.php:46` : `throttle:api` + `SubstituteBindings`), limiteur `api` = 60/min
par utilisateur, sinon par IP (`RouteServiceProvider::configureRateLimiting`). Pas
d'`EnsureFrontendRequestsAreStateful` : un cookie de session n'authentifie pas une route
Sanctum.

Les URL de `routes/web.php` sont sous le groupe de locale (`routes/web.php:29`) :
`/{langue}/…` (`/fr/kpi/otd`, `/en/production/Task/Statu/Api/12`…).

---

## 2. Routes `auth:api` (guard `token`) : inutilisables aujourd'hui

Déclarées dans `routes/api.php:90-129`. Un appel avec un jeton Sanctum reçoit `401`.

| Méthode | URL | Contrôleur | Remarque |
|---|---|---|---|
| GET | `/api/user` | closure (`routes/api.php:32`) | |
| GET | `/api/companies` | `Api\CompanyController@index` | `CompanieResource`, pagination 10 |
| GET | `/api/companies/{id}` | `@show` | |
| POST | `/api/companies` | `@store` | `code` (requis, unique, 50), `label` (requis, 255), `siren` (14), `naf_code` (10), `intra_community_vat` (20), `website` (url), `longitude`, `latitude` (50) |
| PUT/PATCH, DELETE | `/api/companies/{id}` | — | déclarées par `apiResource` mais **méthodes absentes** du contrôleur (erreur 500 si un jour authentifiable) |
| GET | `/api/quote`, `/api/quote/{id}` | `Api\QuoteController@index/show` | `QuoteResource` ; la liste ne charge ni détail ni opérations |
| GET | `/api/order`, `/api/order/{id}` | `Api\OrderController@index/show` | `OrderResource`. `apiResource('order')` déclare aussi POST/PUT/DELETE, **méthodes absentes** |
| GET | `/api/product`, `/api/product/{id}` | `Api\ProductController@index/show` | `ProductResource`, pagination 10 |
| GET | `/api/tasks`, `/api/tasks/{id}` | `Api\TaskController@index/show` | `TaskResource`. POST/PUT/DELETE déclarés, **méthodes absentes** |
| GET, POST | `/api/energy-consumptions` | `Api\EnergyConsumptionController` | POST : `methods_ressource_id` (requis, existe), `kwh`, `cost_per_kwh` (requis, numériques) → 201 |
| GET | `/api/exports/sales-orders` | `Api\ExportSalesOrderController` | `ExportSalesOrderRequest` : `from`, `to` (≥ from), `status`, `date_format` (`Y-m-d`, `d/m/Y`, `m/d/Y`, `d.m.Y`), `datetime_format` (`Y-m-d H:i:s`, `Y-m-d H:i`, `d/m/Y H:i:s`, `d/m/Y H:i`), `include_lines` |
| | `/api/integrations/qonto/*`, `/api/integrations/pdp/*` | voir § 3 | |

Rendre ces lectures accessibles à un tiers suppose de les déplacer sous `auth:sanctum` (et de
retirer les déclarations `apiResource` sans méthode) : décision à prendre, non faite.

---

## 3. Intégrations Qonto et PDP

L'écran d'administration et la fiche facture n'utilisent **pas** les routes `/api/…` : ils
passent par des routes de session.

| Usage réel (session) | Route |
|---|---|
| Écran Qonto | `admin.integrations.qonto.*` (`routes/web.php:1208`, préfixe `/{langue}/admin/integrations/qonto`) : index, connect, sync, settings, disconnect, `reviews/{id}/resolve` |
| Callback OAuth Qonto | `admin.integrations.qonto.callback` (`routes/web.php:45`, sans auth, `state` en cache) — c'est le `redirect_uri` envoyé par `QontoSettingsController` |
| Dépôt / suivi d'une facture sur la PDP active | `invoices.pdp.submit`, `invoices.pdp.poll` (`routes/web.php:570-571`) |
| Annuaire PDP d'une société | `companies.pdp.lookup` (`routes/web.php:245`) |

Doublons sous `auth:api`, donc inutilisables (`routes/api.php:109-128`) :

- `/api/integrations/qonto/` : `status`, `connect`, `clients/sync`, `clients/reconcile`,
  `clients/{wemClientId}/push`, `clients/{reviewId}/resolve`, `settings`, `disconnect`,
  `invoices/{invoiceId}/submit`, `invoices/{invoiceId}/poll` (`QontoIntegrationController`).
- `/api/integrations/pdp/` : `status`, `invoices/{invoiceId}/submit`,
  `invoices/{invoiceId}/poll` (`PdpIntegrationController`, factures types 1 et 4, résolues par
  `auth('api')->id()`).
- `GET /api/integrations/qonto/callback` est hors auth, mais n'est le `redirect_uri` que de
  `QontoIntegrationController::connect` (lui-même sous `auth:api`).

Détails de l'intégration : `docs/integration-qonto.md`.

---

## 4. Webhooks prestataires et endpoint générique

| Méthode | URL | Contrôle | Throttle | Réponses |
|---|---|---|---|---|
| POST | `/api/integrations/qonto/webhook/invoice` | HMAC-SHA256 hex du corps brut, en-tête `Qonto-SHA256-Signature`, secret `QONTO_WEBHOOK_SECRET` (`QontoGateway`) | limiteur `api` seul | 401 signature, 200 acquitté, 500 → Qonto réessaie. Inerte (Qonto ne publie pas d'événement `client_invoice.*`) |
| POST | `/api/integrations/esignature/webhook` | HMAC DocuSign Connect si une clé HMAC est saisie, vérifié par le driver ; l'enveloppe est toujours relue par l'API DocuSign (`ESignatureWebhookController`) | `throttle:120,1` | 404 non configuré, 401 signature, 200 (y compris enveloppe inconnue), 500 → DocuSign réessaie |
| POST | `/api/integrations/{system_code}/inbound` | middleware `integration.inbound` (`VerifyIntegrationInbound`) | `throttle:120,1` | décrit pour les tiers dans `docs/API.md` § 8 |

`VerifyIntegrationInbound` : endpoint `integration_endpoints` (`system_code`, direction
`inbound`) ; `auth_method` `none` | `bearer` | `hmac` | `bearer_hmac` ; en-têtes
`timestamp_header` / `hmac_header` (défauts `X-Timestamp` / `X-Signature-256`, endpoint N2P
seedé avec `X-N2P-Timestamp` / `X-N2P-Signature-256`) ; tolérance `timestamp_tolerance_s`
(300). Rejets journalisés (`Integration inbound rejected`). Dispatch par
`IntegrationDispatcher::dispatchInbound` (dédoublonnage `integration_deliveries.event_id`,
verrou, transaction) vers `config('integrations.handlers')`. Erreurs du dispatch journalisées
dans le canal `n2p` avec un `correlation_id`.

Réglage : écran `admin.integrations.endpoints.*` (`routes/web.php:1147-1155`), carte « Tester
depuis le partenaire (curl) » sur la fiche d'un endpoint entrant. Contrats d'événements :
`docs/pointage-scan.md` (`task.*`), `docs/n2p-integration.md` (`stock.consumed`,
`nest.updated`).

> Écart relevé : la carte curl annonce `200 {"status":"ignored","reason":"event_type_not_subscribed"}`
> pour un `ping`. Le code renvoie `202` avec cette raison quand l'endpoint filtre ses
> événements, et `200` avec `"error": "no_handler"` quand il les accepte tous
> (`IntegrationInboundController::handle`).

---

## 5. Routes de session consommées par le front

Middlewares habituels : `auth`, `verified`, `has.role`, `check.factory` (+ permission du
module). Session et CSRF (`X-CSRF-TOKEN`). Liste non exhaustive : chaque écran React a ses
routes `*.json.*` ; seules celles que l'ancienne doc présentait comme API sont reprises.

### KPI des widgets (`routes/web.php:97-112`, `Api\KpiController`)

Préfixe `/{langue}/kpi`, middlewares `auth`, `verified`, `has.role`, `check.factory`.

| Méthode | URL | Paramètres |
|---|---|---|
| GET | `/quotes/rate` | `year`, `company_id` |
| GET | `/orders/monthly` | `year` ; achats et objectifs à `null` sans `purchases-menu` |
| GET | `/delivery/board` | |
| GET | `/recent/orders`, `/recent/quotes` | `limit` (5 par défaut, 20 max) |
| GET | `/recent/invoices`, `/recent/deliveries`, `/recent/purchases` | `limit` (8 par défaut, 20 max) |
| GET | `/top-clients`, `/nc-stats`, `/supplier-delays`, `/otd` | |
| GET, POST | `/mood` | POST : `mood` |

### Écran tâche / MES (`routes/web.php:1086-1104`, `Api\TaskStatuController`)

Préfixe `/{langue}/production/Task/Statu/Api`, middlewares `auth`, `verified`, `has.role`,
`check.factory`, `check.task.status`.

| Méthode | URL |
|---|---|
| GET | `/resolve` |
| GET | `/{id}` |
| POST | `/{id}/start`, `/{id}/pause`, `/{id}/finish` |
| POST | `/{id}/good-qty`, `/{id}/good-qty-stock`, `/{id}/bad-qty` |
| PUT | `/{id}/date`, `/{id}/resource` |
| POST | `/{id}/nc` |
| POST, DELETE | `/{id}/release` (`permission:task-release`) |
| GET | `/{id}/inspection` ; POST `/{id}/inspection/measure`, `/{id}/inspection/close`, `/{id}/inspection/measures/{measure}/nc` (`InspectionDossierController`) |

Le pointage par scan (`/{langue}/workshop/Scan`, `workshop.scan.store`, `throttle:120,1`) est
décrit dans `docs/pointage-scan.md`.

### Tableaux blancs (`routes/web.php:150-163`)

URL `/{langue}/api/collaboration/whiteboards…` **malgré le préfixe `api`** : routes web, session.
`ApiWhiteboardController` (index, store, show, update), `WhiteboardSnapshotController` (index,
store), `WhiteboardFileController` (index, store). Consommées par `components/Whiteboard.jsx`.

### GED

Le front utilise `files.json.*` (`routes/web.php:1652`) ; les routes Sanctum
`/api/files/json/*` appellent le même contrôleur (`FileApiController`) mais n'en reprennent que
quatre (list, store, patch, delete) : ni `replace`, ni `versions`, ni `office-edit`, ni `thumbnail`. `files.raw`,
`files.download`, `files.preview`, `files.thumbnail` restent en session : un tiers ne peut pas
télécharger un fichier.

---

## 6. Notes sur les routes Sanctum documentées pour les tiers

À connaître côté équipe, non écrit (ou écrit sans le détail technique) dans `docs/API.md` :

- **Aucun contrôle de permission** sur `POST/PUT /api/quote` et `POST/PUT /api/product`
  (`UpsertQuoteRequest::authorize()` et `UpsertProductRequest::authorize()` renvoient `true`) :
  un jeton de n'importe quel utilisateur crée des devis et des articles. En particulier,
  `products-edit` n'est **pas** exigé par l'API article (déjà signalé dans `CLAUDE.md`).
- `QuoteController` écrit chaque payload dans le canal `quote_import` (images base64 remplacées
  par leur taille), `ProductController` dans `product_import` (payload complet).
- `PUT /api/quote` : pas de contrôle de statut, pas de motif de perte, pas de garde sur les
  lignes déjà commandées ; `"lines": []` supprime toutes les lignes article
  (`QuoteController::syncLines`). Code en doublon → violation d'index unique (`quotes.code`,
  migration `2026_04_21_000001`) → 500.
- `PUT /api/product` : `sold` / `purchased` / `tracability_type` absents remis à 1, poste /
  famille / unité absents remis aux défauts (`ProductController::resolveReferences`).
- Vignettes de devis écrites dans `public/images/quote-lines` (hors GED).
- `POST /api/leads/inbound` : `tokenCan('leads:inbound')` ; les jetons de l'écran sont créés avec
  `['*']`, donc la capacité n'est jamais restreinte en pratique.
- `GET /api/n2p/sheet-lots/{ref}/stock` : n'expose que les mouvements `typ_move` 3, 5, 12, 14
  d'un article dont le poste est de type 3 (tôle) ; `aggregate_qty` = stock de la ligne de
  stock, pas du lot.
- Écran des jetons (`admin.api-tokens.*`, `routes/web.php:1256`) : liste et suppression de
  **tous** les jetons de tous les utilisateurs, pour tout porteur de `your-company-menu`.

---

## 7. Ce qui a disparu de l'ancienne `docs/API.md`

L'ancienne version (dernière mise à jour 2026-05-04) présentait comme API :

- `/api/kpi/*`, `/api/production/Task/Statu/Api/*`, `/api/collaboration/whiteboards*` : ce sont
  des routes **web** (§ 5), sans le préfixe `/api` pour les deux premières.
- `/api/inspection-projects/{id}`, `/api/inspection-sessions/{id}`, `/api/inspection-measures`,
  `/api/inspection-nonconformities` : **n'existent plus** (ancien écran d'inspection supprimé,
  remplacé par les plans de contrôle ; la saisie passe par `/{langue}/production/Task/Statu/Api/{id}/inspection*`).
- Les lectures `GET /api/companies`, `/api/quote`, `/api/order`, `/api/product`, `/api/tasks`,
  `/api/exports/sales-orders`, `/api/energy-consumptions` : présentes mais sous `auth:api` (§ 2).
- Erreurs factuelles corrigées : `tracability_type` vaut 1 = sans traçabilité, 2 = lot,
  3 = numéro de série (l'ancienne doc disait 1 = lot) ; les réponses devis et article sont
  enveloppées dans `data`.
