---
title: Du prospect à la facture
summary: Le parcours de vente complet — prospect, lead, opportunité, devis avec gamme, commande, déclaration de production, bon de livraison et facture.
category: Ventes
order: 10
duration: 20
icon: fas fa-route
updated: 2026-10-25
---

**Objectif** : suivre une affaire de bout en bout dans Nest2Prod ERP, du premier contact
avec un prospect jusqu'à la facture, en passant par le devis, la commande, la fabrication
et la livraison.

**Exemple utilisé** : la Métallerie Durand, rencontrée sur un salon, commande 20 platines
en acier S235 de 8 mm, découpées au laser puis pliées, à 42,50 € HT pièce.

## Avant de commencer

- Vous devez avoir accès aux menus Tiers, Leads, Opportunités, Devis, Commandes,
  Bons de livraison et Factures.
- Le paramétrage de base doit être fait par votre administrateur : conditions et modes de
  paiement, modes de livraison, taux de TVA, unités, et les **services** de fabrication
  (laser, pliage…) avec leur taux horaire.

## Vue d'ensemble

```steps
Prospect
Lead
Opportunité
Devis (ligne + gamme)
Commande
Production déclarée
Bon de livraison
Facture
```

Chaque document est créé **à partir du précédent** : vous ne ressaisissez jamais le client,
l'adresse ni les lignes. Le fil d'Ariane en haut de chaque document (Lead › Opportunité ›
Devis › Commande…) permet de remonter l'historique de l'affaire.

---

## 1. Se connecter

![Écran de connexion](img/01-connexion.png)

1. Saisissez votre **e-mail**.
2. Saisissez votre **mot de passe**.
3. Cliquez sur « Connexion ».

Vous arrivez sur le tableau de bord.

![Tableau de bord](img/02-tableau-de-bord.png)

---

## 2. Créer le prospect

Un prospect est une société qui n'est pas encore cliente. Il faut le créer avec **une
adresse et un contact** : le lead et le devis en ont besoin.

### 2.1 Ajouter la société

Menu **Tiers**, onglet « Liste des Tiers », puis cliquez sur « Ajouter un tiers ».

![Liste des tiers](img/03-tiers-liste.png)

![Fenêtre Ajouter un tiers](img/04-tiers-modale.png)

1. **Nom du tiers** : la raison sociale.
2. **Utilisateur responsable** : le commercial qui suit cette société.
3. Cliquez sur « Enregistrer ». La fiche de la société s'ouvre.

### 2.2 Passer la société en « Prospect »

> ⚠️ **Étape indispensable.** Une société nouvellement créée est « Inactive » : elle
> n'apparaîtra **pas** dans la liste des sociétés au moment de créer le lead.

Sur la fiche, onglet « Détail », réglez **Etat client** sur « Prospect »…

![Etat client = Prospect](img/05-tiers-statut-prospect.png)

… puis cliquez sur « Mettre à jour » en bas de la fiche. Un message vert confirme
l'enregistrement.

![Bouton Mettre à jour](img/06-tiers-mettre-a-jour.png)

### 2.3 Ajouter l'adresse

Onglet « Adresses » : renseignez **Tri** (1 pour la première adresse), **Libellé**
(ex. « Siège »), l'adresse, le code postal, la ville et le pays, puis « Enregistrer ».

![Nouvelle adresse](img/07-tiers-adresse.png)

> 💡 Les interrupteurs « Adresse par défaut pour DEV / CMD / BL / FAC » choisissent
> l'adresse proposée automatiquement sur chaque type de document.

### 2.4 Ajouter le contact

Onglet « Contact » : **Tri**, **Civilité**, **Prénom**, **Nom**, puis « Enregistrer ».
Le contact doit apparaître dans le tableau de gauche.

![Nouveau contact](img/08-tiers-contact.png)

> ⚠️ Renseignez toujours la **civilité**. Sans elle, le contact n'est pas enregistré et
> aucun message ne vous prévient : vérifiez qu'il apparaît bien dans le tableau.

---

## 3. Enregistrer le lead

Le lead trace l'origine du contact (salon, site web, recommandation…) et son niveau
d'intérêt.

Menu **Leads**, onglet « Liste des Lead », puis « Nouveau Lead ».

![Liste des leads](img/09-lead-liste.png)

Choisissez le **Tier** : l'adresse et le contact se chargent alors dans les deux listes
suivantes. Complétez l'**Utilisateur**, la **Priorité** (Bouillant, Chaud, Tiède, Froid)
et la **Source**, puis cliquez sur « Soumettre ».

![Fenêtre Nouveau Lead](img/10-lead-modale.png)

---

## 4. Transformer le lead en opportunité

Quand le prospect exprime un besoin concret, transformez le lead en opportunité : sur la
fiche du lead, carte « Options », cliquez sur « Nouvelle Oportunité ». Le lead passe
à l'état « Converti ».

![Fiche du lead](img/11-lead-fiche.png)

L'opportunité s'ouvre sur l'onglet **Synthèse**, qui suivra toute l'affaire : étape en
cours, points bloquants, prochaine action et montants (devisé, commandé, facturé…).

![Synthèse de l'opportunité](img/12-opportunite-synthese.png)

---

## 5. Établir le devis

### 5.1 Créer le devis

Onglet « Oportunité info ». Dans la carte « Options » (à droite, dépliez-la avec **+**),
cliquez sur « Nouveau devis ».

![Nouveau devis depuis l'opportunité](img/13-opportunite-fiche.png)

Le devis est créé à l'état **Ouvert**, avec le client, l'adresse, le contact et vos
conditions de paiement et de livraison par défaut.

![Devis créé](img/14-devis-fiche.png)

### 5.2 Ajouter une ligne

Onglet « Ligne du devis », puis cliquez sur la languette verte **Ajouter**, sur le bord droit.

![Onglet des lignes](img/15-devis-lignes-vide.png)

![Panneau Nouvelle ligne](img/16-devis-ligne-panneau.png)

1. **Description** : la désignation imprimée sur le devis.
2. **Quantité**.
3. **Prix unitaire** HT.
4. Cliquez sur « Ajouter ».

> 💡 Le panneau reste ouvert pour saisir la ligne suivante. Fermez-le avec « Annuler »
> quand vous avez terminé. Le champ « Produit » permet de partir d'un article existant.

![Ligne ajoutée](img/17-devis-ligne-ajoutee.png)

### 5.3 Décrire la gamme de fabrication

La gamme liste les opérations (découpe, pliage…). Elle sert au chiffrage ici, puis
deviendra le travail à faire en atelier une fois la commande passée.

Ouvrez le menu de la ligne (petite flèche à droite), puis « Tâches ».

![Menu de la ligne](img/18-devis-ligne-menu-taches.png)

Cliquez sur « Gérer les tâches ».

![Fenêtre des tâches](img/19-devis-ligne-modale-taches.png)

Cliquez sur la languette bleue **Opération**, sur le bord droit, puis renseignez :

![Nouvelle opération](img/20-gamme-operation.png)

1. **Service** : le poste de travail (laser, pliage…).
2. **Temps de réglage**, en minutes, pour toute la série.
3. **Temps unitaire**, en minutes par pièce.
4. Cliquez sur « Ajouter l'opération ».

Le coût et le prix se calculent à partir du taux horaire du service. Répétez l'opération
pour chaque étape, puis cliquez sur « Retour ».

![Gamme complète](img/21-gamme-complete.png)

> ⚠️ Si le service n'a pas de taux horaire, saisissez vous-même le **coût unitaire** et le
> **prix unitaire** : l'opération est refusée tant qu'ils valent 0.
>
> 💡 Les **modèles standards** (en bas de l'écran) ajoutent une gamme type en un clic.

> 💡 Le devis s'envoie au client depuis l'onglet « Information du devis » (e-mail, PDF ou
> lien public sécurisé). Il peut aussi le signer en ligne, si la signature électronique
> est activée.

---

## 6. Transformer le devis en commande

Quand le client accepte, onglet « Ligne du devis » : cochez les lignes commandées, puis
cliquez sur « Créer une commande » et confirmez.

![Créer la commande](img/22-devis-vers-commande.png)

La commande est créée avec ses lignes **et leurs gammes**. Le devis passe à l'état
« Gagné ».

![Commande créée](img/23-commande-fiche.png)

> ⚠️ Seul un devis **Ouvert** ou **Envoyé** peut être transformé en commande.
> On peut ne cocher qu'une partie des lignes si le client ne commande pas tout.

---

## 7. Déclarer la production

Chaque opération de la gamme se déclare sur son **écran de suivi** : démarrage, pièces
bonnes et rebutées, fin.

Onglet « Lignes » de la commande : cliquez sur le bouton jaune des tâches de la ligne,
puis sur « Gérer les tâches ».

![Lignes de la commande](img/24-commande-lignes.png)

Cliquez sur le libellé d'une opération pour ouvrir son écran de suivi.

![Gamme de la commande](img/25-commande-gamme.png)

Sur l'écran de suivi :

1. Cliquez sur « Play » au démarrage du travail.

   ![Démarrer](img/26-suivi-play.png)

2. Sous « Quantité acceptée », saisissez le nombre de pièces bonnes puis cliquez sur
   « Set ». Les boutons +1 / +10 / +100 servent aux déclarations au fil de l'eau.
   Les rebuts se déclarent de la même façon, sous « Quantité rejetée ».

   ![Déclarer les pièces](img/27-suivi-quantite.png)

3. Cliquez sur « Fin » quand l'opération est terminée.

   ![Terminer](img/28-suivi-fin.png)

Recommencez pour chaque opération (le bouton en haut à droite, ex. « 20 · Pliage »,
passe à la suivante). Sur la commande, la colonne « Tâches » de la ligne passe à
« Terminé ».

> 💡 « Pause » interrompt le chronomètre, par exemple pendant une coupure ou un changement d'outil.

---

## 8. Livrer : le bon de livraison

Onglet « Lignes » de la commande : cochez les lignes à livrer, cliquez sur
« Nouveau BL » et confirmez.

![Créer le bon de livraison](img/29-commande-vers-bl.png)

Le bon de livraison reprend tout ce qui reste à livrer sur les lignes cochées. Son PDF
est disponible dans la carte « Options ».

> ⚠️ Le logiciel **ne vérifie pas** que la production est déclarée avant de livrer :
> c'est à vous de vous en assurer.

---

## 9. Facturer

Sur le bon de livraison, carte « Options », cliquez sur « Nouvelle facture ».

![Facturer depuis le BL](img/30-bl-fiche.png)

La facture est créée à l'état **En cours** (brouillon), avec les lignes livrées.

![Facture créée](img/31-facture-fiche.png)

> ⚠️ **Facturez depuis le bon de livraison**, pas depuis la commande. Le bouton
> « Nouvelle facture » de la commande crée son propre bon de livraison : la facture n'est
> alors pas rattachée au BL déjà fait, qui reste « à facturer ».
>
> 💡 Son numéro lui est attribué dès sa création. Tant qu'elle est en brouillon, ses lignes
> restent modifiables mais elle n'a ni PDF ni envoi par e-mail : émettez-la pour la verrouiller
> et pouvoir l'envoyer (voir le guide « Facturer et encaisser »).

---

## Et ensuite ?

- **Émettre et envoyer la facture**, puis **enregistrer le règlement** (onglet « Règlements »).
- **Suivre l'affaire** sur la synthèse de l'opportunité : montants devisés, commandés,
  facturés et encaissés.
- **Relancer** les factures échues : menu Factures › Relances.

## Questions fréquentes

**Ma société n'apparaît pas dans « Nouveau Lead ».**
Elle est probablement encore « Inactive » : passez son « Etat client » en Prospect
(étape 2.2).

**La liste des contacts est vide dans « Nouveau Lead ».**
Le contact n'a pas été enregistré : vérifiez sur la fiche de la société, onglet Contact,
et n'oubliez pas la civilité.

**Je ne peux plus modifier les lignes ou la gamme du devis.**
Ce n'est possible qu'à l'état **Ouvert**. Un devis gagné, perdu ou fermé est figé.

**Le bouton « Nouvelle Oportunité » a disparu du lead.**
Une opportunité existe déjà pour ce lead : la carte « Options » propose de la consulter.
