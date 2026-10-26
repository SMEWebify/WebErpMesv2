---
title: Maintenance (GMAO)
summary: Enregistrer ses équipements, planifier la maintenance préventive, générer et suivre les ordres de travail, déclarer une panne et garder l'historique des interventions de chaque machine.
category: Production
order: 20
duration: 15
icon: fas fa-tools
can: asset_manager
updated: 2026-10-26
---

**Objectif** : tenir à jour la maintenance des machines de l'atelier. Chaque intervention,
qu'elle soit préventive ou consécutive à une panne, est tracée sur l'équipement concerné.

**Pour qui** : le responsable maintenance et le chef d'atelier.

**Exemple utilisé** : la presse plieuse de l'atelier. Sa vidange hydraulique est planifiée
tous les mois, et une fuite sur un vérin doit être déclarée.

## Avant de commencer

- Le menu **GMAO** n'apparaît qu'avec la permission **asset_manager**. La liste des
  équipements demande en plus **asset-menu**.
- La machine doit exister comme **ressource** dans les Méthodes (voir le guide
  « Les Méthodes ») : l'équipement GMAO s'y rattache.

## Vue d'ensemble

```steps
Équipement
Plan de maintenance
Ordre de travail
Intervention
Clôture
```

Le menu **GMAO** a trois entrées : **Dashboard** (le tableau de bord), **Immobilisations**
(les équipements) et **Work orders** (les ordres de travail). Les plans de maintenance
s'ouvrent depuis le tableau de bord ou depuis la fiche d'un équipement.

---

## 1. Le tableau de bord

![Tableau de bord GMAO](img/01-tableau-de-bord.png)

- **Bons de travail** : le nombre d'ordres de travail, par statut ;
- **Maintenance préventive** : le nombre de plans actifs, et les boutons **Nouveau bon de
  travail** et **Plans de maintenance** ;
- **Bons de travail récents** : les derniers ordres créés.

> ⚠️ Les indicateurs du haut (MTBF, MTTR, disponibilité, coût, part préventif / curatif)
> restent affichés « — » : ils ne sont pas encore calculés.

---

## 2. Enregistrer un équipement

Menu **GMAO › Immobilisations**, puis **Nouveau Actif**.

![Nouvel équipement](img/02-equipement-creation.png)

1. **Nom** : un nom que l'atelier reconnaît, avec le repère de la machine.
2. **Ressource** : la machine des Méthodes à laquelle l'équipement correspond.
3. **Acquisition value** : la valeur d'achat. La date d'acquisition et la durée
   d'amortissement en mois sont facultatives.
4. **Soumettre**.

La **fiche équipement** regroupe ses ordres de travail et ses plans de maintenance.

![Fiche équipement](img/03-equipement-fiche.png)

1. **Nouvel ordre de travail** : une intervention ponctuelle (panne, réglage).
2. **Nouveau plan de maintenance** : une maintenance qui revient.

> ⚠️ Le bouton **Supprimer** de la fiche efface l'équipement **sans demander de
> confirmation**. Il en va de même pour les plans et les ordres de travail.

---

## 3. Planifier la maintenance préventive

Depuis la fiche de l'équipement, **Nouveau plan de maintenance** : l'équipement est déjà
choisi.

![Nouveau plan de maintenance](img/04-plan-creation.png)

1. **Type de déclencheur** : au temps (Time-based), aux heures machine, aux cycles, ou à
   date fixe.
2. **Valeur du déclencheur** : la fréquence, en clair (« mensuel », « 500 heures »…).
3. **Date fixe** : la date de la prochaine intervention.

Complétez la **durée estimée**, la **compétence requise**, la **liste des actions** et les
**pièces nécessaires** : ces informations sont recopiées dans chaque ordre de travail.

![Fiche du plan](img/05-plan-fiche.png)

1. **Générer un ordre de travail** crée l'intervention à partir du plan.

> ⚠️ Les ordres ne sont **pas** générés automatiquement à l'échéance : il faut cliquer sur
> le bouton. Chaque clic crée un nouvel ordre de travail, alors ne cliquez qu'une fois.

---

## 4. L'ordre de travail

L'ordre généré est **planifié** (Planned), de type **Preventive**, à la date prévue du plan.

![Ordre de travail généré](img/06-ot-genere.png)

Les statuts se suivent dans cet ordre :

| Statut | Signification |
|---|---|
| Draft | brouillon, pas encore planifié |
| Planned | planifié à une date |
| In progress | intervention en cours |
| Completed | intervention terminée |
| Closed | ordre clôturé, rien à reprendre |

---

## 5. Réaliser l'intervention

**Modifier l'ordre de travail**, puis renseignez l'intervention :

![Statut, technicien et horaires](img/07-ot-execution.png)

1. **Statuts** : « In progress » pendant l'intervention, « Completed » une fois terminée.
2. **Technicien assigné**.
3. **Démarré le** et **Terminé le**.

![Compte rendu](img/08-ot-compte-rendu.png)

1. **Durée réelle** en minutes, à comparer à la durée estimée.
2. **Actions réalisées** : ce qui a été fait, et ce qui a été constaté.
3. **Pièces consommées**.

Enregistrez.

> 💡 Le champ **Machine arrêtée** compte : un ordre de travail avec la machine arrêtée
> retire la période d'intervention de la **capacité** de la ressource dans le planning de
> charge.

---

## 6. Clôturer

Une fois l'intervention vérifiée, rouvrez l'ordre : statut **Closed**, puis **Clôturé le**.

![Clôture](img/09-ot-cloture.png)

1. **Clôturé le** : la date de clôture.
2. **Enregistrer**.

![Ordre clôturé](img/10-ot-fiche.png)

La fiche montre la chronologie complète : demandé, planifié, démarré, terminé, et la durée
estimée en regard de la durée réelle.

---

## 7. Déclarer une panne

Depuis la fiche de l'équipement, **Nouvel ordre de travail**.

![Ordre curatif](img/11-ot-correctif.png)

1. **Type** : **Corrective** pour une réparation (Preventive pour l'entretien planifié).
2. **Priorité** : Low, Medium, High ou Critical.

En bas du formulaire, décrivez la panne :

![Description de la panne](img/12-ot-panne.png)

1. **Défaillance type** : la nature de la panne.
2. **Gravité**.
3. **Machine arrêtée** : YES si la machine ne peut plus produire.
4. **Début de la panne** : la date et l'heure de l'arrêt.

Puis suivez l'intervention comme aux sections 5 et 6.

> ⚠️ Si un champ est mal rempli, la page se recharge **sans message d'erreur** et l'ordre
> n'est pas créé. Vérifiez qu'il apparaît bien dans la liste.

---

## 8. Suivre les interventions

Menu **GMAO › Work orders** : tous les ordres de travail, avec leur équipement, leur type,
leur priorité, leur statut et leur technicien.

![Liste des ordres de travail](img/13-ot-liste.png)

La **fiche équipement** garde l'historique de ses interventions :

![Historique de l'équipement](img/14-equipement-historique.png)

## Questions fréquentes

**Mon plan de maintenance n'a créé aucun ordre de travail à l'échéance.**
C'est normal : les ordres se génèrent avec le bouton « Générer un ordre de travail » de la
fiche du plan.

**Les indicateurs MTBF et MTTR restent à « — ».**
Ils ne sont pas encore calculés. La durée réelle et le début de la panne saisis sur chaque
ordre de travail serviront à les calculer.

**Ma machine n'apparaît pas dans la liste des ressources de l'équipement.**
Créez-la d'abord dans Méthodes › Ressources.

**Pourquoi des libellés en anglais (Planned, Corrective, High…) ?**
Les statuts, types et priorités de la GMAO ne sont pas encore traduits : ce guide les cite
tels qu'ils s'affichent.
