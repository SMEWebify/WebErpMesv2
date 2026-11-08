# Conservation des enregistrements (ISO 19443)

Un client du nucléaire doit pouvoir relire ses preuves (visas, contrôles, dérogations,
historique des modifications) pendant toute la vie de l'installation. Deux tâches
planifiées suppriment des données ; voici ce qu'elles gardent.

## Journal d'activité — `wem:activitylog:clean` (mensuel)

Chaque journal (`log_name` de la table `activity_log`) a sa propre durée, en jours :

| Journal | Contenu | Variable `.env` | Défaut |
|---|---|---|---|
| `access` | Consultations des fiches sensibles (`AUDIT_LOG_READS`) | `ACTIVITYLOG_ACCESS_RETENTION_DAYS` | 365 |
| tous les autres | Modifications, visas, anonymisations RGPD | `ACTIVITYLOG_RETENTION_DAYS` | vide = jamais purgé |

```bash
php artisan wem:activitylog:clean --dry-run   # compte ce qui serait purgé
php artisan wem:activitylog:clean
```

Après un changement de variable : `php artisan config:cache`.

> ⚠️ Ne pas lancer ni replanifier `php artisan activitylog:clean` (commande du paquet) : elle
> purge tous les journaux sans distinction. Sa durée est portée à 100 ans pour qu'un lancement
> par erreur ne supprime rien.

## Purge RGPD — `rgpd:purge` (hebdomadaire)

Les utilisateurs, sociétés, contacts et adresses supprimés depuis plus de 90 jours sont
supprimés définitivement, **sauf** s'ils sont cités par un enregistrement à conserver :

- **Utilisateur** : auteur d'un visa (revue de contrat, BPF, visa de libération, contrôle de
  réception, visa documentaire, validation de dossier, de dérogation, d'avoir, d'inventaire,
  signatures du rapport premier article, passe-droits…), auteur d'un document (devis,
  commande, BL, facture, achat, NC…) ou d'une entrée du journal d'activité.
  → **anonymisé** (nom « Utilisateur #12 », e-mail désactivé, données personnelles effacées,
  jetons d'API révoqués) et laissé supprimé (soft-delete). Il ne peut plus se connecter ; ses
  visas restent attribués.
- **Contact** cité par un document ou une dérogation → anonymisé et conservé.
- **Société / adresse** citée par un document, une NC ou une dérogation → conservée telle
  quelle (une société est aussi conservée si un de ses contacts ou adresses l'est).

Les autres sont supprimés comme avant. La sortie de la commande indique, par type, combien
ont été supprimés et combien conservés.

La liste des colonnes contrôlées est dans `App\Services\Rgpd\RecordRetention`.

## Ce qui n'est pas conservé

Les données purement personnelles d'un salarié parti (pointages, notes de frais, soldes de
congés, formations, humeurs) suivent son compte : elles sont supprimées avec lui quand il
n'est cité par aucun enregistrement, et conservées avec lui sinon.

Les e-mails envoyés (`email_logs`) restent purgés au bout d'un an.
