---
title: Articles et stock
summary: Créer un article et sa gamme, organiser le stock en emplacements, enregistrer les entrées, sorties et transferts, suivre le statut du stock, les numéros de série et les inventaires.
category: Articles
order: 10
duration: 20
icon: fas fa-boxes-stacked
can: products-menu
updated: 2026-10-26
---

**Objectif** : tenir la fiche de chaque article (ce que l'on vend, achète ou fabrique) et
savoir à tout moment combien il en reste, et où.

**Pour qui** : le responsable des méthodes (articles et gammes) et le magasinier (stock).

**Exemple utilisé** : une équerre support pliée à 90° en acier de 3 mm, découpée au laser
puis pliée, rangée sur l'étagère A1 du magasin avec un stock minimum de 10 pièces.

## Avant de commencer

- La liste et la fiche des articles demandent la permission **products-menu** (lecture) ;
  créer, modifier, dupliquer un article, sa gamme, sa nomenclature ou ses documents demande
  en plus **products-edit**. Le stock, les lots, les numéros de série et les inventaires
  demandent **stock-lot-serial-management**.
- Les **services** (laser, pliage…), **familles** et **unités** viennent des Méthodes
  (voir le guide « Les Méthodes »).

## Vue d'ensemble

```steps
Article
Gamme
Stock et emplacement
Entrées et sorties
Statut du stock
Inventaire
```

Le menu **Article** regroupe : **Liste de articles**, **Numéros de série**, **Lots**,
**Stock**, **Statut du stock** et **Inventaires**.

Le stock s'organise sur quatre niveaux :

| Niveau | Exemple |
|---|---|
| **Stock** (entrepôt) | Magasin atelier |
| **Emplacement** | Étagère A1 |
| **Ligne de stock** : un article dans un emplacement | Équerre 90° sur l'étagère A1, minimum 10 |
| **Mouvement** : une entrée, une sortie ou un transfert | +20 en réception, −12 en sortie |

---

## 1. La liste des articles

Menu **Article › Liste de articles**, onglet **Liste des produits** : code, libellé,
service, famille, vendu / acheté et leurs prix, nombre d'opérations de la gamme.

![Liste des articles](img/01-liste.png)

1. **Nouveau produit** ouvre la fenêtre de création.

L'onglet **Tableau de bord** résume le catalogue par service et par famille ; l'onglet
**Doublons** aide à fusionner deux fiches du même article.

---

## 2. Créer un article

![Nouveau produit](img/02-creation.png)

1. **Code** : la référence de l'article, unique.
2. **Libellé**.
3. **Service** : le service principal de l'article.
4. **Famille** : le classement de l'article (tôle, raccord, composant…).
5. **Unité** : pièce, kilo, mètre…

Puis **Vendu** et **Acheté** (un article peut être les deux), la **traçabilité**, le
**prix de vente (Prix U)** et le **prix d'achat**, la matière, l'épaisseur, le poids et les
dimensions. **Soumettre** ouvre la fiche de l'article.

> ⚠️ Les libellés de la **traçabilité** sont trompeurs : « Avec numéro batch » correspond au
> **lot**, « Avec numéro de lot » au **numéro de série**.

---

## 3. La fiche article

![Fiche article](img/03-fiche.png)

1. Les onglets de la fiche :
   - **Détail produit** : toutes les informations de la création, plus la nature de TVA et le
     plan de contrôle ;
   - **Tarifs** (article vendu) : la **grille tarifaire client**, des paliers de prix par
     quantité pour tous les clients, un type de client ou un client précis, avec l'écart au
     prix de vente de base ;
   - **Découpage technique et nomenclature** : la gamme et les matières (section 4) ;
   - **Stock** : les lignes de stock de l'article (section 7) ;
   - **Historique** : les devis, commandes et achats de l'article ;
   - **Numéros de série**, **Documents** (plans, modèles 3D, photos), **Plans de
     contrôle** (voir le guide « Qualité ») et **Logs**.

Si l'article est acheté, un onglet **Fournisseurs préférés** liste ses fournisseurs et leurs
prix par quantité.

> 💡 La carte **Options** (colonne de droite) propose **Dupliqué produit** : la copie, gamme
> comprise, est créée **immédiatement**, avec un code provisoire. Renommez-la aussitôt.

---

## 4. La gamme de fabrication

Onglet **Découpage technique et nomenclature**. La languette **Opération**, au bord droit de
l'écran, ouvre le panneau **Nouvelle opération** :

![Nouvelle opération](img/04-gamme-operation.png)

1. **Service** : laser, pliage, soudure… Le libellé et le taux horaire suivent.
2. **Temps de réglage** : une fois par lancement.
3. **Temps unitaire** : par pièce.
4. **Ajouter l'opération**.

Le coût et le prix unitaires se calculent avec le taux horaire et la marge du service, et
restent modifiables.

![Gamme de l'article](img/05-gamme.png)

La gamme est recopiée sur chaque ligne de devis et de commande qui utilise l'article. Les
cartes **Nomenclature matières** et **Sous-ensembles** décrivent les matières et les
composants consommés.

> 💡 Les temps s'affichent en minutes ou en heures selon le réglage **Unité des temps** de
> l'entreprise.

---

## 5. Les documents de l'article

Onglet **Documents** : déposez le plan PDF, le fichier DXF, le modèle 3D (STEP, STL) et les
photos.

![Documents](img/06-documents.png)

Choisissez le **type de document** (plan, modèle 3D, photo…). Le plan principal est repris
par les lignes de devis et de commande, par la fiche suiveuse et par le bulleur des plans de
contrôle. Un clic sur un fichier l'affiche à droite, y compris les modèles 3D.

---

## 6. Organiser le stock

Menu **Article › Stock**, carte **Nouveau stock** :

![Nouveau stock](img/07-stock-creation.png)

1. **ID externe** : un code court du stock.
2. **Description**.
3. **Soumettre** ouvre la fiche du stock.

Sur la fiche du stock, carte **Nouveau emplacement de stock** : un code et une description
(étagère, rack, zone).

![Nouvel emplacement](img/08-emplacement-creation.png)

Puis, sur la fiche de l'emplacement, rangez-y l'article :

![Nouvelle ligne de stock](img/09-ligne-creation.png)

1. **Produit** : tapez le code ou le libellé, puis cliquez sur l'article proposé.
2. **Qt mini** : le stock minimum souhaité dans cet emplacement.
3. **Adresage** : la position précise (A1-01).
4. **Soumettre**.

![Emplacement et lignes](img/10-emplacement.png)

> ⚠️ Stocks, emplacements, lignes et mouvements **ne se suppriment pas** depuis l'écran :
> vérifiez le code et l'article avant d'enregistrer.

---

## 7. Entrées, sorties et transferts

Le bouton **Voir** d'une ligne de stock ouvre ses mouvements, avec quatre tuiles : prix
moyen pondéré (CUMP), entrées, sorties et stock courant.

**Entrée** (carte « Nouvelle ligne entré stock ») :

![Entrée de stock](img/11-entree.png)

1. **Type de mouvement** : Réception manuelle des stocks, Inventaires, Réception commande
   achat, Composant fabriqué.
2. **Qt** : la quantité entrée.

**Sortie** (carte « Nouvelle ligne sortie stock ») :

![Sortie de stock](img/12-sortie.png)

1. **Type de mouvement** : Dispatching manuel du stock, ou Pièce livrée.
2. **Qt** : la quantité sortie. Elle est refusée si le stock est insuffisant.

La sortie peut être rattachée à une ligne de commande ou à une tâche de l'article. La carte
**Transfert de stock** déplace une quantité vers un autre emplacement (une sortie et une
entrée en une seule opération).

![Mouvements](img/13-mouvements.png)

Les réceptions d'achat, les livraisons et les déclarations de production créent ces
mouvements toutes seules : la saisie manuelle sert aux cas hors flux.

> ⚠️ Un mouvement est **définitif** : il n'y a ni suppression ni annulation. Une erreur se
> corrige par un mouvement inverse.
>
> ⚠️ Une entrée manuelle ne porte pas de prix : elle ne fait pas évoluer le **CUMP**, qui
> reste à 0 tant que l'article n'est entré que de cette façon. Seules les réceptions
> d'achat valorisent le stock.
>
> 💡 Laissez le champ **Batch** vide : le lot se note dans **Tracabilité**.

L'onglet **Stock** de la fiche article reprend toutes ses lignes, avec la quantité en rouge
quand elle passe sous le minimum :

![Stock de l'article](img/14-fiche-stock.png)

---

## 8. Le statut du stock

Menu **Article › Statut du stock** : pour chaque article, le stock total face au **besoin**
(commandes non livrées et tâches non terminées), les réservations et les emplacements.

![Statut du stock](img/15-statut.png)

- **Statut** : OK si le stock couvre le besoin, Alerte s'il est tout juste égal, Rupture
  s'il est inférieur.
- **Emplacements** : chaque badge passe au rouge sous le **minimum** de l'emplacement.

Dans l'exemple, l'équerre est **OK** (8 en stock, aucun besoin) mais son emplacement est
**rouge** (8 sous le minimum de 10) : il faut réapprovisionner, sans urgence de commande.

**Réapprovisionner** ouvre les besoins d'achat. Pour un article fabriqué avec un besoin,
l'icône de dossier crée une **commande interne** de fabrication, après confirmation.

---

## 9. Numéros de série et lots

Menu **Article › Numéros de série** : un numéro par pièce livrée, avec l'article, la
commande, le statut et le bouton **Trace** (son parcours).

![Numéros de série](img/16-series.png)

Les numéros de série sont créés à la **livraison**, pour les articles suivis à la pièce.

> ⚠️ L'écran **Lots** reste vide pour l'instant : le numéro de lot d'un article suivi par
> lot est porté par le champ **Tracabilité** des mouvements de stock (il est généré à la
> première mise en stock depuis une réception ou une commande interne).

---

## 10. Les inventaires

Menu **Article › Inventaires**, **Nouvel inventaire** : choisissez le périmètre (tout le
stock, des emplacements ou des catégories).

![Inventaires](img/17-inventaires.png)

La fiche suit trois étapes :

1. **Exporter** la liste à compter (Excel), ou sa **version aveugle** sans les quantités
   attendues, à imprimer pour le comptage ;
2. **Importer** le fichier compté : un aperçu signale les erreurs avant l'import ;
3. **Valider** : les écarts deviennent des mouvements de régularisation.

![Fiche d'inventaire](img/18-inventaire.png)

> ⚠️ Mettez les mouvements de stock en pause pendant l'inventaire : la validation est refusée
> si le stock a bougé depuis la création. Une fois validé, l'inventaire est figé et ses
> mouvements sont définitifs.
>
> 💡 Le comptage se fait dans le fichier Excel : il n'y a pas de saisie des quantités
> comptées dans l'écran.

## Questions fréquentes

**« Code déjà utilisé » en créant un stock ou un emplacement.**
Un autre stock porte déjà ce code : choisissez-en un autre.

**Le prix moyen pondéré reste à 0.**
L'article n'est entré en stock que par des entrées manuelles, qui ne portent pas de prix.

**Je me suis trompé de quantité dans une sortie.**
Faites une entrée de la différence (ou l'inverse) : un mouvement ne se modifie pas.

**L'article est « OK » mais l'emplacement est rouge.**
Le stock couvre les commandes en cours, mais il est passé sous le minimum de l'emplacement.
