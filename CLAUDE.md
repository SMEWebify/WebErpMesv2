# WebErpMesv2 — ERP/MES pour l'industrie (tôlerie, usinage, moule)

## Stack technique actuelle
- **Backend** : Laravel 12 (PHP 8.2+), architecture MVC classique
- **Frontend** : Blade (rendu serveur dominant), Livewire (composants interactifs),
  Vue.js (1.2% — déprécié), React (spreadsheet)
- **CSS** : Tailwind CSS + Bootstrap 4 via AdminLTE (conflit à résoudre)
- **JS utilitaire** : Alpine.js (micro-interactions)
- **Bundler** : Vite (migré depuis Laravel Mix)
- **Temps réel** : Laravel Echo + Redis (laravel-echo-server)
- **Tests** : PHPUnit (backend), aucun test frontend connu
- **Infra** : Docker (Nginx + PHP-FPM), docker-compose.yaml

## Structure clé
- `app/Http/Controllers` — Contrôleurs web et API (devis, production, stock, CRM)
- `app/Livewire` — Composants Livewire (QuoteLine, OrderLine, etc.)
- `app/Models` — Modèles Eloquent (commandes, produits, stocks, nomenclatures)
- `resources/js` — Composants Vue (déprécié) + React (spreadsheet)
- `resources/views` — Templates Blade + vues Livewire
- `database/migrations` — Schéma ERP/MES (BOM, gammes, mouvements de stock)

## Stratégie frontend cible (open source)

### Règle de décision par type de composant
- **Blade + Livewire** : CRUD simple, formulaires standard → conserver
- **React** : composants riches à forte logique UI (QuoteLine, OrderLine, spreadsheet)
- **Alpine.js** : micro-interactions uniquement (toggle, dropdown, calcul léger)
- **Vue.js** : DÉPRÉCIÉ — aucun nouveau composant, suppression progressive

### Pourquoi React pour les composants clés
Projet open source : React est universel et réduit la barrière à la contribution.
Livewire est peu connu et freine les contributeurs externes.

### Roadmap de migration
1. ~~Migrer Laravel Mix → Vite~~ ✅ Fait
2. Résoudre le conflit CSS Bootstrap/Tailwind
3. Optimiser Livewire QuoteLine (Alpine.js + OrderService)
4. Migrer Vue.js → React
5. Créer API REST QuoteLines → Migrer QuoteLine vers React
6. Créer API REST OrderLines → Migrer OrderLine vers React

### Ce qu'on ne migre PAS
- Le layout AdminLTE/Blade reste en place
- Les CRUD simples restent Livewire

## Architecture de déploiement

### Modèle choisi : isolation par instance Docker
- Chaque client = 1 container Docker + 1 base de données isolée
- Pas de multi-tenant dans le code — isolation au niveau infra
- Démarrage : 5 premiers clients sans Docker (VPS classique)
- Migration vers Docker + Portainer à partir du 6ème client

### Stack de monitoring par instance
- **Laravel Pulse** : santé applicative (requêtes lentes, SQL, erreurs)
- **Portainer** : santé infrastructure (CPU, RAM, containers)
- **UptimeRobot** : disponibilité externe (alerte si instance down)

### Seuils d'alerte
- Requête SQL > 500ms → lente
- Temps de réponse HTTP > 1s → dégradé
- Temps de réponse HTTP > 3s → critique

## Dette technique identifiée

### CRITIQUE — Sécurité (bloquant avant mise en prod)
- Aucune vérification d'ownership sur les ressources (ex: destroyQuoteLine)
- Queue worker non configuré
  → ajouter service worker dans docker-compose.yaml ou Supervisor
-  Aucune stratégie de backup (base + fichiers uploadés)
  → spatie/laravel-backup + destination externe (Backblaze/S3)

### Moyenne priorité
- Double système CSS Bootstrap/Tailwind en conflit
- Vue.js marginal (1.2%) → supprimer
- AdminLTE impose jQuery comme dépendance globale
- Accessors Eloquent sans cache (formatted_price, TotalTime, Margin)
- Try/catch qui avalent les exceptions sans les logger
- Aucun test frontend, tests métier insuffisants


### Fait 
- whereColumn() corrigé sur PurchaseLines (#1024)
- DB::transaction() ajouté sur storeOrder() (#1025)
- ShouldQueue ajouté sur tous les listeners (#1026)
- Cache::remember() sur les 5 requêtes SQL du menu (#1027)
- Index manquants ajoutés en migration dédiée (#1028)
- wire:model.lazy sur les champs de formulaire (#1029)
- Cache::rememberForever() sur Status Finished (#1031)
- Events transportent les modèles complets (#1032)
- ~~Laravel Mix → migrer vers Vite~~ ✅ Migré (PRs #1030, #1034, #1035, #1036)
- ~~PR #1025 : event(OrderCreated) dispatché DANS la transaction~~ ✅ Corrigé (#1034)
- ~~PR #1029 : wire:model.lazy incorrect sur chatlive.blade.php~~ ✅ Corrigé (#1035)
- ~~PR #1029 : wire:model.lazy incorrect sur les selects de filtrage~~ ✅ Corrigé (#1035)
- ~~PR #1030 : asset(Vite::asset()) syntax incorrecte~~ ✅ 
- Logs Laravel non persistants dans Docker
  → stderr channel + volume monté sur storage/logs
- Queue worker absent de docker-compose.yaml
- Ownership QuoteLine
- Ownership OrderLine
- Auth autres composants Livewire
- Route guest NC
- $fillable Orders
- MenuServiceProvider créé — BuildingMenu extrait de AppServiceProvider
- EventServiceProvider nettoyé — fusion des listeners Registered
- broadcastOn() supprimé sur les 8 events (channel-name littéral retiré)
- Orders::find()->update() dans les listeners → observers déclenchés
- Ownership QuoteLine + OrderLine sécurisé
- Auth sur composants Livewire (QuotesIndex, OrdersIndex, etc.)
- SelectDataService : 16 méthodes cachées 
  (rememberForever référentiels, remember 30min semi-dynamiques)
- Index invoice_status ajouté en migration
- OrdersObserver : isDirty() avant Cache::forget()
- Products : Status IDs mis en rememberForever
- HomeController : eager loading companie (N+1 dashboard corrigé)
- storeOrder() : batch load QuoteLines avant la boucle
- SoftDeletes sur companies, companies_contacts, 
  companies_addresses, users
- LogsActivity sur Users et Companies (logOnlyDirty)
- RgpdAnonymizationService — anonymisation sans casser les FK
- Commandes artisan : rgpd:erase-contact, rgpd:export-contact, 
  rgpd:purge
- Rétention activity_log : 365 jours
- Purge hebdomadaire automatique

### À vérifier
- password exclu des logs d'activité User
- rgpd:purge passe bien par RgpdAnonymizationService 
  avant force-delete (pas de suppression brute si FK liées)

### Reste à faire (RGPD)
- Registre des traitements (document interne — hors code)
- Mentions légales à adapter par client déployé
- Durées de conservation à documenter par type de donnée