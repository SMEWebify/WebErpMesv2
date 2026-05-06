# Système de sauvegarde — spatie/laravel-backup

## Vue d'ensemble

Chaque instance client sauvegarde automatiquement sa propre base de données et ses fichiers vers le disque local du serveur. La migration vers OVH Object Storage est prévue en Phase 2.

```
Instance Laravel
  ├── DB dump (MySQL, gzip)     ┐
  └── storage/app (PDFs, docs)  ┘ → storage/app/backups/{APP_NAME} [DB_DATABASE]/
```

---

## Ce qui est sauvegardé

| Source | Contenu |
|--------|---------|
| Base de données | Dump MySQL compressé gzip |
| `storage/app` | PDFs (devis, BL, factures), fichiers importés, documents clients |

**Non inclus** (inutile — reconstituable) :
- `vendor/` → `composer install`
- `node_modules/` → `npm install`
- Le code source → git

---

## Planification (routes/console.php)

| Heure | Commande | Rôle |
|-------|----------|------|
| 01h00 | `backup:clean` | Supprime les anciennes sauvegardes selon la rétention |
| 02h00 | `backup:run` | Crée la sauvegarde du jour |
| 09h00 | `backup:monitor` | Vérifie la santé, alerte si backup > 2 jours |

Le scheduler Laravel doit être activé via cron sur le serveur :
```bash
* * * * * cd /var/www/html && php artisan schedule:run >> /dev/null 2>&1
```

---

## Rétention (config/backup.php)

| Période | Politique |
|---------|-----------|
| 7 premiers jours | Tous les backups conservés |
| Jusqu'à 30 jours | 1 backup par jour |
| Jusqu'à 12 semaines | 1 backup par semaine |
| Jusqu'à 12 mois | 1 backup par mois |
| Jusqu'à 2 ans | 1 backup par an |
| Limite disque | 5 000 MB max — supprime les plus anciens au-delà |

---

## Notifications mail

Toutes les alertes sont envoyées à **contact@nest2prod.com**.

Le sujet identifie l'instance par `APP_NAME` + `DB_DATABASE` :

```
[OK]     Sauvegarde réussie — Nest2Prod ERP [client_tolerie] (production)
[ERREUR] Sauvegarde échouée — Nest2Prod ERP [client_usinage] (production)
[ALERTE] Sauvegardes corrompues — Nest2Prod ERP [client_moule] (production)
[OK]     Nettoyage des sauvegardes réussi — ...
[OK]     Sauvegardes saines — ...
```

Les traductions sont dans `resources/lang/vendor/backup/fr/notifications.php`.

---

## Variables .env par instance

```env
APP_NAME=Nest2Prod ERP        # Apparaît dans le sujet du mail
DB_DATABASE=nom_db_client     # Identifie l'instance dans le sujet du mail
BACKUP_ARCHIVE_PASSWORD=      # Optionnel — chiffre l'archive ZIP (AES-256)
```

---

## Stockage des sauvegardes

### Phase 1 — Local (actuel)

Les sauvegardes sont stockées dans `storage/app/backups/`.

Disk Laravel : `backup` (défini dans `config/filesystems.php`)

```
storage/app/backups/
└── Nest2Prod ERP [client_tolerie]/
    ├── 2026-05-06-02-00-00.zip
    ├── 2026-05-05-02-00-00.zip
    └── ...
```

### Phase 2 — OVH Object Storage

1. Créer un bucket OVH Object Storage (région GRA ou SBG — EU/RGPD)
2. Remplir les variables dans le `.env` de l'instance :

```env
OVH_S3_KEY=xxxxxxxxxxxxxxxx
OVH_S3_SECRET=xxxxxxxxxxxxxxxx
OVH_S3_REGION=gra
OVH_S3_BUCKET=nom-du-bucket
OVH_S3_ENDPOINT=https://s3.gra.io.cloud.ovh.net
```

3. Ajouter `'ovh'` dans `config/backup.php` :

```php
'destination' => [
    'disks' => [
        'backup',   // local — conserver en transit
        'ovh',      // distant — destination principale
    ],
],
```

4. Mettre à jour `monitor_backups` dans `config/backup.php` :

```php
'disks' => ['backup', 'ovh'],
```

---

## Tester manuellement

```bash
# Backup base de données uniquement (rapide)
php artisan backup:run --only-db

# Backup complet (DB + fichiers)
php artisan backup:run

# Vérifier l'état des sauvegardes
php artisan backup:monitor

# Lister les sauvegardes existantes
php artisan backup:list

# Nettoyer selon la rétention
php artisan backup:clean
```

---

## Restaurer une sauvegarde

1. Localiser l'archive dans `storage/app/backups/{nom}/` (ou sur OVH)
2. Dézipper l'archive (mot de passe dans `BACKUP_ARCHIVE_PASSWORD` si chiffré)
3. Restaurer la base :

```bash
mysql -u root -p nom_db < db-dumps/mysql-nom_db.sql.gz | gunzip
# ou si non compressé :
mysql -u root -p nom_db < db-dumps/mysql-nom_db.sql
```

4. Restaurer les fichiers :

```bash
cp -r storage/app/* /var/www/html/storage/app/
```

> Tester une restauration complète avant la mise en production.

---

## Dépendances

- Package : `spatie/laravel-backup` v9.x (PHP 8.2 compatible)
- PHP 8.3+ → migrer vers v10.x
- Aucune dépendance externe requise en Phase 1
- Phase 2 OVH : `league/flysystem-aws-s3-v3` (compatible S3)
