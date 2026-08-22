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
| `php artisan emails:send-auto-reports` | Envoie les rapports email automatiques aux utilisateurs selon l'heure configurée |
| `php artisan preorders:scan-output [--path=] [--pattern=] [--done-path=]` | Scanne le dossier output et importe les CSV comme pré-commandes |
| `php artisan stock:recalculate-cump [--dry-run]` | Recalcule le CUMP historique pour tous les emplacements produit (`--dry-run` pour simuler) |
| `php artisan quality:dispatch-calibration-alerts` | Notifie les responsables pour les appareils de contrôle qualité à étalonner |
| `php artisan ldap:import-users` | Importe les utilisateurs depuis l'Active Directory LDAP |
| `php artisan rgpd:erase-contact` | Anonymise les données personnelles d'un contact |
| `php artisan rgpd:export-contact` | Exporte les données personnelles d'un contact (droit d'accès RGPD) |
| `php artisan rgpd:purge` | Purge tokens expirés, email_logs > 1 an, soft-deleted > 90j |
| `php artisan hr:recompute-absence-days [--dry-run]` | Recalcule le coût en jours de toutes les demandes d'absence (à lancer une fois après migration des soldes de congés, et après tout changement du calendrier des jours fériés) |
| `php artisan wem:files:import [--dry-run] [--skip-move]` | **À lancer une fois après déploiement.** Déplace `public/{file,photo,drawing,stl,svg,images/products}` vers `storage/app/private/legacy`, renseigne `disk/path/kind` des lignes `files` existantes, et convertit les colonnes CAO produit en fichiers attachés |

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

### Dossier salarié (confidentiel)
Les alias `user` et `employment-contract` portent des données personnelles. Contrairement au
reste de la GED, ils **ne sont pas lisibles par toute l'usine** :
`App\Services\Files\FileConfidentiality` restreint la lecture, l'écriture et la suppression
au salarié concerné et aux porteurs de `human-resources-menu` (plus le rôle Admin).
Le contrôle est posé à deux endroits — `FilePolicy::view/update/delete` pour l'accès par id
de fichier (`files.raw`, `files.download`), et `FileApiController::resolveEntity()` pour
l'accès par entité (liste, dépôt) — pour qu'aucune des deux portes ne reste ouverte.
Rôles de fichier dédiés : `contrat`, `bulletin_paie`, `arret_travail`, `diplome`, `identite`
(`FileRole::forHumanResources()`).

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

## Ressources humaines

### Soldes de congés
- `leave_types` : nature du congé (CP, RTT, récupération, maladie, événement familial,
  formation, sans solde). `counts_against_balance` distingue ce qui consomme un droit de ce
  qui est seulement tracé (un arrêt maladie est une absence, pas un congé décompté).
- `leave_balances` : **uniquement le crédit** — droit acquis, report N-1, régularisation —
  par (salarié, nature, période de référence). Le débit n'est jamais stocké là : il est
  recalculé depuis `times_absences` par `LeaveBalanceService`, donc les deux ne peuvent pas
  diverger.
- `times_absences.days_count` : coût résolu de la demande, écrit à l'enregistrement par
  `TimesAbsenceObserver` (week-ends et jours fériés déjà retirés selon `absence_type_day` :
  calendaire / ouvrable = lun-sam / ouvré = lun-ven). Évite de dérouler chaque plage de
  dates à la lecture d'un solde.
- Période de référence configurable dans `config/hr.php` (défaut français : 1er juin →
  31 mai ; mettre le mois à 1 pour une année civile). Une absence à cheval sur deux périodes
  est **répartie** entre elles, jamais comptée deux fois.
- Le solde restant déduit les demandes validées **et** les demandes en attente, pour qu'un
  salarié ne puisse pas poser deux fois le même reliquat.
- Écrans : onglet « Soldes de congés » sur la fiche salarié (saisie des droits, RH),
  tableau usine sur `human-resources/leave/balances`, lecture seule sur le profil du salarié.

### Absences — validation
Le circuit existe : `times_absences.statu` vaut 1 = à valider, 2 = validé, 3 = refusé.
**Vue salarié** = onglet « Demande d'absence » du profil (saisie, puis amendement tant que
la demande est en attente — le bouton disparaît une fois traitée). **Vue valideur** = onglet
Absence de l'écran Temps, qui porte le select `statu`.

`AbsenceController` limite la saisie pour autrui et le changement de `statu` aux porteurs de
`human-resources-menu` ; un salarié ne peut amender que sa propre demande tant qu'elle est
en attente. Avant ça la distinction des deux vues était visuelle et non appliquée :
n'importe qui pouvait POSTer `statu=2` sur sa demande, et le solde n'aurait rien voulu dire.

Ce qui manque encore : aucune notification (pas d'`AbsenceNotification`, le valideur doit
aller voir l'écran), et `users.supervisor_id` est saisi sur la fiche salarié mais ne route
aucune validation — c'est « les RH valident », pas « mon manager valide ».

### Export paie
Il n'existe **pas** de format d'export standard : la DSN est produite par le logiciel de
paie, pas par l'ERP, et chaque éditeur (Silae, Sage, Cegid, Quadra…) importe sa propre
mise en page. `PayrollExportService` produit donc le plus petit dénominateur commun, une
ligne par salarié et par rubrique : matricule, période, code rubrique, quantité, unité.
S'adapter à un éditeur = mapper la colonne `code`, pas réécrire l'export.
- Rubriques émises : une par nature de congé (code = `leave_types.code`, en jours, absences
  **validées uniquement**), `HTRAV` (heures badgées) et `HPROD` (heures sur tâches).
- Une absence à cheval sur deux mois est répartie entre les deux bulletins.
- `users.payroll_number` porte le matricule connu du logiciel de paie ; à défaut l'export
  retombe sur `users.id` et le signale.
- L'écran liste les anomalies à traiter avant transmission (badgeage non refermé, matricule
  absent) et propose CSV ou XLSX via `maatwebsite/excel`, sur le patron de l'export FEC.

### Matrice de polyvalence (QSE)
`osh_formations` tenait déjà un registre de formations par salarié avec date de péremption,
mais `type_of_training` était du texte libre — « CACES 3 » et « Caces 3 » ne se regroupaient
pas. `training_types` fournit le référentiel, `training_type_resource` relie une
habilitation aux ressources sur lesquelles elle est attendue, et `HabilitationService`
calcule l'état par salarié : valide / bientôt échue / périmée / non obtenue / non formé
(fenêtre d'alerte dans `config/hr.php`, 60 jours par défaut). Un renouvellement prime sur la
session périmée ; sans date de fin l'habilitation est à vie.

**L'écran est informatif et ne bloque rien.** `HabilitationService` n'est appelé par aucun
chemin d'affectation, de lancement de tâche ni d'ordonnancement : `taskAlerts()` est calculé
à la demande pour l'écran et se contente de lister les tâches dont l'opérateur n'est pas
couvert. Une habilitation périmée ne doit jamais arrêter une machine — c'est couvert par un
test dédié (`SkillsMatrixTest::a_missing_authorisation_does_not_prevent_assigning_the_task`).

### Temps travaillé — agrégation partagée
`AttendanceAggregator` apparie les événements bruts en temps travaillé, pour les pointages
badgeuse (`attendances`, in/out) comme pour l'activité de production (`task_activities`,
start/end par tâche). L'écran présence et l'export paie s'appuient dessus, donc ils ne
peuvent pas diverger. Un badgeage non refermé, une ouverture en double ou une fermeture
orpheline sont comptés en anomalies plutôt qu'ignorés.

> ⚠️ Corrigé au passage : Carbon 3 renvoie un écart **signé**, et le code d'origine appelait
> `$fin->diffInSeconds($debut)` — l'écran présence cumulait donc des secondes négatives.
> Régression couverte par `AttendanceReportTest`.

## Dette technique

### 🔴 Exploitation (client 1 en ligne)
- ✅ spatie/laravel-backup en place (`backup:run` / `backup:clean` / `backup:monitor` planifiés)
- Queue worker → Supervisor sur le VPS client : à confirmer + surveiller (jobs en échec,
  `queue:failed` non vide = fonctionnalité silencieusement cassée côté client)
- Vérifier que le cron `schedule:run` tourne bien sur l'instance client (sinon : pas de
  backup, pas de purge RGPD, pas de rapports email auto)
- Restauration de backup jamais testée en réel → à faire une fois sur une copie

### 📋 Roadmap

#### RH — reste à faire
- **Notification d'absence** : le workflow de validation existe (voir plus haut), mais aucune
  notification n'est émise et `users.supervisor_id` ne route pas la validation
- **Absences absentes de la capacité planning** : `PlanningController` ne tient compte que des
  jours fériés, pas des congés validés
- **Alerte de péremption des habilitations** : `expiration_date` est affichée sur la matrice
  mais aucune notification n'est déclenchée, alors que le patron existe juste à côté
  (`quality:dispatch-calibration-alerts`)
- **Mapping des codes rubrique paie** par éditeur (Silae, Sage, Cegid…) : l'export sort un
  format neutre, la correspondance des codes reste à faire côté client
- **Doublons de classes `Attendance`** : `App\Models\Attendance` (câblé aux routes) et
  `App\Models\HumanResources\Attendance` (inutilisé) pointent sur la même table, idem pour
  les deux `AttendanceController`. À dédoublonner
- **Périmètre de droits du module RH** : le groupe `human-resources` n'exige que `has.role`
  (n'importe quel rôle), la permission `human-resources-menu` ne masque que le menu. Les
  routes de congés ajoutées la portent, les routes historiques (fiches salarié, contrats,
  notes de frais) restent à durcir — voir aussi `HumanResourcesController@UpdateUser` qui
  attribue un rôle sans contrôle
- **`/pointage` public** : hors groupe `auth`, sans throttle, avec la liste de tous les
  salariés dans un select
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
