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
- **Backend** : Laravel 13 (PHP 8.4+), architecture MVC classique
- **Frontend** : React (dominant — composants riches migrés), Blade (layout/shell), Alpine.js (micro-interactions)
- **Vue.js** : SUPPRIMÉ
- **Livewire** : SUPPRIMÉ ✅ (reste en vendor uniquement comme dépendance transitive de laravel/pulse)
- **CSS** : **AdminLTE 4 / Bootstrap 5.3** (jeroennoten/laravel-adminlte 4, depuis la v3.0.0),
  Bootstrap fourni **une seule fois** par `public/vendor/adminlte`. Classes **Bootstrap 5
  uniquement** (`ms-1`, `text-bg-success`, `float-end`, `form-select`…). Voir la section
  « Shell AdminLTE 4 ». Tailwind supprimé.
- **Briques de tableau** : `resources/js/components/table/` (`SortIcon`, `Pagination`,
  `StatusBadge`, `StatusFilter`) — à utiliser plutôt que d'en recopier dans un écran.
  `DataTable` : tableau d'index déclaratif (liste de colonnes `key/label/render/sortable/
  filter/total/mobile`), colonnes masquables et réordonnables persistées sous les clés
  localStorage **existantes** de l'écran, filtres par colonne, total de page, et rendu
  en cartes sous `md`. L'écran garde son fetch, son tri et sa pagination serveur.
  Utilisé par les 20 index React. Cas particuliers couverts par des props plutôt que par un
  tableau écrit à la main : `rowClickable`, `renderExpanded` (formulaire déplié sous la
  ligne), `renderGroupHeader` / `rowStyle` (regroupement RFQ), `actionsColumn={false}` +
  colonne `hideable: false` (actions déplaçables), `mobileActions`. Les variantes de rendu
  historiques (`unsortableIcon`, `unsortableCursor`, `nowrap`, `tableClassName`…) existent
  pour migrer un écran sans changement visible — ne pas les « nettoyer » sans le vouloir.
  `useIndexTab(clé, { tabs, forced })` : onglet d'un index (tableau de bord / liste…)
  mémorisé par navigateur sous `{ecran}_index_tab` ; à défaut « list » sur téléphone,
  `forced` (fiche société, `?tab=`) prime et n'est pas mémorisé.
- **Bundler** : Vite
- **Temps réel** : Laravel Echo + Redis
- **Tests** : PHPUnit (backend), Vitest (frontend, `resources/js/tests`, `npm test`)
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
7. ✅ AdminLTE 3 / Bootstrap 4 → AdminLTE 4 / Bootstrap 5.3 (v3.0.0)
8. ✅ Select2 → Tom Select (`<x-adminlte-select-bs>`), puis retrait de jQuery

## Shell AdminLTE 4 (v3.0.0)

Principe : **s'aligner sur la v4**, ne pas reproduire le rendu d'AdminLTE 3. Aucune couche
de compatibilité Bootstrap 4 n'est chargée.

### Assets
- **Bootstrap 5.3** (CSS et JS) vient d'AdminLTE (`public/vendor/adminlte`, `public/vendor/bootstrap`,
  ressources publiées et **suivies par git**). `resources/sass/app.scss` (pages AdminLTE) ne
  contient **que nos styles** ; ne jamais y réimporter `bootstrap/scss/bootstrap` : un second
  Bootstrap, compilé avec d'autres couleurs, écraserait les règles d'AdminLTE (`.btn-teal`…).
  Les pages **hors** gabarit AdminLTE (liens publics, portail client, installation, visite sur
  site, pointage) chargent `resources/sass/standalone.scss`, qui compile Bootstrap.
- **Bootstrap JS** : uniquement le bundle chargé par AdminLTE (`window.bootstrap`). Ne jamais
  `import 'bootstrap'` ni `import { Tooltip } from 'bootstrap'` dans Vite (le paquet n'a pas de
  `sideEffects` : tout le module serait embarqué, avec des écouteurs globaux en double).
- **jQuery n'est plus chargé du tout** (ni AdminLTE, ni Vite, ni plugin). Les listes
  déroulantes à recherche sont des `<x-adminlte-select-bs>` (**Tom Select**, plugin `TomSelect`
  de `config/adminlte.php`, assets dans `public/vendor/tom-select`). Tom Select déclenche
  l'événement natif `change` sur le `<select>` d'origine ; pour en remplacer les options :
  `el.tomselect.clear(true)`, `clearOptions()`, reconstruire les `<option>`, puis `sync()`
  (cf. `include/form/company-address-contact-sync`, société → adresse/contact sur proforma,
  facture, BL). Une option `value=""` devient un simple placeholder : passer
  `:config="['allowEmptyOption' => true]"` quand « aucun » est un vrai choix.
- **Font Awesome 6.7.2** reste la bibliothèque d'icônes (plugin `FontAwesome`) ; les vues du
  paquet utilisent Bootstrap Icons, chargées par AdminLTE. La copie `public/vendor/fontawesome-free`
  est la nôtre (AdminLTE 4 ne la publie plus) : aucun paquet npm. Les noms v5 (`fas fa-times`,
  `fa-edit`…) restent valides comme alias v6 ; écrire les noms v6 dans le nouveau code.

### Balisage
- Classes **Bootstrap 5** uniquement ; `tools/bs5-codemod.cjs` convertit une vue Bootstrap 4
  (`node tools/bs5-codemod.cjs [--write]`, utile pour une vue `print/custom` ou un fork).
- Couleurs : palette **AdminLTE 4** (`violet`, `sky`, `teal`, `orange`, `indigo`…). Un bouton
  coloré s'écrit `btn-teal` (généré par le paquet), jamais `btn bg-teal` ; un fond coloré avec
  texte contrasté s'écrit `text-bg-*`.
- **Mode sombre** : aucune couleur neutre en dur (`#fff`, `#f8f9fa`, `#495057`…) dans les styles
  inline ou SCSS → variables `var(--bs-body-bg)`, `var(--bs-tertiary-bg)`, `var(--bs-border-color)`,
  `var(--bs-secondary-color)` ; classes `bg-body` / `bg-body-tertiary` plutôt que `bg-white` /
  `bg-light` (qui restent clairs en sombre).
- **Cartes écrites à la main** : AdminLTE 4 ne donne plus de marge basse à `.card` (le
  composant `<x-adminlte-card>` pose `mb-4`) → `mb-3` explicite.
- `<select>` → `form-select` ; pas d'enveloppe `input-group-prepend/append` ; champ fichier =
  `<input type="file" class="form-control">`.
- Cartes de tableau de bord `small-box` : balisage v4 (`small-box-icon`, `text-bg-*`).

### Fichiers propres au projet
- `public/css/wem-shell.css` : ajustements assumés (élément actif de sidebar neutre en couleur
  primaire, place réservée aux badges du menu, `sidebar-hidden` des écrans atelier, onglets du
  mode IFrame, `table-light` en mode sombre, `profile-user-img`).
- Vues surchargées (`resources/views/vendor/adminlte`, 4) : `master` (permissions JS, favicons
  et fond de la marque, lanceur mobile, détection d'onglet IFrame), widget clair/sombre/pro,
  préchargeur WEM/N2P, bouton de menu + lanceur mobile.
- `App\Menu\Filters\LocalizedHrefFilter` : préfixe de langue sur les liens du menu (sans lui,
  l'entrée courante n'est jamais active).

### Apparence par utilisateur
`App\Support\UiTheme` : 17 habillages d'AdminLTE 4 repris de
`vendor/almasaeed2010/adminlte/src/utils/palette.mjs` + « nest2prod » (défaut tant que
`WEBERP_COLOR_THEME=nest2prod`), et une couleur principale (`data-lte-primary`). Choix stocké
dans `users.ui_preset` / `users.ui_primary` (null = défaut), réglé dans Profil → Apparence,
appliqué par `View::composer('adminlte::page')`. Le thème Nest2Prod (`theme-nest2prod.css`)
n'est chargé que si l'utilisateur garde cet habillage sans autre couleur principale.
Indépendant du mode clair / sombre / pro (`App\Support\ThemeMode`, en session).

## Commandes Artisan

### Commandes métier custom

| Commande | Description |
|---|---|
| `php artisan wem:diagnostics` | Vérifie les prérequis de l'environnement (PHP, extensions, APP_KEY, Redis, DB, Pusher) |
| `php artisan wem:n2p:push-order {orderId} [--sync]` | Pousse une commande vers Nest2Prod (queue par défaut, `--sync` pour immédiat) |
| `php artisan wem:pdp:sync [--tenant=] [--events] [--inbound]` | Synchronise la PDP : cycle de vie des factures émises + réception des factures fournisseurs. Indispensable pour les plateformes sans webhooks (SUPER PDP) |
| `php artisan wem:pdp:directory [--open=] [--date=] [--close=] [--lookup=] [--search=]` | Annuaire : liste/ouvre/ferme **notre** ligne de réception (prérequis du 1er sept. 2026), et cherche l'adresse de facturation d'un client par SIREN ou raison sociale |
| `php artisan wem:pdp:seed-sandbox [--force]` | **Dev uniquement.** Écrit l'identité bac à sable SUPER PDP : vendeur Burger Queen dans `factory`, client Tricatel dans `companies`. Écrase l'identité de la société |
| `php artisan wem:esign:sync` | Relit les enveloppes DocuSign en attente : range le devis signé dans la GED et passe le devis en « Gagné » ; range l'accord signé d'une dérogation dans sa GED. Filet de la notification DocuSign (instance non publiée en HTTPS) |
| `php artisan emails:send-auto-reports` | Envoie les rapports email automatiques aux utilisateurs selon l'heure configurée |
| `php artisan preorders:scan-output [--path=] [--pattern=] [--done-path=]` | Scanne le dossier output et importe les CSV comme pré-commandes |
| `php artisan wem:orders:backfill-promised-dates [--dry-run] [-v]` | **À lancer une fois après déploiement.** Renseigne `order_lines.promised_delivery_date` des lignes existantes : ancienne valeur du premier report historisé, sinon `delivery_date` actuelle. Ne touche jamais une ligne déjà renseignée |
| `php artisan wem:purchases:backfill-line-totals [--dry-run] [-v]` | **À lancer une fois après déploiement.** Recalcule `purchase_lines.unit_price_after_discount` / `total_selling_price` (prix × qté × (1 − remise)) des lignes où ils diffèrent : l'écran React des lignes d'achat ne les renseignait pas, d'où 0 € sur « En attente de facturation ». Les écritures comptables passées ne sont pas touchées |
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
| Toutes les 15 min | `wem:pdp:sync` | Facturation électronique : statuts des factures émises + factures fournisseurs reçues |
| Toutes les 15 min | `wem:esign:sync` | Signature électronique : relecture des enveloppes de devis et de dérogations en attente |
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

Passage de la v2.x à la **v3.0.0** (AdminLTE 4) : même procédure, plus les points de
`docs/upgrade-3.0.md` (vues surchargées et `print/custom`, cache navigateur).

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

## Plans de contrôle (bullage) — lot 1 du chantier FAI
## Guides utilisateur (menu « Guides »)

`/guides` liste les pas à pas illustrés, `/guides/{slug}` en affiche un. **Un guide = un dossier**
`resources/guides/{slug}/` : `guide.md` (en-tête YAML `title`, `summary`, `category`, `order`,
`duration`, `icon`, `can`, `updated` + Markdown), traduction facultative `guide.{locale}.md`,
captures dans `img/`. Ajouter un guide = déposer un dossier : ni route, ni migration, ni code.
- `App\Services\Guides\GuideLibrary` lit, filtre (`can` : une permission suffit) et rend le
  Markdown (`html_input => escape`) avec deux extensions : bloc ```` ```steps ```` (frise
  d'étapes) et citations `> ⚠️` / `> 💡` (encadrés). Les `## ` forment le sommaire latéral.
- Captures servies par `guides.media` (images du dossier `img/` seulement, `nosniff`).
- Production des guides : agent `training-writer` + Playwright (`tools/training/`, scénarios
  rejoués sur l'instance **locale**, données préfixées `[FORMATION]`, identifiants
  `TRAINING_EMAIL` / `TRAINING_PASSWORD` du `.env`). Jamais sur une instance client.
- Tests : `tests/Feature/GuidesTest.php`.


Écran « Plans qualité » (`/quality/plans`, menu Qualité → Inspection), bulleur
(`/quality/plans/{plan}/bubbler`) et onglet « Plans de contrôle » de la fiche article.
L'ancien écran d'inspection est **supprimé** : son URL (`quality.inspection.projects`, citée
par d'anciennes notifications) redirige vers Plans qualité ; ses tables `inspection_documents`,
`inspection_instruments`, `inspection_nonconformities` sont supprimées par migration **si
vides** (sinon conservées, orphelines, avec un avertissement).
- **Plan de contrôle** = `inspection_plans` : article + révision (= `products.ind`, ou l'indice
  de la ligne de commande) + fichier plan **de la GED de l'article**. Brouillon → validé (figé,
  409 sur toute écriture) ; « Nouvelle version » recopie les cotes, et sa validation rend
  obsolète le plan validé **de la même révision** seulement (des commandes à l'ancien indice
  peuvent tourner).
- **Cotes** = `inspection_control_points` avec `inspection_plan_id` (l'ancien
  `inspection_project_id` est devenu facultatif) : rectangle et bulle en **proportions de la
  page** (0–1), tolérancement géométrique, `is_fai`, opération (`methods_services_id`).
  `tol_min` / `tol_max` restent des **écarts signés** ; une tolérance géométrique = nominal 0,
  tol_max = zone. Coordonnées toujours dans le **repère de la page d'origine** :
  `inspection_plans.rotation` (0/90/180/270, en plus du /Rotate du PDF) n'est qu'un affichage,
  converti à l'écran (`toView` / `toPage` du bulleur, `InspectionPlan::toView()` pour la
  renumérotation).
- **Onglet « À bullé »** : `App\Services\Inspection\ControlPlanNeeds` (2 requêtes, test de
  comptage). Besoin = `companies.requires_control_plan`, sauf forçage
  `products.control_plan_mode` (`always` / `never`).
- **Droits** : lecture pour tous ; `inspection-plans-edit` (créer, bullé) et
  `inspection-plans-approve` (valider), posés en middleware de route.
- **Lecture de la zone tracée** (pré-remplit la fiche, n'enregistre rien d'autre que ce
  pré-remplissage, l'utilisateur relit) : texte du PDF dans le rectangle (`textIn`, décodé par
  `parseDimensionText` de `characteristics.js`, tests Vitest), sinon image découpée envoyée à
  `App\Services\Inspection\CharacteristicReader` (provider IA actif, même patron que les
  justificatifs de notes de frais ; seule la zone part, jamais le plan ; route throttlée 30/min).
- **pdf.js** (`pdfjs-dist`, chargé à la demande, `resources/js/lib/pdf/pdfRender.js`) dessine
  le plan sous les bulles. Le lecteur PDF de la GED reste celui du navigateur.
- **Miniatures** : rendues par le navigateur puis déposées (`files.thumbnail_path`,
  `inspection_plans.thumbnail_path`, `App\Services\Files\ThumbnailStore` : PNG/WebP/JPEG
  ≤ 480 px relus dans les octets). Aucun moteur PDF côté serveur.
- **Contrôle en fabrication (lot 2)** : `App\Services\Inspection\TaskInspection`. Un dossier
  (`inspection_projects` avec `inspection_plan_id`) par ligne de commande dont l'article a un
  plan **validé à l'indice commandé**, créé au démarrage de la tâche ou à l'ouverture de
  l'onglet ; premier dossier d'un article à un indice = premier article (`is_fai`). Une
  session de mesure par tâche, une mesure par (session, cote, pièce `part_index`), la grille
  affiche la dernière toutes tâches confondues. Pièces à mesurer selon l'échantillonnage
  (`partsFor`, grille plafonnée à 50 pièces) ; une cote FAI d'un dossier FAI exige la pièce 1.
  Jugement par `InspectionControlPoint::evaluate()` ; sans tolérance, l'opérateur juge.
  Écran tâche : onglet « Contrôle » (`components/inspection/TaskInspection.jsx`, plan bullé +
  grille, appareils `quality_control_devices`) et **simple avertissement** à la déclaration de
  pièces bonnes tant que la pièce 1 n'est pas mesurée — **jamais de blocage serveur** (test).
  Onglets « En cours » / « Terminés » de Plans qualité.
- **Contrôle libre** (article choisi à la main, sans commande ni tâche : réception, audit) :
  bouton « Contrôler » d'un plan validé → `TaskInspection::createFree()`, page
  `/quality/inspections/{dossier}`, même composant de saisie (`TaskInspection.jsx`, prop
  `baseUrl`). Un dossier est donc identifié par lui-même, pas par une tâche
  (`InspectionDossierController` sert les deux portes).
- **NC depuis une mesure** hors tolérance : `TaskInspection::declareNonConformity()` crée une
  NC du module qualité (`quality_non_conformities`, code `NC-CTRL-{mesure}`) pré-remplie
  (exigence via `InspectionControlPoint::requirementText()`, valeur, pièce, appareil,
  opérateur, commande, tâche) et la relie à la mesure ; une seule NC par mesure.
- **Rapport premier article (lot 3)** : `App\Services\Inspection\FaiReport` (formulaires 1-3
  sur le modèle AS9102, tout déduit de l'ERP sauf `FaiReport::EDITABLE`), page
  `/quality/inspections/{dossier}/report`, signatures « vérifié » (`inspection-plans-edit`)
  puis « approuvé » (`inspection-plans-approve`, fige le rapport), PDF `print/inspection-fai`
  rangé dans la GED de la commande (sinon de l'article), rôle `controle`. Format neutre ou
  AS9102 : `config('inspection.report_format')` / `INSPECTION_REPORT_FORMAT`.
- Tests : `tests/Feature/Inspection/`. Suite (lots 2-4) : mémoire `project_quality_plans_fai_chantier`.

## Visite sur site (opportunité, mobile)

`/opportunities/{id}/visit` : page autonome sans sidebar (`opportunity-visit.blade.php` →
`components/visits/OpportunityVisitApp.jsx`), un brouillon `opportunity_visits` par visite.
- **Photos** : réduites à 2560 px dans le navigateur, envoyées par `files.json.store` dans la
  GED **de l'opportunité** (rôle `photo`, hashtag `visite-{id}`) — pas de stockage propre.
- **Cotes** : JSON `measurements` [{label, value, unit, note}], sauvegarde automatique.
- **Mémo vocal** : `MediaRecorder` → `VisitTranscriptionService` (Whisper OVH, clé OVH de
  l'écran IA). L'audio n'est **jamais écrit** côté serveur (fichier temporaire de la requête) ;
  en cas d'échec le navigateur le garde pour réessayer. Sans clé OVH : dictée navigateur.
- **Compte rendu** : `VisitReportService`, provider IA actif, éditable ; la validation crée un
  événement type 3 « Visite sur site » et fige la visite.

## Dossier d'affaire (synthèse de l'opportunité)

L'affaire **est** l'opportunité : pas d'entité propre. Onglet « Synthèse » (premier, actif)
de `opportunities.show`, alimenté par `opportunities.json.summary` / `.timeline`
(`OpportunitySummaryController`, mêmes middlewares que la fiche).
- **Périmètre** (`App\Services\Affairs\AffairScope`) : devis `opportunities_id` → commandes
  client `quotes_id` → lignes article → tâches → lignes d'achat `tasks_id` ; factures par
  `order_id` **ou** par une ligne de facture sur une ligne de l'affaire (facture
  multi-commandes). Un devis sans opportunité reste un devis courant, jamais rattaché.
- **`AffairSummaryService`** : étape déduite des documents (`App\Enums\AffairStage`),
  blocages par règles explicites (devis expiré / envoyé sans réponse, achat en retard,
  réception partielle, tâche suspendue, commande en retard, facture échue, visite non
  validée), prochaine action, montants. **9 requêtes quelle que soit la taille**, lectures
  en `toBase()` (les accesseurs de prix feraient une requête par ligne) — couvert par un
  test de comptage. Seuils dans `config/affairs.php`.
- **Permissions** : les sections d'un module dont l'utilisateur n'a pas le menu
  (`quotes-menu`, `orders-menu`, `purchases-menu`, `deliverys-menu`, `invoices-menu`)
  disparaissent avec leurs blocages, montants et actions ; l'étape reste calculée sur tout.
- Front : `components/affairs/AffairSummary.jsx` et `AffairTimeline.jsx` (onglet
  `#TimeLine`, chargé quand il devient visible). Traductions `resources/lang/*/affairs.php`.
- Tests : `tests/Feature/Affairs/`.

## Trames de devis, duplication, copie de lignes

Une trame est un devis `quotes.is_template = true`, éditée avec l'écran devis habituel
(code `TRAME-{id}`, hors numérotation). Elle garde un client de l'origine sans importance :
le vrai client est choisi à l'instanciation (« Nouveau devis » → « Partir d'une trame »).
- **Scope global** sur `Quotes` : une trame est invisible partout par défaut (listes,
  sélecteurs, KPI Eloquent, IA). Un écran qui édite un devis donné passe par
  `Quotes::withTemplates()` ; `onlyTemplates()` liste les trames. La liaison de route
  `/quotes/{id}` et `FileableRegistry::find()` incluent les trames.
- Les agrégats lus **directement** sur `quotes` / `quote_lines` (`DB::table`) ne voient pas le
  scope : filtrer `is_template` ou `QuoteLines::excludingTemplates()`.
- Une trame ne se convertit jamais en commande (`storeOrderJson` → 422).
- Toutes les copies passent par `App\Services\Quotes` (`QuoteDuplicator` pour un devis entier,
  `QuoteLineCopier` pour une ligne) et `Planning\BillOfMaterialsCopier` pour la gamme : la
  nomenclature est recopiée sur toute sa profondeur (les niveaux inférieurs ne portent que
  `sub_assembly_id`).

## Lignes de présentation (sections, sous-totaux, textes, masquage, forfait)

`quote_lines`, `order_lines` et `order_confirmation_lines` portent `line_type`
(`article` par défaut | `section` | `subtotal` | `text`, enum `App\Enums\SalesLineType`),
`hide_on_pdf` (article compté dans le total mais non imprimé) et `pdf_package`
(section imprimée au forfait : 0 détail, 1 montant seul, 2 « 1 × unité », l'unité étant
le `methods_units_id` de la section — pas d'unité « forfait » en dur).
- **Une ligne non article vaut zéro** : `HasSalesLineType` force qty/prix/remise à 0 à
  l'enregistrement, et côté commande reliquats à 0 + statuts livraison/facturation à 3.
  Une somme SQL non filtrée reste donc juste ; **un comptage ou une moyenne par ligne doit
  filtrer** (`->articles()`, `->filter->isArticle()`), de même que la ventilation de TVA.
- **Jamais livrée ni facturée** : `DeliveryLines` et `InvoiceLines` refusent à la création
  une ligne de commande non article (`OrderLines::guardArticle`), donc rien n'atteint le
  Factur-X. N2P, planning, nesting et export des commandes ne voient que les articles.
- **PDF** : `SalesPrintLayout` réécrit `$Document->Lines` (articles visibles + une ligne
  synthétique par forfait) et fournit `$printRows` aux vues qui savent afficher titres et
  sous-totaux. Un document sans présentation n'est pas touché (`printRows = null`) :
  `SalesPdfLegacyRenderingTest` compare le HTML à des références capturées avant la
  fonctionnalité — **ne jamais régénérer ces références pour faire passer le test**.
  Une vue `print/custom/*` (non versionnée) qui ignore `$printRows` imprime quand même un
  document juste, sans les titres.
- Pages client (lien public devis/commande, portail) : `SalesPrintLayout::webRows()`.
- Conversion en commande : `presentation=keep|drop` (`QuoteLineToOrderLineConverter::linesToConvert`) ;
  une section suit si un de ses articles est commandé. L'API d'upsert ne supprime jamais
  une ligne non article absente du payload.

## Signature électronique des devis (DocuSign)

Le client signe un devis depuis son **lien public** (`/guest/quote/{uuid}`) : bouton
« Signer le devis » sur un devis **Envoyé** (statu 2), non expiré, hors trame, dont le
contact a un e-mail valide (`QuoteSignatureService::blockingReason()`).
- **Configuration en base, pas dans le .env** : écran Intégrations → Signature électronique
  (`esignature_settings`, singleton). Clé privée RSA et clé HMAC chiffrées (`encrypted`),
  jamais réaffichées (champ vide = conserver). Bouton de test + lien de consentement JWT
  (à faire une fois ; l'URL de l'écran doit figurer dans les « Redirect URIs » DocuSign).
- **Contrat** `App\Services\Integrations\Signature\Contracts\SignatureGateway`, driver
  `DocuSignGateway` (JWT Grant signé par openssl, client HTTP Laravel, pas de SDK).
  Un second prestataire s'ajoute par un driver dans `QuoteSignatureService::gateway()`.
- **Enveloppe** = PDF du devis (`DocumentPdfService`, inchangé) + page « Bon pour accord »
  (`print/esignature-acceptance`) qui porte les marqueurs en texte blanc où DocuSign
  accroche signature / nom / date. Le PDF commercial n'est **pas** modifié.
- **Mode** : signature intégrée depuis la page publique (défaut, toute personne ayant le
  lien peut signer) ou e-mail DocuSign au contact (identité vérifiée par la boîte mail).
- **Rien n'est cru sans relecture API** : le retour navigateur (`?event=signing_complete`),
  la notification Connect (HMAC si clé saisie) et `wem:esign:sync` ne font qu'appeler
  `QuoteSignatureService::sync()`, verrouillé par enveloppe. La notification n'est
  demandée que si l'URL de l'instance est en HTTPS.
- **Signé** → PDF signé + certificat dans la GED du devis (rôle `document_signe`,
  hashtag `signature-{id}`), devis passé en Gagné (statu 3) s'il était ouvert/envoyé.
  **Pas de conversion automatique en commande.**
- Devis modifié après l'envoi (total TTC différent) ou mode changé → l'enveloppe en
  attente est annulée (`voided`) et une nouvelle part au clic suivant.
- Tests : `tests/Feature/ESignature/` (API DocuSign simulée, clé RSA jetable en fixture).

## Dérogations — accord du client (ISO 19443)

Écran Qualité → Dérogations (`quality_derogations`, 2021) + fiche `/quality/derogation/{id}`
(`quality-derogation-show`). Type 1 = interne, 2 = externe ; statut 1 en cours, 2 attente
client, 3 validé, 4 annulé.
- **Colonnes additives** : `companies_id`, `companies_contacts_id`, `order_lines_id` (repris de
  la NC à l'enregistrement s'ils sont vides), `customer_reply_date`, `customer_signatory`,
  `validated_by` / `validated_at`. `reply` et `decision` sont devenues facultatives.
- **Validation** (`App\Services\Quality\DerogationApproval`) : passer en « Validé » exige la
  permission `quality-derogation-validate` (rôle Admin, migration) et, pour une **externe**,
  client, réponse, date, signataire et une pièce de la GED au rôle `accord_client` ou
  `document_signe`. Une interne n'exige que la décision. Une dérogation validée n'est
  modifiable que par un porteur de la permission. Historique : activitylog sur le modèle.
- **GED** : alias `derogation`, rôles `FileRole::forDerogations()`.
- **BL bloqué** (`DerogationDeliveryGuard`) : une ligne de commande visée par une dérogation
  **externe ouverte** (statut 1 ou 2), directement ou via `quality_non_conformities.order_lines_id`,
  ne passe pas sur un BL (`orders.lines.json.store-delivery`, `deliverys-request.store` → 422).
  Non contrôlé : BL automatique de la facturation directe (comme le plafond d'encours).
- **DocuSign** (`DerogationSignatureService`, table `quality_derogation_signatures`) : réutilise
  la configuration et le driver des devis, mais toujours **par e-mail DocuSign** au contact
  (pas de page publique), et seulement `isComplete()` (l'option « signature des devis
  publics » ne s'applique pas). Document = `print/derogation-request`. Envoi → statut 2.
  Dérogation modifiée (empreinte `contentHash()`) → enveloppe en attente annulée. Signée →
  PDF signé + certificat dans la GED (rôle `document_signe`), réponse / date / signataire
  complétés s'ils étaient vides ; **jamais validée d'office**. Notification et
  `wem:esign:sync` relisent devis et dérogations.
- Tests : `tests/Feature/Quality/DerogationApprovalTest.php`, `tests/Feature/ESignature/DerogationSignatureTest.php`.

## Reports de date des lignes de commande

`delivery_date` / `internal_delay` ne gardent que la dernière valeur saisie ; chaque
changement est historisé dans `order_line_delay_histories` (ancienne/nouvelle valeur, motif,
origine, auteur) par `OrderLinesObserver::updated`. `order_lines.promised_delivery_date` est
la date promise au client : posée à la création, **jamais modifiée** (l'observer annule toute
écriture, colonne hors `$fillable`).
- **Une écriture de masse contourne l'observer** : `OrderLines::where(...)->update(['delivery_date' => …])`
  ou `DB::table('order_lines')` ne laissent aucune trace. Mettre à jour ligne par ligne.
- Origine et motif : `App\Support\OrderLineDelayContext::run($origin, fn () => …, $motif)` ;
  par défaut `manual` (web), `api` (/api), `system` (console).
- Lignes de présentation non historisées.
- **OTD** : `OrderKPIService::getPromisedDateServiceRate()` (date promise) est servi **à côté**
  de la mesure historique (dernière date) par `kpi.otd` — bascule non décidée.
- Dossier d'affaire : blocage `order_postponed` (date promise dépassée, date actuelle non) et
  entrées `line_rescheduled` dans la chronologie.
- Tests : `tests/Feature/OrderLineDelayHistoryTest.php`.

## Repères d'équipement (nucléaire)

`quote_lines`, `order_lines`, `order_confirmation_lines` portent `equipment_tag` (texte
nullable) : une **liste JSON** de repères (une ligne de quantité > 1 en porte plusieurs), lue
et écrite par le cast `App\Casts\EquipmentTags` (toujours un tableau, null en base si vide,
JSON sans échappement de `/` pour que le LIKE trouve `1RCP/001`). Trait `HasEquipmentTags`.
- Recopiés par `QuoteLineToOrderLineConverter` (conversion et proforma) et figés sur l'ARC ;
  **jamais** sur une ligne dupliquée ni sur une ligne de présentation.
- Recherche : `->withEquipmentTag($texte)` (partielle, `ESCAPE '!'` pour SQLite et MySQL) ;
  la recherche de l'index des commandes porte aussi sur les repères (`matched_equipment_tags`).
- Impression seulement si renseigné (`pdf-sales` = devis/commande/AR, BL, fiche suiveuse) ;
  blocs `@if` placés juste avant `</td>` : un `@endif` en fin de ligne mangerait le saut de
  ligne et changerait le rendu des documents sans repère.
- Plus de repères que la quantité : simple avertissement dans le tiroir, jamais bloquant.
- Tests : `tests/Feature/EquipmentTagTest.php`, `resources/js/tests/components/equipmentTags.test.jsx`.

## Bon pour fabrication (BPF)

`order_lines.released_for_production_at` / `_by` (hors `$fillable`), écrits seulement par
`App\Services\Production\ProductionRelease`. Option `production.require_release`
(`PRODUCTION_REQUIRE_RELEASE`, **false** par défaut) : inactive = aucun blocage **et aucun
affichage** BPF (colonne, bandeau, badges).
- **Bloqué** : démarrage d'une tâche dont le **service** est productif (type 1) et dont la
  ligne (directe ou via sous-ensembles) n'a pas de BPF. Matière (2-6, 8), sous-traitance (7),
  achats, réservations : jamais. Points de démarrage : `Api/TaskStatuController::start`
  (422), Kanban `TaskController::sync` (colonnes « Started » et « In progress », 422, rien
  appliqué), GTD `gtdMove`, événement N2P
  `task.started/resumed` (livraison en échec, rejouable), commande passée « En cours »
  (tâches productives non libérées laissées à leur statut). Un nouveau point de démarrage
  doit appeler `blockingReason()` / `assertCanStart()`.
- **Donner / annuler** : `order-lines.json.release` (POST/DELETE), permission
  `production-release` (Admin par migration). Annulation refusée dès qu'une tâche de la
  ligne a une activité START.
- **Revue et changement** : BPF refusé si la revue de commande est `rejected` ou si une
  demande de changement attend (`change_requested_by` sans `change_approved_at`). Un
  changement d'`ind` révoque le BPF (`OrderLinesObserver::updating`) ; une ligne neuve ou
  dupliquée n'en a jamais. BPF et indice tracés par activitylog.
- Affichage : colonne de l'index des lignes de commande, badge par ligne et action groupée
  sur les lignes cochées de la fiche commande (`orders.lines.json.release`, refus rendus
  ligne par ligne), bandeau de l'écran tâche, cadenas de la liste des tâches
  (`/production/Task`), badge « sans BPF » du planning de charge (`unreleasedTaskIds`,
  1 requête) et du Gantt.
- Tests : `tests/Feature/Production/ProductionReleaseTest.php`,
  `resources/js/tests/lib/productionRelease.test.js`.

## Journal des consultations (traçabilité des accès, nucléaire)

Les modifications sont tracées par activitylog ; les **lectures** des fiches sensibles le
sont dans `activity_log` avec `log_name = access` (causer, sujet, `properties.route` / `ip`).
- Option `audit.log_reads` (`AUDIT_LOG_READS`, **false** par défaut) : inactive = aucune écriture.
- Middleware `audit.access:{type},{paramètre}` (`LogReadAccess`) posé sur `opportunities.show`,
  `quotes.show`, `orders.show`, `orders.lines.detail.edit`, `files.raw`, `files.download`.
  Écriture dans `terminate()` seulement (GET 2xx, utilisateur connecté), sujet jamais chargé.
  Types autorisés : `AccessLogger::SUBJECTS` (`batch` prêt, aucune fiche lot n'existe encore).
- Dédoublonnage : `Cache::add` par (utilisateur, sujet), fenêtre `audit.dedup_minutes` (10).
- Écran `admin/access-log` + export CSV, permission `audit-access-log` (rôle Admin, migration).
  L'historique partagé (`LogsViewerController`, ouvert à tous) **exclut** `access`.
- Purge : `activitylog:clean` (rétention de `config/activitylog.php`), rien de spécifique.
- Tests : `tests/Feature/Audit/AccessLogTest.php`.

## Motifs de perte (devis, opportunités)

Référentiel `loss_reasons` (écran Méthodes → Motifs de perte, `methods-menu` ; 5 motifs
insérés par la migration, désactivables, jamais supprimés) ; colonnes nullable
`loss_reason_id` + `loss_comment` sur `quotes` et `opportunities`.
- **Règle** (`App\Services\Workflow\LossReasonRecorder`) : motif exigé quand le document
  **entre** en perdu (devis 4, opportunité 5) et qu'au moins un motif est actif. Un document
  déjà perdu reste valide sans motif ; le renseigner après coup (`*.json.loss-reason`) est
  facultatif. Posée sur `quotes.json.statu`, `opportunities.json.statu`, `kanban-move`.
- Refus = 422 portant `loss_reason` (config de la fenêtre) : `resources/js/lib/lossReason.jsx`
  (`sendWithLossReason`) ouvre la fenêtre et renvoie la requête. `ArrowSteps` reçoit la config
  d'avance (`<x-document-header :loss>`) et affiche le motif d'un document perdu.
- Devis perdu → opportunité perdue (`UpdateOpportunityStatus`) avec le motif du devis si
  l'opportunité n'en a pas.
- Indicateur « pertes commerciales » : `QuoteLossStats` (3 requêtes, trames exclues, devis sans
  motif comptés « non renseigné »), carte du tableau de bord de l'index des devis.
- Tests : `tests/Feature/LossReasonTest.php`, `resources/js/tests/components/lossReason.test.jsx`.

## Encours client et relances de factures

Un seul calcul sert aux deux : `App\Services\Receivables\ReceivablesService`.
- **Reste dû d'une facture** = TTC (recalculé depuis les lignes, comme partout) − règlements
  (`invoice_payments`) − avoirs émis sur cette facture (`credit_notes.invoices_id`, hors
  refusés — `config/receivables.php`). Il n'existe pas d'imputation d'avoir : l'avoir corrige
  la facture qu'il cite. Un trop-perçu compte pour zéro.
- **`invoices.statu` n'est jamais lu** : modifiable à la main ou par la PDP, et aveugle aux
  avoirs. Une facture « soldée » au sens du service peut donc rester « en attente ».
- **Encours d'une société** = factures non soldées (brouillons compris : leurs lignes de
  commande ne sont plus « à facturer ») + commandes non facturées si
  `receivables.include_open_orders` (lignes article, `invoiced_remaining_qty`, commandes
  type 1 hors statuts 0/5/6, au `selling_price` stocké). Une requête par appel, y compris
  pour une page entière de sociétés (`exposures()`) — couvert par un test de comptage.
- Seuil « soldée » écrit en littéral dans le SQL (`EPSILON`) : lié en paramètre, SQLite le
  compare comme du texte.

### Plafond d'encours
`companies.credit_limit` (TTC, null = pas de plafond) et `credit_block_level`
(`none` | `warn` | `block`, `App\Enums\CreditBlockLevel`). Une société sans plafond n'est
jamais calculée. `CreditLimitGuard` est appelé à la **création** (il n'existe pas d'étape de
validation) :
- commande manuelle (`orders.json.store`, montant ajouté 0), conversion de devis (montant des
  lignes converties), conversion de pré-commande (formulaire Blade), BL (`deliverys-request.store`
  et `orders.lines.json.store-delivery`, montant ajouté 0 : déjà compté dans la commande) ;
- **non contrôlés** : import CSV, proforma (statut 0, payée avant), commandes internes,
  BL automatique de la facturation directe (bloquer la facturation dégraderait la trésorerie).
- `warn` : le document est créé, avertissement flashé en session (`include/alert-result`).
  `block` : réponse 409 portant `credit_limit` (montants + textes) →
  `resources/js/lib/creditLimit.jsx` ouvre la fenêtre ; un porteur de
  `credit-limit-override` saisit un motif et l'écran renvoie la requête avec
  `credit_override` / `credit_override_reason`. Le passe-droit vaut **pour ce seul document**
  (`credit_limit_overrides`, polymorphe), affiché sur le tableau de bord de la fiche société.
- Le plafond lui-même n'est modifiable qu'avec `credit-limit-override` (`updateJson` retire
  les champs sinon).

### Relances
Écran `invoices/reminders` (React `InvoiceRemindersApp`), lecture pour tout le module
factures, envoi et paramétrage sous `invoice-reminders-send`.
- `invoice_reminder_levels` (3 niveaux insérés par migration : J+1, J+15, J+30, avec leurs
  modèles `email_templates.document_type = invoice-reminder`). Variables remplacées par
  `InvoiceReminderService` uniquement : `{client}`, `{factures}`, `{total_du}`, `{retard}`,
  `{entreprise}`.
- **Niveau proposé** = le plus bas de (niveau atteint par le retard, niveau suivant la
  dernière relance) : jamais de saut de niveau, et une facture déjà relancée au niveau que
  justifie son retard n'est plus proposée.
- Seules les factures `invoice_type = 1`, hors brouillons, échues et non soldées. Proformas
  (pas une créance), avoirs (déjà déduits) et acomptes (aucun code n'en crée) sont exclus.
- **Aucun envoi automatique.** Un e-mail par client (`InvoiceReminderMail`, PDF de chaque
  facture joint, envoi synchrone comme `EmailController`), une ligne `email_logs` **et** une
  ligne `invoice_reminders` par facture. Un e-mail en échec est tracé mais ne fait pas monter
  le niveau. Appel / courrier : relance tracée sans e-mail, qui compte comme une relance.
- Une facture soldée entre l'affichage et l'envoi fait refuser l'envoi (422).
- Une tâche planifiée viendra plus tard : sous Windows en production → Planificateur de
  tâches, pas de cron.
- Tests : `tests/Feature/Receivables/`.

## Nesting (imbrication tôle)

L'imbrication tôle repose **entièrement** sur NestEngine : il n'y a plus de calcul
local (l'ancien shelf packer `nestSheets()` et le parsing DXF/SVG navigateur ont été
retirés de `NestingPage.jsx`).

### Sélection du moteur
- **Open source** (`services.nestengine.enabled` faux) — l'écran garde ses
  paramètres et son bouton « Calculer le besoin », mais le clic affiche l'écran
  « disponible dans la version commerciale » (même présentation que l'outillage
  presse plieuse : `include/commercial-feature-styles.blade.php`, textes
  `commercial.nesting`). `POST /nesting/compute` répond 403.
- **Barres / tubes** : le débit 1D (`nestBars()`, First-Fit Decreasing) reste
  calculé dans le navigateur, mais seulement en version commerciale.
- **NestEngine** (payant, forme exacte) — moteur NFP + algorithme génétique
  déployé localement à côté de Laravel. Actif si :
  ```
  APP_COMMERCIAL=true
  NESTENGINE_URL=http://127.0.0.1:8000
  ```
  `NestingController::dispatchNestEngineJobs()` copie les DXF de la GED dans
  `NESTENGINE_INPUTS_DIR`, POSTe un job v1 par groupe (matière+épaisseur),
  puis le front poll `/nesting/engine/status/{jobId}` toutes les 2 s et
  affiche les preview SVG servies par `/nesting/engine/preview/{jobId}/{n}`.

### Déploiement du service NestEngine local (Windows + NSSM)

Le moteur tourne dans un `venv` Python + uvicorn, encapsulé par NSSM pour
survivre aux redémarrages. Pas de Docker requis (cf. mémoire
`project_windows_prod.md`).

```powershell
# 1. Installation
cd C:\services\NestEngine
python -m venv .venv
.venv\Scripts\pip install -r requirements-api.txt

# 2. NSSM
nssm install NestEngine "C:\services\NestEngine\.venv\Scripts\python.exe"
nssm set NestEngine AppParameters "-m uvicorn api.main:app --host 127.0.0.1 --port 8000"
nssm set NestEngine AppDirectory "C:\services\NestEngine"
nssm set NestEngine AppEnvironmentExtra ^
    NEST_INPUTS_DIR=C:\laragon\www\WebErpMesv2\storage\app\nesting-inputs ^
    NEST_JOBS_DIR=C:\services\NestEngine\jobs ^
    NEST_RETENTION_HOURS=24
nssm start NestEngine
```

Sanity check : `curl http://127.0.0.1:8000/healthz` retourne `{"status":"ok"}`.

### Variables `.env` côté Laravel
| Variable | Défaut | Rôle |
|---|---|---|
| `APP_COMMERCIAL` | `false` | Master switch du mode payant (déjà existant, gate global) |
| `NESTENGINE_URL` | *(vide)* | Point d'entrée HTTP du service NestEngine local |
| `NESTENGINE_TIMEOUT` | `120` | Timeout HTTP (s) — un job peut durer 60 s |
| `NESTENGINE_ROTATIONS` | `36` | Nb d'orientations testées (36 = tous les 10°) |
| `NESTENGINE_SPACING` | `5` | Marge entre pièces (mm, laser kerf compris) |
| `NESTENGINE_MAX_SHEETS` | `50` | Plafond de tôles par job |
| `NESTENGINE_INPUTS_DIR` | `storage/app/nesting-inputs` | Dossier de travail partagé Laravel↔NestEngine |

### Retour arrière et diagnostic
- Si NestEngine est injoignable au moment du POST /compute, la route loggue un
  warning et répond 503 : **plus de repli local**, l'utilisateur voit l'erreur.
- Un groupe tôle qui n'a pas pu partir porte `engine_skipped`
  (`no_cad_file` : aucune pièce avec DXF/SVG — les cotes de ligne ne suffisent pas ;
  `files_unreadable` ; `job_failed`) et s'affiche « Non imbriqué » avec la raison.
- Le tableau « Besoin tôles » est alimenté par le nombre de tôles des jobs terminés
  (format = `sheet_format` du job), puis croisé avec le stock comme avant.
- Tests : `tests/Feature/NestingComputeTest.php`.
- Le format tôle envoyé à NestEngine est résolu par `resolveSheetFormat()` :
  produit stock exact → même matière → plus grande dispo → 3000×1500 en dernier
  recours. À affiner via l'écran stock quand le multi-format (`/v2/jobs`) sera
  branché.

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

### Notes de frais — justificatifs et lecture IA
- **Justificatifs dans la GED**, alias `expense-report` (`UserExpenseReport`), **confidentiel**
  comme le dossier salarié (`FileConfidentiality` : salarié de la note + RH). Rôle
  `justificatif` par défaut. Carte « Justificatifs » séparée du formulaire de dépense.
  L'ancien `user_expenses.scan_file` (stockage `expenses/`) reste lu pour l'existant.
- **Accès** : écran, lignes, anciens scans → salarié de la note ou RH
  (`authorizeExpenseReport`) ; valider / retourner une note → RH uniquement.
- **Lecture IA** (`App\Services\HumanResources\ExpenseReceiptReader`, provider actif de
  l'écran IA, appel direct sans outils comme le compte rendu de visite) : **pré-remplit
  le formulaire, n'enregistre rien**. Photo réduite à 1600 px et redressée (EXIF) ; PDF lu
  par Claude seulement (OVH : image uniquement). Montant = TTC, TVA = taux principal.
- `user_expenses.file_id` relie la ligne à son justificatif ; la liste ne propose que les
  justificatifs pas encore saisis. Tests : `tests/Feature/HumanResources/ExpenseReceipt*`.

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

## Versions et nommage des releases

Chaque release GitHub porte le nom d'un **papillon de jour menacé**, pris **dans l'ordre**
de la Liste rouge UICN France des papillons de jour de métropole
(https://uicn.fr/wp-content/uploads/2012/03/Liste_rouge_France_Papillons_de_jour_de_metropole.pdf),
en commençant par les plus menacés (RE, CR, EN, VU, puis NT).
- Titre : `vX.Y.Z - Genre espèce` (nom latin seul), ex. `v2.0.1 - Parnassius mnemosyne`.
- Dernier utilisé : *Lycaena helle* (v3.0.0). Suivants : *Maculinea alcon*,
  *Polyommatus dorylas*.
- Mettre à jour cette ligne à chaque release, ainsi que `config/app.php` → `version`
  (affichée sur les écrans de connexion).
- Numérotation : correctif seul → Z (2.0.1) ; nouvelles fonctionnalités → Y (2.1.0).
- Note de version en anglais, sur le modèle de v2.0.1 : avertissements de mise à jour
  (migrations, permissions, suppressions) en tête, tableau des avis GHSA, puis nouveautés.

## Dette technique

### 🔴 Exploitation (client 1 en ligne)
- ✅ spatie/laravel-backup en place (`backup:run` / `backup:clean` / `backup:monitor` planifiés)
- Queue worker → Supervisor sur le VPS client : à confirmer + surveiller (jobs en échec,
  `queue:failed` non vide = fonctionnalité silencieusement cassée côté client)
- Vérifier que le cron `schedule:run` tourne bien sur l'instance client (sinon : pas de
  backup, pas de purge RGPD, pas de rapports email auto)
- Restauration de backup jamais testée en réel → à faire une fois sur une copie

### 🔴 Facturation électronique
- **`factory.electronic_address` sans UI** : éditable en base uniquement, contrairement à celui des sociétés clientes
- **`companies.siren` non obligatoire à la saisie** : `FacturXBuilder::assertPartiesAreIdentifiable()` bloque désormais l'émission si SIREN/TVA sont absents ou malformés, mais rien n'empêche encore de créer une société sans ces champs

### 📋 Roadmap

#### Frontend — suite AdminLTE 4
- **Éditeur des modèles d'e-mail** (`admin/templates-index`) : reste une simple zone de texte.
  Les `<x-adminlte-text-editor>` (e-mail, commentaires article / lead / opportunité) utilisent
  Quill 2 (plugin `Quill`, `public/vendor/quill`, chargé par `@section('plugins.Quill', true)`
  sur ces seules vues) ; les `$config` Summernote de ces vues sont ignorés par le composant.
- **Sauvegarde de la disposition du tableau de bord** à revérifier après le passage de
  `react-grid-layout` en 2.3.0 (Dependabot) : l'enregistrement serveur fonctionne, le
  comportement du glisser-redimensionner reste à observer en navigateur.
- **Fichiers morts** : `public/js/{app,bootstrap,node_modules_vue…,three*,OrbitControls,STLLoader}.js`
  et `public/css/app.css` (anciens builds Laravel Mix), composant `components/header-print`.
- **Font Awesome 6 → 7** (non prioritaire) : seule `fa-vector-square`
  (`FileKindResolver::icon()`) disparaît, et la v7 rend toutes les icônes à largeur fixe
  (contrôle visuel). Remplacer `public/vendor/fontawesome-free/{css,webfonts}` par ceux du
  paquet npm, comme pour le passage 5 → 6 (#1189).

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

#### Devis — import DPGF (appels d'offres bâtiment)
Rien n'existe aujourd'hui (ni DPGF, ni BPU/DQE, ni notion de lot). Cible :
- **Import Excel → devis** : lecture PhpSpreadsheet sur le patron `InventoryXlsxImporter`
  (parse → aperçu sans écriture → application en transaction). Détection de la ligne
  d'en-tête et des colonnes N° / Désignation / Unité / Qté / PU HT / Montant, corrigeables
  dans l'aperçu. Classement des lignes : titre de lot → `section`, poste → `article`,
  « Total lot » → `subtotal`, « PM / Inclus / Sans objet » → `text`. N° de poste dans
  `code`. Correspondance d'unités (m², ml, u, ens, ft…) : le seeder ne crée que KG/MM/UNIT.
  DPGF d'origine rangé dans la GED du devis. Classeur multi-onglets : un onglet par lot ou
  onglet choisi.
- **Renvoi du DPGF chiffré au format du maître d'œuvre** (la vraie valeur : l'AO exige son
  fichier, pas notre PDF) : colonne additive nullable `quote_lines.source_ref` (cellule
  d'origine), puis réécriture des PU (et quantités corrigées) dans le classeur d'origine
  sans casser formules ni mise en page. Aucun export XLSX de devis n'existe aujourd'hui.
- **Plus tard** : rapprochement désignation → article / trame avec prix des devis passés,
  classement assisté par IA (`AIGateway`) des structures irrégulières, alerte sur les
  quantités (l'entreprise est responsable de ses quantités dans un DPGF).
- **Contrainte** : sections non imbriquables (pas de parent/niveau sur `quote_lines`) →
  aplatir l'arborescence en sections numérotées + sous-total par lot ; ne pas introduire
  d'imbrication (impact PDF / Factur-X).
- **Bugs de l'import de lignes existant à corriger au passage** (`ImportCsvService`) :
  un `.xlsx` est accepté mais lu par `fgetcsv` (l. 95) ; `array_key_exists($request->x,
  $importData)` teste le n° de colonne contre la liste des lignes au lieu de la ligne
  courante (lignes de devis l. 658-664, commandes l. 734, sociétés l. 378-386).

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
- `order_lines` : la migration de passage en décimal (`2026_04_21_000004`) a fait tomber le
  `default(0)` des quantités (`->change()` ne reprend pas les modificateurs). Invisible en
  production (MySQL `strict => false` met 0), mais toute insertion qui omet `delivered_qty` /
  `invoiced_qty` échoue sur une base stricte (SQLite des tests). Rétablir les défauts par une
  migration additive.
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
