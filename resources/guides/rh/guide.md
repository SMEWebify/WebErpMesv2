---
title: Ressources humaines — fiche salarié et notes de frais
summary: Tenir la fiche de chaque salarié (informations RH, matricule de paie, contrats, dossier confidentiel) et traiter les notes de frais, du ticket photographié par le salarié jusqu'à la validation par les RH.
category: Ressources humaines
order: 10
duration: 15
icon: fas fa-users
can: human-resources-menu
updated: 2026-10-31
---

**Objectif** : regrouper sur une seule fiche tout ce que l'entreprise sait d'un salarié, et
faire circuler les notes de frais sans papier.

**Pour qui** : la personne chargée des RH, et chaque salarié pour ses notes de frais.

**Exemple utilisé** : un opérateur presse plieuse embauché en CDI au 1er septembre. Il
rentre d'un salon professionnel à Lyon avec un ticket de restaurant à se faire rembourser.

## Avant de commencer {#avant-de-commencer}

- Le menu **Ressources humaines** apparaît avec la permission **human-resources-menu**,
  nécessaire aussi pour modifier une fiche salarié, ses contrats, et valider les notes de
  frais.
- Chaque salarié saisit ses notes de frais depuis son **profil**, sans permission
  particulière.
- Les congés, le rapport de présence et l'export paie font l'objet du guide « Temps,
  horaires et congés » ; la matrice des habilitations, du guide « Risques et santé au
  travail ».

## Vue d'ensemble {#vue-densemble}

```steps
Fiche salarié
Contrat
Dossier
Note de frais
Justificatif
Validation
```

Le menu **Ressources humaines** regroupe : **Note de frais** (validation, catégories de
dépense, natures de congé), **Utilisateurs liste**, **Rapport de présence**, **Soldes de
congés** et **Export paie**.

---

## 1. La liste des salariés {#la-liste-des-salaries}

Menu **Ressources humaines › Utilisateurs liste** : un salarié par ligne, avec son statut de
connexion, son poste, ses rôles et, s'il est bloqué, la date de fin du blocage. La flèche en
bout de ligne ouvre sa fiche.

![Liste des salariés](img/01-salaries.png)

> 💡 Un salarié est un compte utilisateur de l'ERP : il s'inscrit lui-même, puis
> l'administrateur lui donne un rôle (voir le guide « Mettre en route son instance ») ; sa
> fiche RH se complète ensuite ici.

---

## 2. La fiche salarié {#la-fiche-salarie}

Onglet **RH information** :

![Informations RH](img/02-fiche-rh.png)

1. **Poste** : l'intitulé du poste occupé.
2. **Matricule** : le matricule du salarié dans votre logiciel de paie. Il est repris par
   l'export paie ; sans lui, l'export utilise l'identifiant interne et le signale.
3. **Rôle** : le rôle qui ouvre les menus de l'ERP. « — » le laisse inchangé.

Plus bas : niveau de rémunération, poste de travail, dates d'adhésion, de confirmation et
de résiliation, et statut de l'employé. En bas du formulaire :

![Superviseur et section](img/03-fiche-rh-superviseur.png)

1. **Superviseur** : le responsable du salarié (chef d'équipe, chef d'atelier). Il est
   prévenu dans sa **cloche de notifications** à chaque demande d'absence du salarié.
2. **Section** : l'atelier ou le service du salarié.
3. **Mettre à jour** enregistre la fiche.

> ⚠️ Vérifiez la liste **Superviseur** à chaque modification de la fiche : c'est cette
> personne qui recevra les demandes d'absence du salarié. Choisissez « — » s'il n'a pas de
> responsable.
>
> 💡 Le superviseur est **prévenu**, il ne **valide** pas : la validation des absences reste
> aux RH (voir le guide « Temps, horaires et congés »).

> 💡 Le rôle ne se modifie qu'avec la permission d'administration de l'entreprise, et jamais
> sur son propre compte.
>
> ⚠️ La carte **Bloqué jusqu'au** (colonne de gauche) empêche le salarié de se connecter
> jusqu'à la date choisie : réservez-la à un départ ou à un compte compromis.

---

## 3. Les contrats {#les-contrats}

Onglet **Contra** (contrats), carte **Nouveau contrat** :

![Nouveau contrat](img/04-contrat.png)

1. **Type de contrat** : CDI, CDD, intérim, apprentissage…
2. **Date de début** (et **Date de fin** pour un contrat à durée déterminée).
3. **Durée hebdomadaire** en heures.

Complétez le **statut** (Essais pendant la période d'essai, puis Actif), la **section**, la
**date de signature**, la **durée de la période d'essai** en jours, la **position** et, si
besoin, le coefficient et les salaires. **Soumettre**.

![Contrats du salarié](img/05-contrats.png)

Le contrat **Actif** compte : sa durée hebdomadaire sert à convertir en jours les absences
saisies en heures.

---

## 4. Le dossier du salarié {#le-dossier-du-salarie}

Onglet **Documents** : contrat signé, bulletins, arrêts de travail, diplômes, pièces
d'identité.

![Dossier du salarié](img/06-documents.png)

Déposez un fichier dans la zone prévue, puis choisissez son **type de document** (contrat,
bulletin de paie, arrêt de travail, diplôme, pièce d'identité…).

> ⚠️ Ce dossier est **confidentiel** : seuls le salarié concerné et les RH peuvent le
> consulter, y déposer un fichier ou en supprimer. Le reste de l'entreprise n'y a pas accès,
> contrairement aux documents des articles ou des commandes.

---

## 5. La note de frais (salarié) {#la-note-de-frais-salarie}

Le salarié ouvre son **profil** (menu de son nom en haut à droite), onglet **Note de frais**,
et crée une note par déplacement ou par mois :

![Nouvelle note de frais](img/07-note-creation.png)

1. **Libellé** : le déplacement ou la période.
2. **Date**.
3. **Soumettre**.

Le bouton **Voir** de la note ouvre sa page : la liste des dépenses à gauche, les
**justificatifs** à droite.

---

## 6. Le justificatif et la dépense {#le-justificatif-et-la-depense}

Déposez d'abord la photo du ticket (ou la facture en PDF) dans la carte **Justificatifs** :

![Justificatif déposé](img/08-justificatif.png)

1. **Justificatifs** : glissez le fichier ou cliquez pour le choisir. Sur téléphone, le
   navigateur propose de prendre la photo.

Puis, dans la carte de saisie :

![Saisir la dépense](img/09-depense.png)

1. **Justificatif** : le ticket déposé. La liste ne propose que les justificatifs pas encore
   saisis.
2. **Lire avec l'IA** : lit le ticket et pré-remplit la date, le lieu, la description, le
   montant et la TVA. Rien n'est enregistré : relisez avant d'envoyer.
3. **Catégorie** : carburant, hôtel, repas…

![Montant et TVA](img/10-depense-montant.png)

1. **Montant** : le montant TTC payé.
2. **Taxe** : le **taux** de TVA (10 pour 10 %), pas son montant.
3. **Soumettre** ajoute la dépense à la note.

La dépense peut aussi être rattachée à une **commande** client (frais refacturables) ; le
**payeur** est celui qui a avancé la somme.

![Note de frais](img/11-note.png)

> ⚠️ **Lire avec l'IA** envoie l'image du justificatif au fournisseur d'IA configuré par
> votre administrateur. Sans fournisseur configuré, saisissez la dépense à la main.

---

## 7. Soumettre la note {#soumettre-la-note}

Une fois toutes les dépenses saisies, le salarié revient dans son profil, onglet **Note de
frais**, et ouvre la note avec **Modifier** :

![Soumettre la note](img/12-note-soumission.png)

1. **Statuts** : « Soumise ».
2. **Mettre à jour**.

Une note soumise ne se modifie plus : ses dépenses et ses justificatifs sont figés jusqu'à
la décision des RH.

---

## 8. La validation (RH) {#la-validation-rh}

Menu **Ressources humaines › Note de frais**, onglet **Validation des note de frais** : les
notes soumises (et celles déjà approuvées), avec leur montant. **Voir** ouvre la note et ses justificatifs.

![Notes à valider](img/13-validation-liste.png)

**Modifier** sur la note :

![Valider la note](img/14-validation.png)

1. **Statuts** : **Approuvée**, ou **Retour** pour la renvoyer au salarié (justificatif
   manquant, montant à corriger).
2. **Mettre à jour**.

Une note retournée redevient modifiable par le salarié, qui la corrige puis la soumet à
nouveau.

| Statut | Qui | Modifiable par le salarié |
|---|---|---|
| Fait / Soumettre | le salarié prépare sa note | oui |
| Soumise | en attente des RH | non |
| Retour | renvoyée au salarié | oui |
| Approuvée | validée par les RH | non |

> 💡 Le remboursement lui-même se fait hors de l'ERP (virement ou paie) : l'approbation
> indique seulement que la note est bonne à payer.

---

## 9. Les catégories de dépense {#les-categories-de-depense}

Onglet **Catégorie de dépense** : la liste proposée au salarié (carburant, hôtel, repas
midi, repas soir…). **Nouvelle catégorie de dépense** en ajoute une.

![Catégories de dépense](img/15-categories.png)

## Et ensuite ? {#et-ensuite}

- Créer les natures de congé et saisir les droits de chaque salarié : guide « Temps,
  horaires et congés ».
- Suivre les habilitations du salarié (CACES, habilitation électrique) : guide « Risques et
  santé au travail ».

## Questions fréquentes {#questions-frequentes}

**Le salarié ne voit pas son ticket dans la liste « Justificatif ».**
Il est déjà rattaché à une autre dépense, ou il a été déposé dans une autre note.

**La note n'apparaît pas dans l'onglet de validation.**
Elle n'est pas au statut « Soumise » : le salarié doit la soumettre depuis son profil.

**Un collègue peut-il voir mes justificatifs ?**
Non : comme le dossier salarié, les justificatifs ne sont visibles que par le salarié de la
note et par les RH.

**Un salarié a un superviseur que personne n'a choisi.**
Sa fiche a été enregistrée quand la liste **Superviseur** n'avait pas encore de choix vide :
le premier nom de la liste a été retenu. Choisissez le bon responsable (ou « — ») et cliquez
**Mettre à jour**.

**Je ne peux pas modifier le rôle d'un salarié.**
Il faut la permission d'administration de l'entreprise, et on ne modifie jamais son propre
rôle.
