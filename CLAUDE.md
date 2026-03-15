# WebErpMesv2 — ERP/MES pour l'industrie (tôlerie, usinage, moule)

## Stack technique actuelle
- **Backend** : Laravel (PHP), architecture MVC classique
- **Frontend** : Blade (rendu serveur dominant), Livewire (composants interactifs),
  Vue.js (1.2% — déprécié), React (spreadsheet)
- **CSS** : Tailwind CSS + Bootstrap 4 via AdminLTE (conflit à résoudre)
- **JS utilitaire** : Alpine.js (micro-interactions)
- **Bundler** : Laravel Mix (webpack.mix.js) — déprécié, à migrer vers Vite
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
1. Résoudre le conflit CSS Bootstrap/Tailwind
2. Optimiser Livewire QuoteLine (Alpine.js + OrderService)
3. Migrer Vue.js → React + Laravel Mix → Vite
4. Créer API REST QuoteLines → Migrer QuoteLine vers React
5. Créer API REST OrderLines → Migrer OrderLine vers React

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
- Actions Livewire sans vérification de permissions (Gates/Policies)
- Aucune vérification d'ownership sur les ressources (ex: destroyQuoteLine)
- $fillable à auditer sur les modèles critiques (Order, Quote, Product)

### CRITIQUE — Performance
- 5 requêtes SQL dans AppServiceProvider à chaque chargement de page
- whereColumn() manquant sur PurchaseLines (comparaison incorrecte)
- Aucun listener n'implémente ShouldQueue → tout bloque l'utilisateur
- Requêtes N+1 dans les vues Blade (relations non eager-loadées)
- storeOrder() sans DB::transaction() → risque de données partielles
- Livewire QuoteLine : wire:model.live excessif, render() trop lourd,
  ProductsSelect chargé entièrement en mémoire

### Infrastructure & Ops
- **CRITIQUE** : Aucune stratégie de backup (base + fichiers uploadés)
  → spatie/laravel-backup + destination externe (Backblaze/S3)
- Logs Laravel non persistants dans Docker
  → stderr channel + volume monté sur storage/logs
- Queue worker absent de docker-compose.yaml
- RGPD : page présente mais registre des traitements manquant,
  durées de conservation à préciser par type de donnée

### Moyenne priorité
- Double système CSS Bootstrap/Tailwind en conflit
- Laravel Mix → migrer vers Vite
- Vue.js marginal (1.2%) → supprimer
- AdminLTE impose jQuery comme dépendance globale
- Index manquants sur colonnes de filtrage (statu, delivery_status, orders_id)
- SoftDeletes à vérifier sur les entités métier critiques
- Accessors Eloquent sans cache (formatted_price, TotalTime, Margin)
- SelectDataService sans cache persistant
- Status::where('title', 'Finished') sans Cache::remember()
- Events qui transportent des IDs au lieu des modèles complets
- Risque de boucle infinie observer → event → update → observer
- OrderCreated avec broadcastOn() retournant 'channel-name' littéral
- Try/catch qui avalent les exceptions sans les logger
- Aucun test frontend, tests métier insuffisants