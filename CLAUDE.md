# WebErpMesv2 — ERP/MES pour l'industrie (tôlerie, usinage, moule)

## Stack technique actuelle
- **Backend** : Laravel 12 (PHP 8.2+), architecture MVC classique
- **Frontend** : React (dominant — composants riches migrés), Blade (layout/shell), Alpine.js (micro-interactions)
- **Vue.js** : SUPPRIMÉ
- **Livewire** : SUPPRIMÉ ✅ (reste en vendor uniquement comme dépendance transitive de laravel/pulse)
- **CSS** : Bootstrap 4 via AdminLTE (Tailwind supprimé)
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

### Phase 1 — Manuel (0 à 5 clients)
- VPS Ionos : demo + commercial
- VPS OVH (16vCore/64GB) : instances clients
- Installation manuelle par client

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

### Client 1 (mise en prod dans 2 semaines)
- Secteur : Tôlerie
- Utilisateurs : 1
- Modules : CRM, Devis, Commandes, Pré-commandes IA, BL, Facturation, FEC
- Tarif : 1 mois gratuit → 100€/mois

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
- `Contracts\PdpGateway` — émission et suivi (obligatoire)
- `Contracts\PdpInboundGateway` — réception des factures fournisseurs (optionnel)
- `Contracts\PdpCursorSyncGateway` — plateformes sans webhooks, synchro par curseur (optionnel)
- `PdpManager` — registre des drivers, résolu par `config('services.pdp.default')`
- `PdpInvoiceService` / `PdpIncomingInvoiceService` — orchestration agnostique

Les drivers sont enregistrés dans `AppServiceProvider::register()`.

### Drivers
| Driver | Émission | Réception | Suivi |
|---|---|---|---|
| `qonto` | données structurées, Qonto produit le document | non | polling manuel |
| `superpdp` | **notre** Factur-X déposé tel quel | oui | curseurs + `wem:pdp:sync` |

### SUPER PDP
API REST `https://api.superpdp.tech/v1.beta/`, OAuth 2.1 `client_credentials`
(access_token 30 min). **Aucun webhook** : la spec OpenAPI n'en déclare pas, la
synchronisation officielle passe par `starting_after_id` sur `/invoices` et
`/invoice_events`, d'où la table `pdp_sync_cursors` et la tâche planifiée.
Le mode bac à sable / production est porté par les identifiants, pas par l'URL.

### Document
`App\Services\Invoicing\FacturXBuilder` est la source unique du Factur-X
(profil EN 16931, via `horstoeko/zugferd`) : le PDF téléchargé par le client et
celui déposé sur la plateforme sont le même fichier. Il n'écrit rien sur disque.

## Dette technique

### 🔴 Bloquant avant prod
- Queue worker → Supervisor sur VPS Linux
- spatie/laravel-backup → backup base + fichiers

### 🔴 Facturation électronique — avant le 1er sept. 2026
- **Ligne d'annuaire** : aucune UI pour `POST /directory_entries`. Sans elle, aucune facture fournisseur n'arrive — c'est le prérequis de l'obligation de réception
- **`companies.siren` non obligatoire** : à rendre requis avant émission (le pre-check SUPER PDP vérifie l'adresse dans l'annuaire Peppol)
- **Champs `electronic_address` sans UI** : les colonnes existent sur `companies` et `factory` et alimentent BT-34/BT-49, mais ne sont éditables qu'en base
- Recherche d'adresse de facturation client via `GET /french_directory/companies` (non branchée)
- Émission de statuts sortants (`POST /invoice_events` : fr:204 prise en charge, fr:210 refus, fr:212 encaissement) sur les factures **fournisseurs** reçues — obligation côté acheteur, non implémentée

### 📋 Roadmap post-prod
- Supprimer Livewire résiduel (ArrowSteps, Calendar, ChatLive, LogsViewer, StockCurrent)
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

### RGPD — Reste à faire (hors code)
- Registre des traitements
- Mentions légales par client déployé
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
