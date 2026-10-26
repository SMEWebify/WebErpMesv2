---
title: Risques et santé au travail
summary: Le suivi santé-sécurité de l'atelier — incidents, évaluation des risques, vérifications réglementaires, formations et habilitations, matrice de polyvalence et écarts sur les tâches affectées.
category: Qualité
order: 20
duration: 15
icon: fas fa-hard-hat
can: osh-menu
updated: 2026-10-26
---

**Objectif** : tenir les registres santé-sécurité de l'atelier, et savoir à tout moment qui
est habilité sur quelle machine.

**Pour qui** : le responsable QSE et le chef d'atelier.

**Exemple utilisé** : un opérateur se pince un doigt à la presse plieuse pendant un
changement d'outil. L'incident est déclaré, le risque évalué, la vérification de la
machine suivie, et l'habilitation « Conduite plieuse » mise en place.

## Avant de commencer

- Le menu **Risque et santé** apparaît avec la permission **osh-menu**, nécessaire aussi
  pour créer les types d'habilitation.
- Les **sections** (ateliers) et les **ressources** (machines) viennent des Méthodes (voir
  le guide « Les Méthodes »).

> ⚠️ Le module ne remplace pas le **document unique** (DUERP) et ne gère ni les EPI ni les
> déclarations d'accident : il tient les registres et alerte sur les habilitations.

## Vue d'ensemble

```steps
Types d'habilitation
Incident
Risque
Conformité
Formations
Matrice et écarts
```

Le menu **Risque et santé** a cinq entrées : **Incidents**, **Risque**, **Formations**,
**Matrice de polyvalence** et **Conformités**. Chaque écran affiche une liste à gauche et un
formulaire de création à droite. Pour modifier une ligne, cliquez sur son crayon.

> ⚠️ Sauf sur la matrice de polyvalence, aucun message ne confirme l'enregistrement, et un
> formulaire incomplet revient sans erreur affichée. Vérifiez que la ligne apparaît dans la
> liste.

---

## 1. Préparer les types d'habilitation

Menu **Matrice de polyvalence**, onglet **Types d'habilitation**. Un type d'habilitation est
une compétence reconnue (conduite plieuse, CACES, SST, soudage…), à renouveler ou non.

![Nouveau type d'habilitation](img/01-type-creation.png)

1. **Référence projet** : le code court de l'habilitation (le libellé du champ devrait être
   « Code »). Il doit être unique.
2. **Validité (mois)** : la durée avant renouvellement, 0 pour une habilitation à vie.
3. **Ressources** : les machines sur lesquelles l'habilitation est attendue (Ctrl + clic
   pour en choisir plusieurs). Laissez vide pour une habilitation générale (SST, CACES).
4. **Soumettre**.

![Types d'habilitation](img/02-types-liste.png)

> 💡 Le lien avec les machines sert uniquement à repérer les **écarts** (section 7) : il
> n'empêche jamais d'affecter un opérateur.

---

## 2. Déclarer un incident

Menu **Incidents**.

![Nouvel incident](img/03-incident-creation.png)

1. **Date de l'Incident**.
2. **Gravité** : Mineur, Majeur ou Critique.
3. **Actions Correctives** : ce qui a été fait tout de suite.
4. **Soumettre**, après avoir choisi l'**Utilisateur** concerné.

L'incident est créé **En cours**. Une fois les actions menées, cliquez sur son crayon :

![Résoudre l'incident](img/04-incident-resolution.png)

1. **Statuts** : Résolue.
2. **Temps de résolution** : malgré son libellé, c'est la **date** de résolution.
3. **Mettre à jours**.

![Registre des incidents](img/05-incidents-liste.png)

> 💡 Saisir la date de résolution ne change pas le statut : passez-le à « Résolue » vous-même.

---

## 3. Évaluer le risque

Menu **Risque** : l'évaluation des risques par atelier.

![Nouveau risque](img/06-risque-creation.png)

1. **Section** : l'atelier concerné.
2. **Gravité** : Faible, Modéré ou Élevé.
3. **Probabilité** : Rare, Possible ou Probable.
4. **Mesures Préventives** : ce qui réduit le risque (protections, consignes, habilitation
   exigée).

![Évaluation des risques](img/07-risques-liste.png)

L'écran ne calcule pas de cotation. Pour hiérarchiser les risques, multipliez la gravité
par la probabilité (de 1 à 9) :

| | Rare (1) | Possible (2) | Probable (3) |
|---|---|---|---|
| **Élevé (3)** | 3 | **6** | **9** |
| **Modéré (2)** | 2 | 4 | **6** |
| **Faible (1)** | 1 | 2 | 3 |

Dans l'exemple, l'écrasement des mains à la plieuse est coté 3 × 2 = **6** : à traiter en
priorité.

> 💡 L'incident et le risque ne sont pas reliés entre eux : citez l'incident dans les
> mesures préventives si vous voulez garder le lien.

---

## 4. Suivre les vérifications réglementaires

Menu **Conformités** : vérifications périodiques (VGP), contrôles électriques, registres
obligatoires.

![Nouvelle conformité](img/08-conformite-creation.png)

1. **Type de document** : la vérification ou le document.
2. **Date d'Expiration** : l'échéance de la prochaine vérification.
3. **Soumettre**, après avoir choisi la **Section** et l'**Utilisateur** responsable.

Une fois le rapport reçu, passez la conformité à **Approuvée** :

![Statut de la conformité](img/09-conformite-statut.png)

1. **Statuts** : En cours, Approuvée ou Expiré.

![Registre des conformités](img/10-conformites-liste.png)

> ⚠️ Le statut **Expiré** ne se pose pas tout seul à la date d'échéance, et aucune alerte
> n'est envoyée : relisez la colonne « Date d'Expiration » chaque mois.

---

## 5. Enregistrer les formations

Menu **Formations** : une ligne par session suivie par un salarié.

![Nouvelle formation](img/11-formation-creation.png)

1. **Type d'habilitation** : sans lui, la formation n'apparaît pas dans la matrice.
2. **Date de Formation**.
3. **Date d'Expiration** : à calculer à partir de la validité du type (elle n'est pas
   proposée). Laissez vide pour une habilitation à vie.
4. **Soumettre**, après avoir choisi l'**Utilisateur**.

Le **Type de Formation** est le libellé libre de la session (« SST initial », « Recyclage
CACES 3 »…).

Si le salarié n'a pas obtenu la certification, ouvrez la formation avec son crayon :

![Certification non obtenue](img/12-formation-non-obtenue.png)

1. **Certification Obtenue** : NO.

![Liste des formations](img/13-formations-liste.png)

> ⚠️ L'interrupteur **Certification Obtenue** du formulaire de création n'est pas pris en
> compte : toute formation est créée « obtenue ». Corrigez-la ensuite si besoin.

---

## 6. Lire la matrice de polyvalence

Menu **Matrice de polyvalence** : un salarié par ligne, une habilitation par colonne, avec
sous chaque colonne les machines concernées.

![Matrice de polyvalence](img/14-matrice.png)

| Pastille | État |
|---|---|
| ✔ vert | **Valide** |
| ⏳ jaune | **Bientôt échue** : la date de fin est dans moins de 60 jours |
| ✖ rouge | **Périmée** : la date de fin est passée |
| ⊘ gris | **Non obtenue** : la dernière session n'a pas été validée |
| — | **Non formé** : aucune session |

- C'est la **dernière session** qui compte : un recyclage réussi remplace une session
  périmée.
- Une habilitation sans date de fin est valable à vie.

---

## 7. Repérer les écarts

Onglet **Écarts constatés** : les opérations affectées à un opérateur qui n'a pas
d'habilitation valide pour la machine de l'opération.

![Écarts constatés](img/15-ecarts.png)

Dans l'exemple, l'opération de pliage sur la presse est affectée à un salarié **non formé**
à la conduite de la plieuse.

> 💡 L'écran est **informatif** : il ne bloque ni l'affectation, ni le démarrage d'une
> opération, ni le planning. La décision reste au chef d'atelier.

---

## 8. Routine mensuelle

1. **Matrice** : relancer les formations « bientôt échues » et organiser les recyclages
   des « périmées ».
2. **Écarts constatés** : réaffecter les opérations ou planifier les formations.
3. **Conformités** : repérer les échéances du mois et passer les dépassées en « Expiré ».
4. **Incidents** : clore ceux dont les actions sont faites, et reporter dans **Risque** les
   mesures préventives qui en découlent.

## Questions fréquentes

**Une formation n'apparaît pas dans la matrice.**
Elle n'a pas de type d'habilitation, ou le type est inactif. Ouvrez la formation et
choisissez son type.

**Un salarié a été recyclé, mais la matrice le montre toujours « Périmée ».**
La nouvelle session doit avoir une date de formation plus récente et le même type
d'habilitation.

**Pourquoi l'onglet « Écarts constatés » est vide ?**
Aucune opération affectée à un opérateur ne porte sur une machine liée à un type
d'habilitation, ou tous les opérateurs sont habilités.

**Je ne trouve pas mes enregistrements après avoir cliqué sur « Soumettre ».**
Un champ obligatoire manquait (date, utilisateur, description) : l'écran revient sans
message. Ressaisissez en vérifiant chaque champ.
