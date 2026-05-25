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
| `php artisan emails:send-auto-reports` | Envoie les rapports email automatiques aux utilisateurs selon l'heure configurée |
| `php artisan preorders:scan-output [--path=] [--pattern=] [--done-path=]` | Scanne le dossier output et importe les CSV comme pré-commandes |
| `php artisan stock:recalculate-cump [--dry-run]` | Recalcule le CUMP historique pour tous les emplacements produit (`--dry-run` pour simuler) |
| `php artisan quality:dispatch-calibration-alerts` | Notifie les responsables pour les appareils de contrôle qualité à étalonner |
| `php artisan ldap:import-users` | Importe les utilisateurs depuis l'Active Directory LDAP |
| `php artisan rgpd:erase-contact` | Anonymise les données personnelles d'un contact |
| `php artisan rgpd:export-contact` | Exporte les données personnelles d'un contact (droit d'accès RGPD) |
| `php artisan rgpd:purge` | Purge tokens expirés, email_logs > 1 an, soft-deleted > 90j |

### Tâches planifiées (`routes/console.php`)

| Fréquence | Commande | Rôle |
|---|---|---|
| Quotidien à 01h00 | `backup:clean` | Supprime les anciennes sauvegardes (rétention `config/backup.php`) |
| Quotidien à 02h00 | `backup:run` | Sauvegarde complète DB + `storage/app` |
| Quotidien à 09h00 | `backup:monitor` | Alerte mail si dernier backup > 2 jours |
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
php artisan config:cache         # Régénérer le cache de config
php artisan route:cache          # Régénérer le cache des routes

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

## Dette technique

### 🔴 Bloquant avant prod
- Queue worker → Supervisor sur VPS Linux
- spatie/laravel-backup → backup base + fichiers

### 📋 Roadmap post-prod
- Supprimer Livewire résiduel (ArrowSteps, Calendar, ChatLive, LogsViewer, StockCurrent)
- Sélects dynamiques précommande (client/adresse/contact)
- Accessors Eloquent sans cache (formatted_price, TotalTime, Margin)
- Try/catch sans logging
- Tests métier manquants

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
