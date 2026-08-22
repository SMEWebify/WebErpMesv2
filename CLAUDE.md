# WebErpMesv2 — ERP/MES pour l'industrie (tôlerie, usinage, moule)

> ⚠️ **PRODUCTION ACTIVE — 1 client en ligne depuis avril 2026.**
> Le dépôt n'est plus un projet pré-prod : des données réelles (devis, commandes, BL,
> factures, FEC) vivent sur une instance client. Conséquences sur toute modification :
> - **Migrations rétro-compatibles** : additives par défaut. Pas de `drop`/`rename` de colonne
>   sans étape de transition (ajout → double écriture → bascule → suppression).
> - **Backup avant migration** : `php artisan backup:run` puis `migrate --pretend` avant `migrate`.
> - **Pas de breaking change silencieux** sur les URLs, les exports comptables (FEC, factures)
>   ni les fichiers déjà attachés (GED / `LegacyFileController`).
> - **Toute régression est visible client** : privilégier le correctif ciblé au refactor large.

## Stack technique actuelle
- **Backend** : Laravel 12 (PHP 8.2+), architecture MVC classique
- **Frontend** : React (dominant — composants riches migrés), Blade (layout/shell), Alpine.js (micro-interactions)
- **Vue.js** : SUPPRIMÉ
- **Livewire** : SUPPRIMÉ ✅ (reste en vendor uniquement comme dépendance transitive de laravel/pulse)
- **CSS** : Bootstrap 5.3 via AdminLTE 4 (Tailwind supprimé)
- **Bundler** : Vite
- **Temps réel** : Laravel Echo + Redis
- **Tests** : PHPUnit (backend), aucun test frontend
- **Infra** : Docker (Nginx + PHP-FPM), docker-compose.yaml

## Structure clé
- `app/Http/Controllers` — Contrôleurs web et API
- `app/Models` — Modèles Eloquent
- `resources/js/components` — Composants React (80+ fichiers .jsx)
- `resources/views` — Templates Blade + shell pages montant les composants React
- `database/migrations` — Schéma ERP/MES

## Stratégie frontend cible (open source)

### Règle de décision
- **React** : tous les composants riches (index, show, lignes, tableaux, dashboards)
- **Blade** : layout, shell, formulaires simples sans interaction
- **Livewire** : SUPPRIMÉ ✅
- **Alpine.js** : micro-interactions uniquement
- **Vue.js** : SUPPRIMÉ ✅

### Roadmap de migration
1. ✅ Migrer Laravel Mix → Vite
2. ✅ Résoudre le conflit CSS Bootstrap/Tailwind
3. ✅ Migrer Vue.js → React (Prompt 4)
4. ✅ Migrer QuoteLine vers React
5. ✅ Migrer OrderLine vers React
6. ✅ Supprimer les derniers composants Livewire résiduels (ArrowSteps, Calendar, ChatLive, LogsViewer, StockCurrent)

## Commandes Artisan

### Commandes métier custom

| Commande | Description |
|---|---|
| `php artisan wem:diagnostics` | Vérifie les prérequis de l'environnement (PHP, extensions, APP_KEY, Redis, DB, Pusher) |
| `php artisan wem:n2p:push-order {orderId} [--sync]` | Pousse une commande vers Nest2Prod (queue par défaut, `--sync` pour immédiat) |
| `php artisan wem:pdp:sync [--tenant=] [--events] [--inbound]` | Synchronise la PDP : cycle de vie des factures émises + réception des factures fournisseurs. Indispensable pour les plateformes sans webhooks (SUPER PDP) |
| `php artisan wem:pdp:directory [--open=] [--date=] [--close=] [--lookup=] [--search=]` | Annuaire : liste/ouvre/ferme **notre** ligne de réception (prérequis du 1er sept. 2026), et cherche l'adresse de facturation d'un client par SIREN ou raison sociale |
| `php artisan wem:pdp:seed-sandbox [--force]` | **Dev uniquement.** Écrit l'identité bac à sable SUPER PDP : vendeur Burger Queen dans `factory`, client Tricatel dans `companies`. Écrase l'identité de la société |
| `php artisan emails:send-auto-reports` | Envoie les rapports email automatiques aux utilisateurs selon l'heure configurée |
| `php artisan preorders:scan-output [--path=] [--pattern=] [--done-path=]` | Scanne le dossier output et importe les CSV comme pré-commandes |
| `php artisan stock:recalculate-cump [--dry-run]` | Recalcule le CUMP historique pour tous les emplacements produit (`--dry-run` pour simuler) |
| `php artisan quality:dispatch-calibration-alerts` | Notifie les responsables pour les appareils de contrôle qualité à étalonner |
| `php artisan ldap:import-users` | Importe les utilisateurs depuis l'Active Directory LDAP |
| `php artisan rgpd:erase-contact` | Anonymise les données personnelles d'un contact |
| `php artisan rgpd:export-contact` | Exporte les données personnelles d'un contact (droit d'accès RGPD) |
| `php artisan rgpd:purge` | Purge tokens expirés, email_logs > 1 an, soft-deleted > 90j |
| `php artisan wem:files:import [--dry-run] [--skip-move]` | **À lancer une fois après déploiement.** Déplace `public/{file,photo,drawing,stl,svg,images/products}` vers `storage/app/private/legacy`, renseigne `disk/path/kind` des lignes `files` existantes, et convertit les colonnes CAO produit en fichiers attachés |

### Tâches planifiées (`routes/console.php`)

| Fréquence | Commande | Rôle |
|---|---|---|
| Quotidien à 01h00 | `backup:clean` | Supprime les anciennes sauvegardes (rétention `config/backup.php`) |
| Quotidien à 02h00 | `backup:run` | Sauvegarde complète DB + `storage/app` |
| Quotidien à 09h00 | `backup:monitor` | Alerte mail si dernier backup > 2 jours |
| Toutes les 15 min | `wem:pdp:sync` | Facturation électronique : statuts des factures émises + factures fournisseurs reçues |
| Hebdomadaire | `rgpd:purge` | Purge RGPD (voir tableau ci-dessus) |
| Mensuel | `activitylog:clean` | Nettoie les logs d'activité (durée `config/activitylog.php`) |

> Pour que le scheduler fonctionne, ajouter au crontab du serveur :
> ```
> * * * * * php /var/www/html/artisan schedule:run >> /dev/null 2>&1
> ```

### Queue worker (Supervisor requis en prod)

```bash
# Démarrer le worker (développement)
php artisan queue:work --sleep=3 --tries=3

# Vérifier les jobs en échec
php artisan queue:failed

# Relancer les jobs en échec
php artisan queue:retry all

# Vider la file (⚠️ irréversible)
php artisan queue:flush
```

Config Supervisor recommandée (`/etc/supervisor/conf.d/wem-worker.conf`) :
```ini
[program:wem-worker]
command=php /var/www/html/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
numprocs=2
```

### Commandes utiles (maintenance)

```bash
# Cache
php artisan cache:clear          # Vider tout le cache (⚠️ si renommage statuts)
php artisan optimize:clear       # Vide tout : config, routes, views, cache (à faire avant de recacher)
php artisan config:cache         # ✅ Régénérer le cache de config
php artisan view:cache           # ✅ Précompiler les vues Blade
# ❌ PAS de php artisan route:cache — casse la localisation mcamara
# (setLocale() gelé au moment du cache CLI → routes figées sur une seule locale,
#  /fr/xxx et /en/xxx retournent 404. Cf. routes/web.php:29)

# Base de données
php artisan migrate              # Appliquer les migrations
php artisan migrate:status       # État des migrations

# Backup manuel (spatie/laravel-backup)
php artisan backup:run           # Déclencher un backup immédiat
php artisan backup:list          # Lister les sauvegardes disponibles
```

## Architecture de déploiement

### Phase 1 — Manuel (0 à 5 clients) — **en cours**
- VPS Ionos : demo + commercial
- VPS OVH (16vCore/64GB) : instances clients
- Installation manuelle par client
- **1 instance client en production depuis avril 2026**

### Procédure de déploiement sur une instance en production
```bash
php artisan down                 # fenêtre hors heures ouvrées
php artisan backup:run           # backup DB + storage AVANT tout
git pull && composer install --no-dev -o && npm ci && npm run build
php artisan migrate --pretend    # relire le SQL avant de l'appliquer
php artisan migrate
php artisan optimize:clear && php artisan config:cache && php artisan view:cache
# ❌ jamais php artisan route:cache (casse la localisation mcamara)
php artisan queue:restart        # les workers rechargent le nouveau code
php artisan up
```

### Phase 2 — Docker (5+ clients)
- Portainer + Nginx Proxy Manager
- 1 container + 1 base par client
- `/data/clients/{client}/storage` + `/data/clients/{client}/mysql`

### Monitoring
- Laravel Pulse : santé applicative
- Portainer : infrastructure
- UptimeRobot : disponibilité externe

### Seuils d'alerte
- SQL > 500ms → lente
- HTTP > 1s → dégradé  
- HTTP > 3s → critique
- Disque > 80% → alerte

## Clients

### Client 1 — ✅ EN PRODUCTION depuis avril 2026
- Secteur : Tôlerie
- Utilisateurs : 1
- Modules : CRM, Devis, Commandes, Pré-commandes IA, BL, Facturation, FEC
- Tarif : 1 mois gratuit → 100€/mois
- Données réelles → obligations RGPD effectives (mentions légales, registre des traitements)
- Point de vigilance : instance mono-utilisateur, donc pas de couverture "test en charge"
  par l'usage — les régressions se voient directement en exploitation

## Gestion documentaire unifiée (GED)

Toutes les entités qui portent des fichiers (produits, devis, commandes, BL, factures,
achats, NC, mouvements de stock, sociétés, opportunités) passent par un seul dispositif.

### Modèle
- `files` : `kind` (mesh / brep / cad2d / vector / doc / image / sheet / archive / other,
  déduit de l'extension par `App\Services\Files\FileKindResolver`), `extension`, `disk`, `path`
- `fileables` (pivot polymorphe) : `role` (plan, modele_3d, vectoriel, photo, cam,
  certificat, controle, autre — `App\Services\Files\FileRole`) et `is_primary`
- `App\Services\Files\FileableRegistry` : liste blanche alias → modèle, pour que le front
  n'envoie jamais un nom de classe arbitraire

### Stockage
Hors racine web, sous `storage/app/private/files/{aaaa}/{mm}`, servi par les routes
authentifiées `files.raw` (inline) et `files.download`. Les anciens dossiers publics sont
servis par `LegacyFileController` sur les **mêmes URLs** (`/drawing/x.pdf`...), déclarées en
fin de `routes/web.php` hors du groupe de locale. Un SVG est renvoyé avec `nosniff` + CSP
`sandbox` (fichier utilisateur donc potentiellement exécutable).

### Front
- Montage Blade : `@include('include.file-manager-mount', ['fileableType' => 'product', 'fileableId' => $id])`
- `resources/js/components/files/` : `FileManager` (dépose + liste + viewer),
  `FileDropzone`, `FileViewer` (dispatch par `kind`), `viewers/` (Mesh, Brep, Dxf, Image, Pdf)
- Chaque moteur est en `React.lazy()` : ouvrir un PDF ne télécharge pas three.js
- **STL/OBJ/PLY/3MF/glTF** : loaders three.js — **STEP/IGES/BREP** : `occt-import-js`
  (OpenCascade en WASM, 7,4 Mo chargés à la demande, aucun convertisseur serveur requis) —
  **DXF** : `dxf-parser` (parsing seul, pas de dépendance three) + rendu maison dans
  `resources/js/lib/dxf/toThree.js`, caméra orthographique

### Compatibilité
`products.drawing_file / stl_file / svg_file / picture` ne sont plus la source de vérité
mais un **cache de lecture**, resynchronisé par `FileStorageService::refreshLegacyColumns()`
à chaque attache/détache. Les lignes de devis, lignes de commande, `ProductResource` et
`TaskStatuApp` continuent de les lire sans modification.

## Facturation électronique (PDP)

Réforme française : réception obligatoire pour toutes les entreprises au **1er septembre 2026**.

### Architecture
Un contrat, plusieurs plateformes. `App\Services\Integrations\Pdp` :
- `Contracts\PdpGateway` — émission et suivi (**obligatoire**)
- `Contracts\PdpInboundGateway` — réception des factures fournisseurs
- `Contracts\PdpCursorSyncGateway` — plateformes sans webhooks, synchro par curseur
- `Contracts\PdpDirectoryGateway` — annuaire : nos lignes de réception, adresses de nos clients
- `Contracts\PdpStatusReportingGateway` — statuts déclarés au fournisseur (obligation acheteur)
- `PdpManager` — registre des drivers, résolu par `config('services.pdp.default')`
- `PdpInvoiceService` / `PdpIncomingInvoiceService` — orchestration agnostique

Seul `PdpGateway` est requis ; les quatre autres sont optionnels et testés par
`instanceof`, ce qui permet à une plateforme pauvre en fonctions de coexister
sans code mort ni méthodes vides.

Les drivers sont enregistrés dans `AppServiceProvider::register()`.

### Drivers
| Driver | Émission | Réception | Suivi | Annuaire | Statuts sortants |
|---|---|---|---|---|---|
| `qonto` | données structurées, Qonto produit le document | non | polling manuel | non | non |
| `superpdp` | **notre** Factur-X déposé tel quel | oui | curseurs + `wem:pdp:sync` | oui | oui |

### Pièges vérifiés en conditions réelles (SUPER PDP, bac à sable)
- **Un « warning » schematron fait rejeter.** `PEPPOL-EN16931-R008` porte le libellé « still status warning » et fait pourtant rejeter la facture en `fr:213` / `REJ_SEMAN`. Règle retenue : tout ce qui rend `is_valid` faux bloque le dépôt.
- **Le rejet arrive des heures après.** Constaté : dépôt à 23h58, `fr:213` à 03h07. Interroger l'API quelques secondes après un dépôt n'apprend rien — seul `wem:pdp:sync` fait foi.
- **Un dépôt accepté n'est pas une facture émise.** `POST /invoices` renvoie 200 et `api:uploaded` même quand le document sera rejeté. Il n'existe aucun signal synchrone.
- **`POST /invoices/convert` est l'outil de diagnostic** : il analyse le document et renvoie l'erreur réelle sans rien déposer, là où `/validation_reports` minimise et où `/invoices` échoue en silence.
- **Le numéro de facture est consommé dès le premier dépôt, même rejeté.** Tout renvoi sous le même numéro est refusé (`fr:213`, code `DOUBLON`). Corriger une facture rejetée impose donc d'en émettre une nouvelle — la carte masque le bouton de dépôt dans ce cas.

### SUPER PDP
API REST `https://api.superpdp.tech/v1.beta/`, OAuth 2.1 `client_credentials`
(access_token 30 min). **Aucun webhook** : la spec OpenAPI n'en déclare pas, la
synchronisation officielle passe par `starting_after_id` sur `/invoices` et
`/invoice_events`, d'où la table `pdp_sync_cursors` et la tâche planifiée.
Le mode bac à sable / production est porté par les identifiants, pas par l'URL.

### Statuts déclarés au fournisseur (obligation de l'acheteur)
Recevoir ne suffit pas : l'acheteur doit renvoyer le cycle de vie du document
(`fr:204` prise en charge, `fr:205` approuvée, `fr:207` litige, `fr:210` refus,
`fr:211` paiement transmis). Ces statuts remontent aussi à l'administration,
qui en déduit l'exigibilité de la TVA sur les prestations de services.

Menu « Déclarer » sur chaque ligne de la boîte de réception →
`PdpIncomingInvoiceService::reportStatus()` → `PdpStatusReportingGateway`.
Un motif est **exigé** sur les statuts défavorables et transmis tel quel au
fournisseur ; un document déposé à la main n'a pas de destinataire et ne
propose pas l'action.

### Réception et rapprochement
Une facture reçue entre dans `pdp_incoming_invoices` (boîte de réception), puis
deux voies :
- **Rapprochement** (voie normale) : l'écran `/purchases/waiting/invoice` est
  ouvert avec `?companies_id=&incoming_id=`, affiche le document du fournisseur
  en regard des réceptions à cocher, signale l'écart de total, et rattache le
  document à la facture d'achat créée.
- **Sans rapprochement** : crée l'en-tête seul, pour les factures sans commande
  ni réception (frais, abonnements).

Les lignes du document reçu **ne sont jamais recopiées** :
`purchase_invoice_lines` ne porte ni libellé ni montant, seulement les clés vers
la ligne de commande et la ligne de réception. C'est ce qui garantit qu'on ne
paie que ce qui a été commandé et reçu ; le document du fournisseur reste une
pièce à confronter, conservée dans `payload`.

### Document
`App\Services\Invoicing\FacturXBuilder` est la source unique du Factur-X
(profil EN 16931, via `horstoeko/zugferd`) : le PDF téléchargé par le client et
celui déposé sur la plateforme sont le même fichier. Il n'écrit rien sur disque.

`assertPartiesAreIdentifiable()` refuse en amont les documents dont les parties
ne sont pas identifiables (SIREN, TVA, adresse, acheminement), avec un message
en français listant tout ce qui manque — sinon l'utilisateur reçoit une règle
schematron après un aller-retour jusqu'à la plateforme.

`config/invoicing.php` porte le contenu **normatif** : mode de facturation
(BT-23) et mentions légales obligatoires (BT-22 : pénalités de retard,
indemnité de recouvrement, escompte). ⚠️ Les textes par défaut sont le régime
légal supplétif : **à aligner sur les CGV de chaque société déployée**, une
facture qui les contredit n'étant pas opposable.

## Dette technique

### 🔴 Exploitation (client 1 en ligne)
- ✅ spatie/laravel-backup en place (`backup:run` / `backup:clean` / `backup:monitor` planifiés)
- Queue worker → Supervisor sur le VPS client : à confirmer + surveiller (jobs en échec,
  `queue:failed` non vide = fonctionnalité silencieusement cassée côté client)
- Vérifier que le cron `schedule:run` tourne bien sur l'instance client (sinon : pas de
  backup, pas de purge RGPD, pas de rapports email auto)
- Restauration de backup jamais testée en réel → à faire une fois sur une copie

### 🔴 Facturation électronique — avant le 1er sept. 2026
- **Ligne d'annuaire** : son ouverture se fait **dans l'interface de la plateforme**, pas dans WEM — c'est l'identité du client vis-à-vis de sa PDP, et l'y dupliquer n'apporterait rien. WEM se contente de constater son absence et d'alerter. La commande `wem:pdp:directory --open=` reste disponible pour un déploiement en masse.
- **`factory.electronic_address` sans UI** : éditable en base uniquement, contrairement à celui des sociétés clientes
- **`companies.siren` non obligatoire à la saisie** : `FacturXBuilder::assertPartiesAreIdentifiable()` bloque désormais l'émission si SIREN/TVA sont absents ou malformés, mais rien n'empêche encore de créer une société sans ces champs

### 📋 Roadmap
- Sélects dynamiques précommande (client/adresse/contact)
- Accessors Eloquent sans cache (formatted_price, TotalTime, Margin)
- Try/catch sans logging
- Tests métier manquants

#### Stock — suite de la refonte
- **Stock projeté** (courbe temporelle physique + PO en attente − réservations) : algo `projectionForProduct(daysHorizon)`, colonne "Rupture prévue à J+X" sur écran Statut du stock, courbe chart.js sur fiche produit
- **Bug 3 — CUMP dirty read** : `StockCalculationService::recalculateAndPersist()` fait `lockForUpdate` sur SLP puis appelle `getCurrentStockMove()` en 2 sous-requêtes hors lock → réécrire en un SQL agrégé unique dans le lock
- **Bug — sorting TOCTOU** : `StockLocationProductsController::sorting()` fait `canDispatch()` sans lock ni transaction, même schéma que le transfert avant fix (voir commit f29bad10)
- **Bug — transfer.destination race** : `StockService::transfer()` fait un `firstOrCreate` sur la destination sans lock → race possible si deux transferts créent la même paire (stock_locations_id, products_id) simultanément
- **Inventaire physique** : table `inventory_details` créée en 2021, jamais reliée à un contrôleur ni à une UI, `typ_move=1 "Inventories"` jamais émis. À activer complètement (comptage, écarts, régularisation)
- **Champ `stock_location_products.reserve_qty` mort** : jamais écrit, lu par un paramètre `includeReserve` trompeur de `getCurrentStockMove`. À supprimer (source de vérité = `stock_reservations`) ou à câbler comme cache dénormalisé
- **Allocation BOM automatique** à la clôture de tâche : aujourd'hui `good-qty-stock` doit être appelé manuellement, pas de décrément auto des composants du BOM
- **FIFO/FEFO automatique** sur consommation tâche : `expiration_date` du batch est stockée mais l'allocation dans `TaskStatuController@goodQtyStock` boucle sans tri
- **Quarantaine réception** : `storeFromPurchaseOrder` met directement en stock, pas d'étape "zone d'attente / contrôle qualité" avant validation
- **Alerte réappro auto** : `mini_qty` sert uniquement au coloriage UI, aucun event / notification / mail déclenché quand stock < mini_qty
- **Validation qty négative sur autres flux stock** : `StoreStockMoveRequest` durci (commit f29bad10) mais `UpdateStockMoveRequest` reste à vérifier
- **Retirer StockCurrentApp** (composant + mount app.js + endpoint `stockCurrentJson`) une fois l'écran Statut du stock validé en usage réel

### ⚠️ À vérifier
- password exclu des logs d'activité User
- rgpd:purge passe par RgpdAnonymizationService avant force-delete
- Cache::rememberForever() Status IDs → php artisan cache:clear si renommage

### RGPD — Reste à faire (hors code) — ⚠️ exigible dès maintenant (client en prod)
- Registre des traitements
- Mentions légales par client déployé (client 1 concerné depuis avril 2026)
- Durées de conservation documentées par type de donnée

## Fait ✅

### Sécurité
- Ownership QuoteLine (9 méthodes) + OrderLine (5 méthodes)
- Auth sur composants Livewire (QuotesIndex, OrdersIndex, etc.)
- Route guest NC avec UUID
- $fillable Orders nettoyé
- Champs révision restreints aux rôles admin/manager

### Performance
- whereColumn() sur PurchaseLines
- DB::transaction() sur storeOrder()
- Batch load QuoteLines dans storeOrder()
- Cache::remember() sur 5 requêtes SQL menu
- SelectDataService : 16 méthodes cachées
- Index manquants + invoice_status ajoutés
- OrdersObserver : isDirty() avant Cache::forget()
- Products : Status IDs en rememberForever
- HomeController : eager loading N+1 corrigé
- storeOrder() : batch load avant boucle

### Events & Observers
- ShouldQueue sur tous les listeners
- Events transportent les modèles complets
- broadcastOn() supprimé (channel-name littéral retiré)
- EventServiceProvider nettoyé
- MenuServiceProvider extrait de AppServiceProvider
- Orders::find()->update() → observers déclenchés

### Frontend — Migration React (complète)
- Vite migré (Laravel Mix supprimé)
- Bootstrap/Tailwind conflit résolu (Tailwind supprimé)
- Vue.js entièrement supprimé
- 80+ composants React dans `resources/js/components/`
- Migration Livewire → React :
  - **CRM** : LeadsIndex, OpportunitiesIndex, CompaniesIndex, CompanyForm, CompanyContacts, CompanyAddresses, CompanyTimeline, CompanyDashboard
  - **Devis** : QuotesIndex, QuoteLinesIndex, QuoteLinesPage, QuoteChartsTab
  - **Commandes** : OrdersIndex, OrderLinesIndex, OrderLinesPage
  - **Achats** : PurchasesIndex, PurchaseLinesPage, PurchasesQuotationIndex, PurchasesQuotationShow, PurchasesRequest, PurchaseReceiptIndex, PurchaseReceiptLinesPage, PurchasesWaitingReceipt, PurchasesWaitingInvoice, PurchaseInvoicesIndex
  - **Livraisons** : DeliverysIndex, DeliverysRequest, DeliveryLinesTab
  - **Facturation** : InvoicesIndex, InvoicesRequest, InvoiceExportLines, FecExportLines
  - **Avoirs** : CreditNotesIndex, ReturnsIndex, ReturnShow
  - **Stocks** : StockDetailPage, ProductHistory, ProductsIndex
  - **Tasks** : TasksIndex, TaskManagePage, TaskStatuApp, TaskLines, GtdBoard, TodayView
  - **Atelier** : WorkshopReportsApp (rapports atelier : réalisé pointé, rebuts, charge machine, andon)
  - **Planning** : LoadPlanningIndex, KanbanBoard, KanbanSetting
  - **GMAO** : GmaoDashboard
  - **Qualité** : QualityIndex, NonConformitiesIndex, AuditPlannerApp, InspectionProjectsApp
  - **Dashboard** : HomeDashboard, DashboardGrid + widgets (KPI, OTD, NcStats, MoodTracker, TopClients, SupplierDelay, MonthlyStats, RecentItems, Goal, Announcement, OrdersMonthly, DeliveryBoard, QuoteRate)
  - **Autres** : SerialNumbersIndex, MethodsOverview, EstimatedBudgetsIndex, NestingPage, ConstructionSitePage, GanttChart, DocumentTable, SetupWizard, ProcessDiagramApp, NotificationLinePage, UserProfilePage, UserAutoEmailReportsPage, Whiteboard
  - NikoNiko (MoodTrackerWidget), ChartJS, SerialNumber

### RGPD
- SoftDeletes : companies, contacts, addresses, users
- LogsActivity sur Users et Companies
- RgpdAnonymizationService
- Commandes : rgpd:erase-contact, rgpd:export-contact, rgpd:purge
- Rétention activity_log : 365 jours
- Purge hebdomadaire automatique
