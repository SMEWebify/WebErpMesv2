# WebErpMesv2 — ERP/MES pour l'industrie (tôlerie, usinage, moule)

## Stack technique actuelle
- **Backend** : Laravel 12 (PHP 8.2+), architecture MVC classique
- **Frontend** : Blade (rendu serveur dominant), Livewire (composants interactifs),
  Vue.js (1.2% — déprécié), React (spreadsheet)
- **CSS** : Bootstrap 4 via AdminLTE (Tailwind supprimé)
- **JS utilitaire** : Alpine.js (micro-interactions)
- **Bundler** : Vite
- **Temps réel** : Laravel Echo + Redis
- **Tests** : PHPUnit (backend), aucun test frontend
- **Infra** : Docker (Nginx + PHP-FPM), docker-compose.yaml

## Structure clé
- `app/Http/Controllers` — Contrôleurs web et API
- `app/Livewire` — Composants Livewire (QuoteLine, OrderLine, etc.)
- `app/Models` — Modèles Eloquent
- `resources/js` — Vue (déprécié) + React (spreadsheet)
- `resources/views` — Templates Blade + vues Livewire
- `database/migrations` — Schéma ERP/MES

## Stratégie frontend cible (open source)

### Règle de décision
- **Blade + Livewire** : CRUD simple, formulaires standard → conserver
- **React** : composants riches (QuoteLine, OrderLine, spreadsheet)
- **Alpine.js** : micro-interactions uniquement
- **Vue.js** : DÉPRÉCIÉ — suppression progressive

### Roadmap de migration
1. ✅ Migrer Laravel Mix → Vite
2. ✅ Résoudre le conflit CSS Bootstrap/Tailwind
3. Migrer Vue.js → React (Prompt 4)
4. Créer API REST QuoteLines → Migrer QuoteLine vers React (Prompt 5)
5. Créer API REST OrderLines → Migrer OrderLine vers React

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
- Prompt 4 : Migration Vue.js → React
- Prompt 5 : API REST QuoteLine → migration React
- Sélects dynamiques précommande (client/adresse/contact)
- Refonte UX écrans Task et QuoteLine
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

### Frontend
- Vite migré (Laravel Mix supprimé)
- Bootstrap/Tailwind conflit résolu (Tailwind supprimé)
- 50 fichiers audités, 16 corrigés
- @vite() partout
- wire:model.lazy sur formulaires
- wire:model.live restauré sur chat et filtres
- event(OrderCreated) hors transaction

### RGPD
- SoftDeletes : companies, contacts, addresses, users
- LogsActivity sur Users et Companies
- RgpdAnonymizationService
- Commandes : rgpd:erase-contact, rgpd:export-contact, rgpd:purge
- Rétention activity_log : 365 jours
- Purge hebdomadaire automatique
```

---

**Ce qui reste vraiment à faire :**
```
Avant prod (infra uniquement) :
→ Queue worker Supervisor
→ spatie/laravel-backup

Post-prod (code) :
→ Prompt 4 Vue → React
→ Prompt 5 API REST QuoteLine