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

Le menu **GMAO** a quatre entrées : **Tableau de bord**, **Immobilisations** (les
équipements), **Plans de maintenance** et **Ordres de travail**. Les plans s'ouvrent aussi
depuis la fiche d'un équipement.

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

1. **Type de déclencheur** : périodique (calendrier), heures machine, cycles, ou date fixe.
2. **Valeur du déclencheur** : la fréquence, en clair (« mensuel », « 500 heures »…), pour
   information.
3. **Date fixe** : la date de la prochaine intervention (pour un plan périodique, la
   première).
4. **Générer automatiquement les ordres de travail** (cochée par défaut), avec la
   **périodicité en jours** d'un plan périodique (7 = hebdomadaire, 30 = mensuel, 365 =
   annuel) et le nombre de **jours d'avance** avec lequel l'ordre est créé (7 par défaut).

Complétez la **durée estimée**, la **compétence requise**, la **liste des actions** et les
**pièces nécessaires** : ces informations sont recopiées dans chaque ordre de travail.

![Fiche du plan](img/05-plan-fiche.png)

La fiche affiche la **prochaine échéance** et la liste des ordres déjà générés par le plan.

Chaque matin, un ordre **planifié** est créé quand l'échéance approche (moins les jours
d'avance), sauf si le plan a déjà un ordre ouvert : un ordre en retard n'en empile pas
d'autres. L'échéance suivante part de la date planifiée du précédent ordre, pas de sa date
de clôture, pour que le calendrier ne dérive pas.

1. **Générer un ordre de travail** crée l'intervention tout de suite, sans attendre.

> ⚠️ Les heures machine et les cycles ne sont relevés nulle part : ces plans ne génèrent
> rien tout seuls, il faut cliquer sur le bouton. Chaque clic crée un nouvel ordre.

---

## 4. L'ordre de travail

L'ordre généré est **Planifié**, de type **Préventif**, à la date d'échéance du plan.

![Ordre de travail généré](img/06-ot-genere.png)

Les statuts se suivent dans cet ordre :

| Statut | Signification |
|---|---|
| Brouillon | pas encore planifié |
| Planifié | planifié à une date |
| En cours | intervention en cours |
| Terminé | intervention terminée |
| Clôturé | ordre clôturé, rien à reprendre |

---

## 5. Réaliser l'intervention

**Modifier l'ordre de travail**, puis renseignez l'intervention :

![Statut, technicien et horaires](img/07-ot-execution.png)

1. **Statuts** : « En cours » pendant l'intervention, « Terminé » une fois terminée.
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

Une fois l'intervention vérifiée, rouvrez l'ordre : statut **Clôturé**, puis **Clôturé le**.

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

1. **Type** : **Curatif** pour une réparation (Préventif pour l'entretien planifié).
2. **Priorité** : Basse, Moyenne, Haute ou Critique.

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

Menu **GMAO › Ordres de travail** : tous les ordres de travail, avec leur équipement, leur type,
leur priorité, leur statut et leur technicien.

![Liste des ordres de travail](img/13-ot-liste.png)

La **fiche équipement** garde l'historique de ses interventions :

![Historique de l'équipement](img/14-equipement-historique.png)

## Questions fréquentes

**Mon plan de maintenance n'a créé aucun ordre de travail à l'échéance.**
Vérifiez sur la fiche du plan que la génération automatique est cochée et, pour un plan
périodique, que la périodicité est renseignée. Un plan qui a déjà un ordre ouvert attend sa
clôture. Les plans aux heures machine ou aux cycles ne génèrent rien tout seuls. Les plans
créés avant cette fonction ont la génération décochée : modifiez-les pour l'activer.

**Les indicateurs MTBF et MTTR restent à « — ».**
Ils ne sont pas encore calculés. La durée réelle et le début de la panne saisis sur chaque
ordre de travail serviront à les calculer.

**Ma machine n'apparaît pas dans la liste des ressources de l'équipement.**
Créez-la d'abord dans Méthodes › Ressources.
