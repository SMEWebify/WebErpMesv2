# Registre des traitements (RGPD, art. 30) — modèle

Ce registre est tenu par **l'entreprise qui exploite l'instance**, responsable du traitement.
L'éditeur fournit ce modèle pré-rempli avec ce que fait l'ERP ; l'entreprise le complète
(champs `[à compléter]`), retire les traitements des modules qu'elle n'utilise pas et le garde
à jour. Il n'est pas transmis à la CNIL, mais doit pouvoir lui être présenté sur demande.

L'information des personnes (art. 13-14) est la page **RGPD** de l'application
(`/rgpd-policy`) : la garder cohérente avec ce registre.

## Responsable du traitement

| | |
|---|---|
| Raison sociale | [à compléter] |
| Adresse | [à compléter] |
| SIREN | [à compléter] |
| Représentant légal | [à compléter] |
| Référent RGPD / DPO (le cas échéant) | [à compléter] |
| Contact pour l'exercice des droits | [à compléter] |
| Date de création du registre | [à compléter] |
| Dernière mise à jour | [à compléter] |

## Mesures de sécurité communes à tous les traitements

- Instance et base de données propres à l'entreprise, aucun partage avec d'autres instances.
- Accès par compte nominatif, mots de passe hashés (bcrypt), droits par rôle et par permission.
- Données sensibles des salariés chiffrées en base (NSS, pièce d'identité, permis, adresse,
  téléphones, date de naissance, e-mail privé), ainsi que les clés des services connectés.
- Fichiers stockés hors de la racine web, servis aux seuls utilisateurs autorisés ; dossier
  salarié et notes de frais lisibles par le salarié et les RH seulement.
- Journal des modifications ; journal des consultations des fiches sensibles (option
  `AUDIT_LOG_READS`).
- HTTPS. Sauvegarde quotidienne de la base et des fichiers (`backup:run`), surveillée.
- [à compléter : hébergeur, sauvegarde externalisée, poste d'administration, etc.]

## Sous-traitants possibles

À ne garder que s'ils sont activés sur l'instance. Vérifier le DPA de chacun.

| Service | Traitements concernés | Données transmises | Transfert hors UE |
|---|---|---|---|
| Hébergeur du serveur [à compléter] | Tous | Toutes | [à compléter] |
| Serveur d'envoi d'e-mails [à compléter] | 1, 2, 3 | Destinataire, contenu des documents | [à compléter] |
| Stockage objet S3 (option) | Tous | Fichiers, sauvegardes | Selon fournisseur |
| Anthropic — Claude (option IA) | 1, 6 | Texte saisi / dicté, image d'un justificatif ou d'une zone de plan | **Oui** (États-Unis) |
| OVHcloud AI Endpoints (option IA) | 1, 6 | Texte, image, audio des mémos vocaux (non conservé) | Non (France) |
| DocuSign (option) | 1, 7 | Document à signer, nom et e-mail du signataire | Selon compte |
| Microsoft 365 / SharePoint (option) | 1, 2, 3, 7 | Fichiers de la GED (jamais le dossier salarié ni les notes de frais) | Selon tenant |
| Plateforme de dématérialisation (PDP, option) | 2 | Factures, coordonnées des parties | Selon plateforme |
| API Recherche d'entreprises (État, option) | 1 | Texte recherché (SIRET, nom) | Non |
| Configurateurs, simulation de pliage, MES (options) | 1, 7 | Article, quantité, fichier, nom du client ; OF et opérateurs | Selon prestataire |

## Traitements

### 1. Gestion commerciale (clients, prospects, devis, commandes)

| | |
|---|---|
| Finalité | Prospection B2B, devis, commandes, livraisons, relation client, portail client |
| Base légale | Exécution du contrat ; intérêt légitime (prospection B2B) |
| Personnes concernées | Contacts des clients et prospects |
| Données | Nom, prénom, fonction, e-mail et téléphone professionnels, adresse de l'entreprise ; e-mails reçus (demandes entrantes) ; photos et notes de visite sur site |
| Destinataires internes | Services commercial, ADV, direction [à ajuster] |
| Durée | Clients : relation commerciale + 3 ans ; prospects : 3 ans après le dernier contact. **Suppression manuelle** par l'entreprise, puis effacement par `rgpd:purge` au bout de 90 jours (anonymisation si la personne est citée par un document) |

### 2. Facturation et comptabilité

| | |
|---|---|
| Finalité | Factures, avoirs, relances, encours client, facturation électronique, export comptable (FEC) |
| Base légale | Obligation légale ; exécution du contrat |
| Personnes concernées | Contacts des clients |
| Données | Identité et coordonnées professionnelles, montants, règlements, historique des relances |
| Destinataires | Comptabilité, expert-comptable [à compléter], plateforme de dématérialisation, administration fiscale |
| Durée | 10 ans (Code de commerce, art. L.123-22) ; jamais supprimées par l'application. E-mails envoyés : 12 mois |

### 3. Achats et fournisseurs

| | |
|---|---|
| Finalité | Demandes de prix, commandes d'achat, réceptions, évaluation des fournisseurs |
| Base légale | Exécution du contrat ; intérêt légitime (qualification fournisseurs) |
| Personnes concernées | Contacts des fournisseurs |
| Données | Identité et coordonnées professionnelles, notes d'évaluation |
| Destinataires | Achats, qualité, comptabilité |
| Durée | Relation commerciale ; pièces comptables 10 ans |

### 4. Gestion du personnel

| | |
|---|---|
| Finalité | Dossier salarié, contrats, congés et absences, formations et habilitations, export vers la paie |
| Base légale | Exécution du contrat de travail ; obligation légale |
| Personnes concernées | Salariés, intérimaires [à ajuster] |
| Données | Identité, coordonnées personnelles (chiffrées), NSS et pièce d'identité (chiffrés), situation familiale, dates d'entrée / sortie, matricule de paie, absences, formations, documents du dossier salarié |
| Destinataires | RH, responsable hiérarchique (notification des absences), cabinet de paie [à compléter] |
| Durée | 5 ans après la fin du contrat [à ajuster selon les pièces : bulletins de paie 5 ans côté employeur]. **Suppression manuelle** du compte, puis `rgpd:purge` (anonymisation si le salarié est cité par un document ou un visa) |

### 5. Temps de présence et pointage

| | |
|---|---|
| Finalité | Pointage des entrées / sorties, temps passé sur les tâches de production, export paie |
| Base légale | Obligation légale (décompte du temps de travail) ; intérêt légitime (suivi de production) |
| Personnes concernées | Salariés |
| Données | Code de badge, horodatages, tâches réalisées, quantités déclarées |
| Destinataires | RH, encadrement de production, MES (option) |
| Durée | [à compléter, ex. 5 ans] — supprimé avec le compte du salarié |

### 6. Notes de frais

| | |
|---|---|
| Finalité | Remboursement des frais professionnels |
| Base légale | Exécution du contrat de travail ; obligation légale (justificatifs comptables) |
| Personnes concernées | Salariés |
| Données | Dépenses, justificatifs (photos, PDF). Option IA : image du justificatif envoyée au fournisseur d'IA pour pré-remplir la saisie |
| Destinataires | RH, comptabilité |
| Durée | 10 ans (pièces comptables) |

### 7. Production et traçabilité qualité

| | |
|---|---|
| Finalité | Traçabilité des opérations, visas (revue de contrat, bon pour fabrication, libération, contrôles), non-conformités, dérogations, rapports premier article |
| Base légale | Intérêt légitime ; obligations contractuelles et normatives (ISO 9001, ISO 19443) |
| Personnes concernées | Salariés (auteurs des visas et contrôles), contacts clients (dérogations) |
| Données | Nom de l'auteur, date, décision, commentaire, mesures |
| Destinataires | Qualité, production, clients (rapports, dérogations signées) |
| Durée | Durée de vie des produits ou de l'installation [à compléter] ; le journal des modifications n'est pas purgé par défaut. Un auteur supprimé est anonymisé, jamais effacé |

### 8. Comptes utilisateurs et sécurité

| | |
|---|---|
| Finalité | Authentification, gestion des droits, journalisation des modifications et des consultations |
| Base légale | Intérêt légitime (sécurité du système d'information) |
| Personnes concernées | Utilisateurs de l'ERP |
| Données | Nom, e-mail, rôle, signature d'e-mail, préférences d'affichage ; journaux (auteur, date, adresse IP pour les consultations) |
| Destinataires | Administrateur de l'instance |
| Durée | Compte : durée d'activité. Journal des consultations : 365 jours. Journal des modifications : sans limite par défaut (`ACTIVITYLOG_RETENTION_DAYS`). Jetons de réinitialisation : 1 heure |

### 9. Indicateur d'humeur (facultatif)

| | |
|---|---|
| Finalité | Indicateur d'ambiance de l'équipe |
| Base légale | Consentement (saisie volontaire) |
| Personnes concernées | Salariés qui le renseignent |
| Données | Humeur du jour, date |
| Durée | [à compléter] — supprimé avec le compte |

À retirer si le widget n'est pas utilisé.

## Exercice des droits

- **Accès / portabilité** : un utilisateur télécharge ses données (JSON) depuis
  « Mes données personnelles » ; un contact client depuis le portail client ;
  sinon `php artisan rgpd:export-contact {id}`.
- **Effacement** : la demande faite depuis ces pages est journalisée et envoyée par e-mail à
  l'adresse de la société ; l'administrateur la traite avec `php artisan rgpd:erase-contact {id}`
  ou l'anonymisation d'un utilisateur.
- Délai de réponse : 1 mois (art. 12).
- Tenir un suivi des demandes reçues : [à compléter].

## Mentions légales (pages publiques de l'instance)

Les liens publics (devis, commande, portail client) sont des pages web éditées par l'entreprise.
Mentions à publier (LCEN, art. 6) :

- Raison sociale, forme juridique, capital, adresse du siège, SIREN / RCS, n° de TVA : [à compléter]
- Directeur de la publication : [à compléter]
- Contact (e-mail, téléphone) : [à compléter]
- Hébergeur (nom, adresse, téléphone) : [à compléter]
- Éditeur du logiciel : voir `config('branding.publisher_name')`
- Lien vers la politique de confidentialité.

## Durées de conservation

Détail technique des purges : `docs/conservation-enregistrements.md`. Sauvegardes : jusqu'à
2 ans (`config/backup.php`) ; une donnée effacée subsiste dans les sauvegardes antérieures
jusqu'à leur suppression.
