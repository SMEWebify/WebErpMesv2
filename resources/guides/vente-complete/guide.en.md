---
title: From prospect to invoice
summary: The complete sales process — prospect, lead, opportunity, quote with routing, order, production reporting, delivery note and invoice.
category: Sales
order: 10
duration: 20
icon: fas fa-route
updated: 2026-10-25
---

**Goal**: follow a deal from start to finish in Nest2Prod ERP, from the first contact
with a prospect to the invoice, through the quote, the order, manufacturing
and delivery.

> 💡 Screenshots show the French interface; button names are given as they appear in the English interface.

**Example used**: Métallerie Durand, met at a trade show, orders 20 plates
in 8 mm S235 steel, laser cut then bent, at €42.50 excl. VAT each.

## Before you start

- You must have access to the Third party, Leads, Opportunities, Quotes, Orders,
  Delivery notes and Invoices menus.
- The basic setup must be done by your administrator: payment terms and payment
  methods, delivery methods, VAT rates, units, and the manufacturing **services**
  (laser, bending…) with their hourly rate.

## Overview

```steps
Prospect
Lead
Opportunity
Quote (line + routing)
Order
Production reported
Delivery note
Invoice
```

Each document is created **from the previous one**: you never re-enter the customer,
the address or the lines. The breadcrumb at the top of each document (Lead › Opportunity ›
Quote › Order…) lets you trace the history of the deal.

---

## 1. Log in

![Login screen](img/01-connexion.png)

1. Enter your **Email**.
2. Enter your **Password**.
3. Click "Sign In".

You arrive on the dashboard.

![Dashboard](img/02-tableau-de-bord.png)

---

## 2. Create the prospect

A prospect is a company that is not a customer yet. You must create it with **an
address and a contact**: the lead and the quote need them.

### 2.1 Add the company

**Third party** menu, "Third party list" tab, then click "New third party".

![Third party list](img/03-tiers-liste.png)

![New third party window](img/04-tiers-modale.png)

1. **Name of third party**: the company name.
2. **User management**: the salesperson who follows this company.
3. Click "Save". The company record opens.

### 2.2 Set the company to "Prospect"

> ⚠️ **Essential step.** A newly created company is "Inactive": it will
> **not** appear in the list of companies when you create the lead.

On the record, "Detail" tab, set **Status client** to "Prospect"…

![Status client = Prospect](img/05-tiers-statut-prospect.png)

… then click "Update" at the bottom of the record. A green message confirms
that it has been saved.

![Update button](img/06-tiers-mettre-a-jour.png)

### 2.3 Add the address

"Address" tab: fill in **Sort** (1 for the first address), **Label**
(e.g. "Head office"), the address, postcode, city and country, then "Save".

![New address](img/07-tiers-adresse.png)

> 💡 The « Adresse par défaut pour » (Default address for) DEV / CMD / BL / FAC switches choose
> the address suggested automatically on each type of document.

### 2.4 Add the contact

"Contacts" tab: **Sort**, **Civility**, **First name**, **Name**, then "Save".
The contact must appear in the table on the left.

![New contact](img/08-tiers-contact.png)

> ⚠️ Always fill in the **civility**. Without it, the contact is not saved and
> no message warns you: check that it does appear in the table.

---

## 3. Record the lead

The lead records where the contact came from (trade show, website, referral…) and how
interested they are.

**Leads** menu, "Lead list" tab, then "New Lead".

![Lead list](img/09-lead-liste.png)

Choose the **Third party**: the address and the contact then load into the next two
lists. Fill in the **User**, the **Priority** (Burning, Hot, Lukewarm, Cold)
and the **Source**, then click "Submit".

![New Lead window](img/10-lead-modale.png)

---

## 4. Turn the lead into an opportunity

When the prospect has a real need, turn the lead into an opportunity: on the
lead record, "Options" card, click "New opportunity". The lead changes
to the "Converted" status.

![Lead record](img/11-lead-fiche.png)

The opportunity opens on the **Summary** tab, which will follow the whole deal: current
stage, blocking points, next action and amounts (quoted, ordered, invoiced…).

![Opportunity summary](img/12-opportunite-synthese.png)

---

## 5. Prepare the quote

### 5.1 Create the quote

"Opportunity info" tab. In the "Options" card (on the right, expand it with **+**),
click "New Quote".

![New quote from the opportunity](img/13-opportunite-fiche.png)

The quote is created with the **Open** status, with the customer, the address, the contact
and your default payment and delivery terms.

![Quote created](img/14-devis-fiche.png)

### 5.2 Add a line

"Quote lines" tab, then click the green **« Ajouter » (Add)** tab on the right edge.

![Lines tab](img/15-devis-lignes-vide.png)

![New line panel](img/16-devis-ligne-panneau.png)

1. **Description**: the item description printed on the quote.
2. **« Quantité » (Quantity)**.
3. **« Prix unitaire » (Unit price)** excl. VAT.
4. Click « Ajouter » (Add).

> 💡 The panel stays open so you can enter the next line. Close it with « Annuler » (Cancel)
> when you have finished. The « Produit » (Product) field lets you start from an existing item.

![Line added](img/17-devis-ligne-ajoutee.png)

### 5.3 Describe the manufacturing routing

The routing lists the operations (cutting, bending…). It is used for costing here, then
becomes the work to do in the workshop once the order is placed.

Open the line menu (small arrow on the right), then « Tâches » (Tasks).

![Line menu](img/18-devis-ligne-menu-taches.png)

Click « Gérer les tâches » (Manage tasks).

![Tasks window](img/19-devis-ligne-modale-taches.png)

Click the blue **« Opération » (Operation)** tab on the right edge, then fill in:

![New operation](img/20-gamme-operation.png)

1. **Service**: the workstation (laser, bending…).
2. **« Temps de réglage » (Setup time)**, in minutes, for the whole batch.
3. **« Temps unitaire » (Unit time)**, in minutes per part.
4. Click « Ajouter l'opération » (Add the operation).

The cost and price are calculated from the service's hourly rate. Repeat for
each step, then click "Back".

![Complete routing](img/21-gamme-complete.png)

> ⚠️ If the service has no hourly rate, enter the **« Coût unitaire » (Unit cost)** and the
> **« Prix unitaire » (Unit price)** yourself: the operation is refused as long as they are 0.
>
> 💡 The **« Modèles standards » (Standard templates)** (at the bottom of the screen) add a typical routing in one click.

> 💡 The quote is sent to the customer from the "Quote info" tab (email, PDF or
> secure public link). The customer can also sign it online, if electronic signature
> is enabled.

---

## 6. Turn the quote into an order

When the customer accepts, "Quote lines" tab: tick the ordered lines, then
click « Créer une commande » (Create an order) and confirm.

![Create the order](img/22-devis-vers-commande.png)

The order is created with its lines **and their routings**. The quote changes to the
"Won" status.

![Order created](img/23-commande-fiche.png)

> ⚠️ Only an **Open** or **Sent** quote can be turned into an order.
> You can tick only some of the lines if the customer does not order everything.

---

## 7. Report production

Each operation in the routing is reported on its **tracking screen**: start, good
and rejected parts, end.

"Lines" tab of the order: click the yellow tasks button of the line,
then « Gérer les tâches » (Manage tasks).

![Order lines](img/24-commande-lignes.png)

Click the name of an operation to open its tracking screen.

![Order routing](img/25-commande-gamme.png)

On the tracking screen:

1. Click "Play" when work starts.

   ![Start](img/26-suivi-play.png)

2. Under "Good quantity", enter the number of good parts then click
   "Set". The +1 / +10 / +100 buttons are for reporting as you go.
   Scrap is reported the same way, under "Rejected quantity".

   ![Report the parts](img/27-suivi-quantite.png)

3. Click "End" when the operation is finished.

   ![Finish](img/28-suivi-fin.png)

Repeat for each operation (the button at the top right, e.g. "20 · Pliage",
moves to the next one). On the order, the « Tâches » (Tasks) column of the line changes to
« Terminé » (Finished).

> 💡 "Pause" stops the timer, for example during a break or a tool change.

---

## 8. Deliver: the delivery note

"Lines" tab of the order: tick the lines to deliver, click
« Nouveau BL » (New delivery note) and confirm.

![Create the delivery note](img/29-commande-vers-bl.png)

The delivery note takes everything that is still to be delivered on the ticked lines. Its PDF
is available in the "Options" card.

> ⚠️ The software **does not check** that production has been reported before delivery:
> it is up to you to make sure.

---

## 9. Invoice

On the delivery note, "Options" card, click "New invoice".

![Invoice from the delivery note](img/30-bl-fiche.png)

The invoice is created with the **In progress** status (draft), with the delivered lines.

![Invoice created](img/31-facture-fiche.png)

> ⚠️ **Invoice from the delivery note**, not from the order. The order's
> "New invoice" button creates its own delivery note: the invoice is then
> not linked to the delivery note you already made, which stays "to be invoiced".
>
> 💡 Its number is assigned as soon as it is created. While it is a draft, its lines
> can still be changed but it has no PDF and cannot be sent by email: issue it to lock it
> and be able to send it (see the "Invoice and collect payment" guide).

---

## What next?

- **Issue and send the invoice**, then **record the payment** (« Règlements » (Payments) tab).
- **Follow the deal** on the opportunity summary: amounts quoted, ordered,
  invoiced and collected.
- **Chase** overdue invoices: Invoices › "Invoices to chase" menu.

## Frequently asked questions

**My company does not appear in "New Lead".**
It is probably still "Inactive": set its "Status client" to Prospect
(step 2.2).

**The contact list is empty in "New Lead".**
The contact was not saved: check on the company record, Contacts tab,
and do not forget the civility.

**I can no longer change the quote lines or routing.**
This is only possible with the **Open** status. A won, lost or closed quote is locked.

**The "New opportunity" button has disappeared from the lead.**
An opportunity already exists for this lead: the "Options" card offers to view it.
