# ΣEM - L'ERP/MES dédié à la Tolerie & Mécanique Industrielle 🏭

<p align="center">
  <a href="https://github.com/SMEWebify/WebErpMesv2/blob/WEM-2.0/README.md">English</a> •
  <a href="https://github.com/SMEWebify/WebErpMesv2/blob/WEM-2.0/docs/README.fr.md">Français</a>
</p>

<p align="center">
  <a href="https://github.com/SMEWebify/WebErpMesv2/stargazers"><img src="https://img.shields.io/github/stars/SMEWebify/WebErpMesv2?style=social" alt="Stars"></a>
  <a href="https://github.com/SMEWebify/WebErpMesv2/network/members"><img src="https://img.shields.io/github/forks/SMEWebify/WebErpMesv2?style=social" alt="Forks"></a>
  <a href="https://github.com/SMEWebify/WebErpMesv2/issues"><img src="https://img.shields.io/github/issues/SMEWebify/WebErpMesv2" alt="Issues"></a>
  <a href="https://github.com/SMEWebify/WebErpMesv2/blob/WEM-2.0/LICENSE"><img src="https://img.shields.io/github/license/SMEWebify/WebErpMesv2" alt="License"></a>
</p>

![image](https://github.com/SMEWebify/WebErpMesv2/assets/75578469/bcc022c1-465e-44fb-a7ce-011f9096eba7)

## 🎯 Pourquoi ΣEM pour votre atelier ?

Conçu **par des professionnels de la tolerie et mécanique, pour des professionnels**, ΣEM répond aux défis spécifiques de votre métier :

### 🔧 Votre activité
- **Tolerie industrielle** : pliage, découpe laser/plasma, poinçonnage, soudure
- **Mécanique de précision** : tournage, fraisage, rectification
- **Fabrication de moules** : conception, usinage, ajustage
- **Sous-traitance industrielle** : gestion multi-clients, séries variées

### 💡 Vos problématiques quotidiennes résolues

| Problème | Solution ΣEM |
|----------|-------------|
| 📋 **Devis complexes** avec nombreuses opérations | Nomenclatures (BOM) détaillées, gammes d'usinage, calcul automatique temps/coûts |
| ⏱️ **Suivi production** en temps réel impossible | Tableau de bord live, pointage atelier, avancement par OF |
| 📦 **Gestion matières premières** (tôles, barres, tubes) | Stock par dimensions, traçabilité matière, alertes réapprovisionnement |
| 🔄 **Planification atelier** chaotique | Planning visuel par machine, gestion priorités, charge machines |
| 📊 **Rentabilité par projet** floue | Suivi coûts réels vs prévisionnels, analyse marges par commande |
| 🚚 **Traçabilité client/fournisseur** limitée | Historique complet, documents attachés, notes techniques |

## ✨ Fonctionnalités métier spécialisées

### 🏭 Module Production (MES)
- **Gammes d'usinage** : définir les opérations (découpe, pliage, soudure, usinage...)
- **Nomenclatures (BOM)** : matières premières, composants, sous-ensembles
- **Planning atelier** : visualisation charge par machine/poste
- **Ordres de fabrication** : génération automatique depuis devis
- **Pointage production** : suivi temps réel par opération
- **Contrôle qualité** : fiches de contrôle, non-conformités

### 📋 Gestion Commerciale
- **Devis détaillés** : lignes multiples, options, variantes
- **Calcul coûts** : matière + main d'œuvre + sous-traitance
- **Suivi affaires** : du lead jusqu'à la livraison
- **Gestion multi-devises** et multi-langues
- **Historique client** : toutes les commandes en un clin d'œil

### 📦 Stocks & Approvisionnement
- **Gestion par dimensions** : Tôle 2000x1000x3mm, Tube Ø50x3...
- **Traçabilité matière** : numéros de coulée, certificats matière
- **Mouvements stocks** : entrées, sorties, transferts, inventaires
- **Alertes** : seuils mini, réapprovisionnement automatique
- **Fournisseurs** : tarifs, délais, évaluation

### 💰 Comptabilité & Facturation
- **Facturation** : acomptes, situations, avoirs
- **TVA** : gestion multi-taux, déclarations
- **Paiements** : suivi règlements, relances
- **Analytique** : rentabilité par projet, client, période

### 🗂️ Gestion documentaire avec visionneuses CAO intégrées
- **Pièces jointes unifiées** : produits, devis, commandes, bons de livraison, factures,
  achats, non-conformités, mouvements de stock, sociétés et opportunités partagent le même
  espace de fichiers
- **Stockage privé** : les fichiers sont hors de la racine web et servis par des routes authentifiées
- **Visionneuses chargées à la demande** (ouvrir un PDF ne télécharge pas le moteur 3D) :
  - **Maillages 3D** : STL, OBJ, PLY, 3MF, glTF (three.js)
  - **CAO 3D** : STEP, IGES, BREP via OpenCascade compilé en WebAssembly — aucun convertisseur serveur
  - **CAO 2D** : DXF, plus PDF, images et tableurs

### 🔧 Maintenance (GMAO) & atelier
- **Plans de maintenance et ordres de travail** par machine
- **Alertes Andon** : escalade temps réel depuis l'atelier
- **Rapports atelier** : temps pointé vs estimé, rebuts, charge machine
- **Tableaux Kanban et GTD**, diagramme de Gantt, planning de charge
- **Imbrication (nesting)** : plans d'imbrication attachés aux lignes de commande
- **Suivi des présences** et de la consommation énergétique

### ✅ Qualité, QHSE & conformité
- **Non-conformités**, projets d'inspection, planificateur d'audits, AMDEC
- **Étalonnage des appareils de contrôle** avec alertes automatiques
- **QHSE** : incidents, risques, formations, conformités réglementaires
- **RGPD** : suppressions logiques, journal d'activité, service d'anonymisation, export et
  effacement des données, purge hebdomadaire automatique, demandes en self-service

### 🔌 Intégrations & automatisation
- **Facturation électronique** : génération Factur-X / EN 16931, passerelle PDP (driver Qonto)
  et lecture des factures fournisseurs Factur-X entrantes
- **Export FEC** pour la comptabilité française
- **Nest2Prod** : envoi des commandes et synchronisation du stock tôles
- **Import d'utilisateurs LDAP / Active Directory**
- **API REST** (Laravel Sanctum) et assistant IA intégré
- **Sauvegardes automatiques** (spatie/laravel-backup) et supervision **Laravel Pulse**


## 💼 Cas d'usage sectoriels

### Exemple 1 : Atelier de tolerie fine
**Contexte** : 15 personnes, pliage + découpe laser + soudure

**Utilisation ΣEM** :
- Devis avec gammes détaillées (laser → ébavurage → pliage → soudure)
- Planification sur 3 plieuses et 2 lasers
- Gestion stock tôles par format et épaisseur
- Suivi temps réel des OF en cours

**Résultat** : +30% productivité planning, -20% stocks dormants

### Exemple 2 : Mécanique de précision
**Contexte** : Sous-traitance automobile, séries moyennes

**Utilisation ΣEM** :
- Nomenclatures avec matières premières certifiées
- Gammes d'usinage (tournage → fraisage → traitement thermique → rectif)
- Contrôle qualité intégré (fiches de contrôle par OF)
- Traçabilité complète matière → pièce finie

**Résultat** : Conformité ISO 9001, traçabilité parfaite

### Exemple 3 : Fabricant de moules
**Contexte** : Moules injection plastique, projets complexes

**Utilisation ΣEM** :
- Devis multi-phases (étude, ébauche, finition, essais)
- Gestion sous-traitance (traitements thermiques, polissage)
- Suivi rentabilité projet en temps réel
- Documentation technique centralisée

**Résultat** : Meilleure maîtrise coûts, délais respectés


## 🎬 Voir ΣEM en action

### 📺 Démo en ligne
**[Tester la démo](http://demo.wem-project.org)**

- **Email** : contact@wem-project.org 
- **Mot de passe** : password

### 🎥 Vidéo de présentation

https://github.com/user-attachments/assets/200e1322-ae60-4270-aa9c-0a28e5ca737a

<img width="1877" height="831" alt="image" src="https://github.com/user-attachments/assets/21b92345-46ad-4af2-9f3a-b38d601eb091" />


### Installation

## 🚀 Installation rapide

### Option 1 : Docker (Recommandé) 🐳

Démarrage en **3 minutes** :

```bash
git clone https://github.com/SMEWebify/WebErpMesv2.git
cd WebErpMesv2
docker compose up --build
```

➡️ Accédez à http://localhost:45060

### Option 2 : Installation locale 💻

```bash
# Cloner et configurer
git clone https://github.com/SMEWebify/WebErpMesv2.git
cd WebErpMesv2
cp .env.example .env

# Installer les dépendances
composer install
npm install

# Configurer l'application
php artisan key:generate
php artisan migrate --seed

# Lancer
npm run dev
php artisan serve
```

➡️ Accédez à http://localhost:8000

> 📚 **Guides détaillés** : 
> - [Development setup](https://github.com/SMEWebify/WebErpMesv2/wiki/Installation-Steps-(for-dev))
> - [Production deployment](https://github.com/SMEWebify/WebErpMesv2/wiki/Installation-Steps-(for-production))

### ⚙️ Post-Installation Configuration

**Important**: Before adding lines to a quote, configure:

1. **Default VAT**: Go to **Accounting → VAT** and mark an item as default
2. **Default Unit**: Go to **Methods → Units** and mark an item as default

Without these settings, you cannot add lines to quotes.

<img width="831" alt="Configuration screenshot" src="https://github.com/user-attachments/assets/f527881c-a7c4-460a-9b06-f647c91402d8" />

## 🏗️ Architecture technique

### Stack technologique moderne
```
WebErpMesv2/
├── app/
│   ├── Http/Controllers/    # Contrôleurs web et API (devis, production, CRM)
│   ├── Models/              # Entités métier (commandes, produits, stocks)
│   └── Services/            # Logique métier (stock, fichiers, intégrations, RGPD)
├── database/
│   └── migrations/          # Database schemas (BOMs, routings, stock movements)
├── resources/
│   ├── js/                  # Composants React
│   └── views/               # Templates Blade
├── tests/                   # Test suite
└── docker/                  # Docker configuration
```

**Technologies clés** :
- **Backend** : Laravel 12, PHP 8.2+
- **Frontend** : React 19 (composants riches), Blade (layout/shell), Alpine.js (micro-interactions)
- **Bundler** : Vite
- **CSS** : Bootstrap 5 / AdminLTE 4
- **Base de données** : MySQL/PostgreSQL
- **Cache & files d'attente** : Redis
- **Temps réel** : Laravel Echo
- **Visionneuses 2D/3D** : three.js, occt-import-js (OpenCascade WASM), dxf-parser
- **DevOps** : Docker, Nginx

> Livewire et Vue.js ont été supprimés : les écrans riches sont des composants React montés
> depuis des vues Blade servant de coquille.

### 🧪 Tests

Run the complete test suite:

```bash
php artisan test
```

Run specific tests:

```bash
php artisan test --filter TestName
```

## 🛠️ Custom Artisan Commands (Commandes Artisan spécifiques)

These commands are defined in this repository and complement the default Laravel tooling.

| Command | Description | Example |
| --- | --- | --- |
| `php artisan wem:diagnostics` | Vérifie les prérequis de l'environnement (version PHP, extensions, clé applicative, permissions cache/storage, Redis, base, diffusion). | `php artisan wem:diagnostics` |
| `php artisan wem:files:import` | Migration unique des pièces jointes historiques de `public/` vers le stockage privé. `--dry-run`, `--skip-move`. | `php artisan wem:files:import --dry-run` |
| `php artisan wem:n2p:push-order {orderId} {--sync}` | Pousse une commande vers Nest2Prod (`--sync` contourne la file d'attente). | `php artisan wem:n2p:push-order 123 --sync` |
| `php artisan wem:n2p:sync-sheet-stock` | Synchronise le stock tôles avec Nest2Prod. `--days=30`, `--sync`. | `php artisan wem:n2p:sync-sheet-stock --days=7` |
| `php artisan preorders:scan-output` | Scanne le dossier de sortie et importe les CSV comme pré-commandes. `--path=`, `--pattern=`, `--done-path=`. | `php artisan preorders:scan-output` |
| `php artisan stock:recalculate-cump` | Recalcule tout l'historique du CUMP pour chaque emplacement produit. `--dry-run`. | `php artisan stock:recalculate-cump --dry-run` |
| `php artisan stock:rebuild-reservations` | Reconstruit les réservations de stock des composants achetés. `--product=`. | `php artisan stock:rebuild-reservations` |
| `php artisan quality:dispatch-calibration-alerts` | Notifie les responsables des appareils de contrôle à étalonner. | `php artisan quality:dispatch-calibration-alerts` |
| `php artisan emails:send-auto-reports` | Envoie les rapports email automatiques selon les préférences utilisateur. | `php artisan emails:send-auto-reports` |
| `php artisan ldap:import-users` | Importe les utilisateurs LDAP dans la base Laravel. | `php artisan ldap:import-users` |
| `php artisan rgpd:export-contact {id}` | Exporte au format JSON toutes les données détenues sur un contact (art. 20 RGPD). | `php artisan rgpd:export-contact 42` |
| `php artisan rgpd:erase-contact {id}` | Traite une demande d'effacement RGPD pour un contact B2B. | `php artisan rgpd:erase-contact 42` |
| `php artisan rgpd:purge` | Purge les données personnelles au-delà de leur durée de conservation. | `php artisan rgpd:purge` |
| `php artisan wem:nesting:seed-svg` | Attache des vecteurs d'imbrication de démonstration aux lignes de commande. `--count=`, `--force`. | `php artisan wem:nesting:seed-svg` |
| `php artisan loadtest:seed` | Génère un jeu de données réaliste pour les tests de charge. `--scale=`. | `php artisan loadtest:seed --scale=0.5` |

### ⏱️ Tâches planifiées & file d'attente

Le planificateur (`routes/console.php`) déclenche les sauvegardes quotidiennes (`backup:run`,
`backup:clean`, `backup:monitor`), la purge RGPD hebdomadaire (`rgpd:purge`) et le nettoyage
mensuel du journal d'activité. À déclarer une fois sur le serveur :

```
* * * * * php /chemin/vers/artisan schedule:run >> /dev/null 2>&1
```

Un worker de file d'attente est nécessaire pour les traitements asynchrones (emails,
intégrations, exports) :

```bash
php artisan queue:work redis --sleep=3 --tries=3
```

> Note : ne pas exécuter `php artisan route:cache` — la locale est figée au moment de la mise
> en cache, ce qui casse les routes localisées.

## 🤝 Contribuer au projet

ΣEM est **open source** ! Votre expertise métier est précieuse.

### 🌟 Comment aider ?

**Développeurs** :
- Corriger des bugs → [`good first issue`](https://github.com/SMEWebify/WebErpMesv2/labels/good%20first%20issue)
- Ajouter des fonctionnalités → voir [Roadmap](../ROADMAP.md)
- Améliorer la documentation → [Contributing Guide](../CONTRIBUTING.md)

**Professionnels du secteur** :
- Tester et donner du feedback
- Proposer des améliorations métier
- Partager vos cas d'usage
- Traduire l'interface

### Development Workflow

```bash
# Fork the repository and clone it
git clone https://github.com/YOUR_USERNAME/WebErpMesv2.git

# Create a feature branch
git checkout -b feature/amazing-feature

# Make your changes and commit
git commit -m "Add amazing feature"

# Push and create a Pull Request
git push origin feature/amazing-feature
```

## 👥 Contributeurs

Merci à tous ceux qui font vivre ce projet !

<table>
  <tr>
    <td align="center">
      <a href="https://github.com/SMEWebify">
        <img src="https://github.com/SMEWebify.png" width="100px;" alt="SMEWebify"/>
        <br />
        <sub><b>SMEWebify</b></sub>
      </a>
      <br />
      <sub>Creator & Lead Maintainer</sub>
      <br />
      <sub>1,225+ commits</sub>
    </td>
    <td align="center">
      <a href="https://github.com/sunxiaoguang">
        <img src="https://github.com/sunxiaoguang.png" width="100px;" alt="sunxiaoguang"/>
        <br />
        <sub><b>sunxiaoguang</b></sub>
      </a>
      <br />
      <sub>Core Contributor</sub>
      <br />
      <sub>11 commits</sub>
    </td>
    <td align="center">
      <a href="https://github.com/saosangmo">
        <img src="https://github.com/saosangmo.png" width="100px;" alt="saosangmo"/>
        <br />
        <sub><b>saosangmo</b></sub>
      </a>
      <br />
      <sub>Active Contributor</sub>
      <br />
      <sub>8 commits</sub>
    </td>
    <td align="center">
      <a href="https://github.com/RobertoBochet">
        <img src="https://github.com/RobertoBochet.png" width="100px;" alt="RobertoBochet"/>
        <br />
        <sub><b>RobertoBochet</b></sub>
      </a>
      <br />
      <sub>Contributor</sub>
      <br />
      <sub>3 commits</sub>
    </td>
  </tr>
  <tr>
    <td align="center">
      <a href="https://github.com/globalcitizen">
        <img src="https://github.com/globalcitizen.png" width="100px;" alt="globalcitizen"/>
        <br />
        <sub><b>globalcitizen</b></sub>
      </a>
      <br />
      <sub>Contributor</sub>
      <br />
      <sub>1 commit</sub>
    </td>
    <td align="center">
      <a href="https://github.com/nedlir">
        <img src="https://github.com/nedlir.png" width="100px;" alt="nedlir"/>
        <br />
        <sub><b>nedlir</b></sub>
      </a>
      <br />
      <sub>Contributor</sub>
    </td>
    <td align="center">
      <a href="https://github.com/SMEWebify/WebErpMesv2/graphs/contributors">
        <img src="https://via.placeholder.com/100x100/4a5568/ffffff?text=%2B1" width="100px;" alt="More contributors"/>
        <br />
        <sub><b>+1 more</b></sub>
      </a>
      <br />
      <sub><a href="https://github.com/SMEWebify/WebErpMesv2/graphs/contributors">See all →</a></sub>
    </td>
  </tr>
</table>

### Want to be featured here?

Check our [Contributing Guide](../CONTRIBUTING.md) and make your first contribution!


## 📊 Project Stats

- ⭐ **180+** Stars
- 🍴 **88** Forks
- 👥 **7+** Active Contributors
- 📝 **2 200+** Commits
- 🎉 **21** Releases (dernière : v1.19)
- 🧪 **60+** fichiers de tests PHPUnit
- 📦 **Open Source** sous licence MIT

## 📚 Documentation

- 📖 [User Guide](https://github.com/SMEWebify/WebErpMesv2/wiki)
- 🔧 [Development Setup](https://github.com/SMEWebify/WebErpMesv2/wiki/Installation-Steps-(for-dev))
- 🚀 [Production Deployment](https://github.com/SMEWebify/WebErpMesv2/wiki/Installation-Steps-(for-production))
- 🏗️ [Architecture Overview](../ARCHITECTURE.md)
- 🤝 [Contributing Guide](../CONTRIBUTING.md)
- 🔒 [Security Policy](../SECURITY.md)

## 🗺️ Roadmap

Consultez la [roadmap](../ROADMAP.md) pour voir ce qui arrive et comment aider !

**Priorités actuelles :**
- 🧪 Amélioration de la couverture de tests (règles métier backend ; pas encore de tests front)
- 📚 Documentation complète de l'API ([docs/API.md](API.md))
- 📦 Stock : courbe de stock projeté, écran d'inventaire physique, alertes de réappro automatiques
- 🐳 Déploiement Docker multi-clients (1 conteneur + 1 base par client)

## 💬 Support & Communauté

- 💭 [Discussions GitHub](https://github.com/SMEWebify/WebErpMesv2/discussions) - Questions, idées, retours
- 🐛 [Issue Tracker](https://github.com/SMEWebify/WebErpMesv2/issues) - Bugs et demandes de fonctionnalités
- 📧 [Email](mailto:contact@wem-project.org) - Support direct
- 🌐 [Démo en ligne](http://demo.wem-project.org) - Tester gratuitement

## 📄 Licence

Projet sous licence **MIT** - Voir [LICENSE](../LICENSE)

Vous êtes libre de :
- ✅ Utiliser commercialement
- ✅ Modifier le code
- ✅ Distribuer
- ✅ Utiliser en privé

## 🙏 Remerciements

Merci à :
- Tous nos [contributeurs](https://github.com/SMEWebify/WebErpMesv2/graphs/contributors)
- Les ateliers qui testent et donnent leur feedback
- Les communautés Laravel et React
- Tous ceux qui ont ⭐ starred le projet

---

## 🏭 Développé pour l'industrie, par l'industrie

ΣEM est né de l'expérience terrain en tolerie industrielle. Chaque fonctionnalité répond à un besoin réel d'atelier.

**Vous êtes tôlier, mécanicien, usineur ?**  
Votre retour est précieux pour améliorer l'outil → [Contactez-nous](mailto:contact@wem-project.org)

**Vous êtes développeur passionné d'industrie ?**  
Rejoignez-nous → [Guide de contribution](../CONTRIBUTING.md)

---

<p align="center">
  <b>Fait avec ❤️ pour les professionnels de la tolerie et de la mécanique</b>
  <br />
  <br />
  <a href="https://github.com/SMEWebify/WebErpMesv2/stargazers">⭐ Star le projet</a> •
  <a href="https://github.com/SMEWebify/WebErpMesv2/fork">🍴 Fork</a> •
  <a href="CONTRIBUTING.md">🤝 Contribuer</a> •
  <a href="http://demo.wem-project.org">🎬 Tester la démo</a>
</p>
