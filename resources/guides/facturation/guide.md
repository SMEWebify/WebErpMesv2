---
title: Facturer et encaisser
summary: De la facture brouillon au paiement — émettre, envoyer, enregistrer les règlements, relancer les impayés, faire un avoir et transmettre les écritures au comptable.
category: Ventes
order: 20
duration: 15
icon: fas fa-file-invoice-dollar
can: invoices-menu
updated: 2026-10-26
---

**Objectif** : transformer une facture brouillon en facture émise, suivre son paiement
jusqu'au solde, relancer si besoin, et transmettre les écritures au comptable.

**Point de départ** : une facture **brouillon**, créée depuis un bon de livraison (voir le
guide « Du prospect à la facture »).

## Avant de commencer

- Il faut la permission **Factures**. L'envoi des relances demande en plus la permission
  dédiée (rôle Admin par défaut), et l'export comptable l'accès au menu **Votre entreprise**.
- Pour la facture électronique (Factur-X) et l'envoi par e-mail : le **SIREN**, le
  **n° de TVA** et l'**adresse** de votre société (Votre entreprise › Réglage société) et
  du client doivent être renseignés.
- Les **modes de paiement** et les **affectations comptables** (menu Comptabilité)
  déterminent les comptes des écritures.

## Vue d'ensemble

```steps
Brouillon
Émission
Envoi
Règlements
Relances
Export comptable
```

---

## 1. Vérifier le brouillon

Une facture brouillon (statut **En cours**) reste entièrement modifiable : quantités, prix
et remises se corrigent directement dans l'onglet « Lignes de facture ».

![Brouillon](img/01-brouillon-lignes.png)

1. Le bandeau rappelle que la facture est un **brouillon**.
2. Le bouton **Émettre la facture** la verrouille (étape 2).

Pour facturer un élément non commandé (frais de port, frais de dossier), remplissez
« Ajouter une ligne » : **Désignation**, **Qté**, **P.U HT**, puis « Ajouter ».

![Ajouter une ligne](img/02-brouillon-ajout-ligne.png)

Onglet « Facture info » : vérifiez le client, l'adresse, le contact et la **date
d'échéance** (calculée depuis la condition de paiement de la commande), puis « Mettre à
jours ».

![Date d'échéance](img/03-brouillon-echeance.png)

> ⚠️ Le **numéro** de la facture lui est attribué dès sa création, brouillon compris.
> Faites toutes vos corrections **avant** l'émission.

---

## 2. Émettre la facture

Onglet « Lignes de facture » : cliquez sur **Émettre la facture** et confirmez. La facture
passe au statut **Envoyé** : ses lignes sont verrouillées, et les boutons **PDF** et
**E-mail** apparaissent dans la carte Options.

![Facture émise](img/04-emise.png)

La version **Factur-X** (le PDF qui contient les données structurées de la facture
électronique) se télécharge depuis la liste des factures, avec le bouton jaune.

![Bouton Factur-X](img/05-liste-facturx.png)

> ⚠️ Les étapes du bandeau en haut de la fiche (En cours, Envoyé, Payé…) **changent le
> statut immédiatement, sans confirmation**. Ne vous en servez pas pour émettre ou solder
> une facture : utilisez « Émettre la facture » et l'onglet « Règlements ».
>
> 💡 Si le Factur-X affiche une erreur, il manque une information d'identification
> (SIREN, n° de TVA, adresse) sur votre société ou sur le client : le message liste
> ce qui manque.

**Envoyer la facture** : le bouton **E-mail** de la carte Options prépare un message au
contact de la facture, avec le PDF joint.

---

## 3. Enregistrer les règlements

Onglet « Règlements » : saisissez chaque paiement reçu.

1. **Montant** : le reste dû est proposé ; un paiement partiel est accepté.
2. **Méthode** : virement, chèque… (elle détermine le compte de trésorerie).
3. **Référence** : n° de virement ou de chèque.
4. Cliquez sur « Enregistrer ».

![Saisir un règlement](img/06-reglement-saisie.png)

Le bandeau affiche ce qui est réglé et le **reste dû**. Un premier règlement partiel fait
passer la facture à **En attente**.

![Règlement partiel](img/07-reglement-partiel.png)

Quand le reste dû tombe à zéro, la facture passe à **Payé** et l'onglet affiche **Soldée**.

![Facture soldée](img/11-soldee.png)

> ⚠️ N'enregistrez un règlement que sur une facture **émise** : le logiciel l'accepte sur
> un brouillon, mais la facture changerait de statut sans avoir été émise.
>
> 💡 Chaque règlement génère son écriture d'encaissement (journal ENCAIS).

---

## 4. Relancer les impayés

Menu **Factures › Factures à relancer** : les factures **émises, échues et non soldées**,
regroupées par client. **Rien n'est envoyé automatiquement** : chaque relance part de votre
décision.

![Factures à relancer](img/08-relances-liste.png)

Le **niveau proposé** suit le retard, sans jamais sauter d'étape :

| Niveau | Délai après l'échéance |
|---|---|
| Rappel | J+1 |
| Relance | J+15 |
| Mise en demeure | J+30 |

Deux façons de relancer un client :

- **Relancer par e-mail** : un message par client, avec le PDF de chaque facture en retard ;
- **Tracer un appel / courrier** : pour garder la trace d'un appel ou d'une lettre, sans
  e-mail. Choisissez le **canal**, notez ce qui a été dit (promesse de paiement…), puis
  « Enregistrer la relance ».

![Tracer un appel](img/09-relance-appel.png)

![Relance enregistrée](img/10-relance-enregistree.png)

Chaque relance fait monter le niveau de la facture et s'affiche dans la carte **Relances**
de sa fiche.

![Historique des relances](img/12-historique-relances.png)

> 💡 Les textes des e-mails de relance se règlent dans « Niveaux de relance » (bouton en
> haut à droite). Une relance tracée ne se supprime pas.

---

## 5. Faire un avoir

Un avoir annule tout ou partie d'une facture **émise** (retour, geste commercial, erreur).
Onglet « Lignes de facture » : cochez les lignes concernées, puis **Nouvel avoir**.

![Créer un avoir](img/13-avoir-creation.png)

L'avoir s'ouvre au statut **En attente**. Passez-le à **Approuvée** une fois validé, ou à
**Refusée** s'il ne doit pas compter. Son PDF se trouve dans la carte Options.

![Fiche avoir](img/14-avoir-fiche.png)

> ⚠️ Une ligne cochée est reprise **pour sa quantité entière**. Un avoir ne se supprime
> pas : vérifiez la sélection avant de cliquer.
>
> ⚠️ Les lignes de commande concernées redeviennent « à facturer » : pensez-y si vous
> refacturez ensuite.
>
> 💡 Un avoir « En attente » ou « Approuvée » est déduit du reste dû de la facture dans
> les relances et l'encours client ; un avoir « Refusée » ne l'est pas.

---

## 6. Suivre l'encours client

Fiche société, onglet **Tableau de bord**, carte **Encours client** : ce que le client vous
doit (factures non soldées) et ce qu'il a commandé sans être encore facturé.

![Encours client](img/15-encours.png)

> 💡 Onglet « Détail » de la fiche, un **plafond d'encours** peut déclencher une alerte ou
> bloquer les nouvelles commandes et BL au-delà d'un montant. La facturation, elle, n'est
> jamais bloquée.

---

## 7. Transmettre au comptable

Menu **Votre entreprise › Imports/Exports**, onglet **Export FEC** :

1. Cochez les **journaux** : ventes (VENT), achats (ACHAT) et encaissements (ENCAIS,
   décoché par défaut).
2. Choisissez la **période**, puis « Soumettre » : les écritures à exporter s'affichent.
3. Cochez les écritures, puis **CSV**, **XLS** ou **PDF**.

![Export FEC](img/16-export-fec.png)

> ⚠️ L'export est **définitif** : les écritures exportées sortent de la liste et ne se
> réexportent pas. Faites un seul export par période et conservez le fichier.
>
> 💡 L'onglet « Exporter ligne de facture » produit la liste des lignes facturées, pour
> un tableur ou un autre logiciel.

---

## La facturation électronique

Menu **Votre entreprise › Intégrations**, carte **Facturation électronique (PDP)** :
la plateforme utilisée pour transmettre les factures et son état.

![Intégrations](img/17-integrations-pdp.png)

Quand une plateforme est configurée, chaque facture affiche une carte **Facturation
électronique** avec son statut (déposée, reçue, acceptée, payée…) et un bouton de dépôt.

> ⚠️ Un numéro de facture déposé sur la plateforme est **consommé**, même si la facture
> est rejetée : une facture rejetée se corrige par une nouvelle facture, pas par un
> nouveau dépôt.

## Questions fréquentes

**Je ne vois ni « PDF » ni « E-mail ».**
La facture est encore un brouillon : émettez-la (étape 2).

**Ma facture n'apparaît pas dans les relances.**
Elle n'est pas échue, pas émise, ou déjà soldée.

**Le Factur-X ou l'e-mail est refusé.**
Il manque le SIREN, le n° de TVA ou l'adresse de votre société ou du client.

**Je veux facturer sans bon de livraison.**
Depuis la commande, cochez les lignes et cliquez sur « Nouvelle facture » : un BL est créé
automatiquement. Ne le faites pas si un BL existe déjà pour ces lignes.

**Une proforma, c'est quoi ?**
Un document de demande de paiement avant livraison (menu Factures › Proformas). Elle n'est
pas une facture : convertie, elle devient une facture brouillon avec son propre numéro.
