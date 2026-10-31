---
title: From prospect to invoice
summary: The complete sales process — prospect, lead, opportunity and its risks, quote with routing, order, production reporting, delivery note and invoice.
category: Sales
order: 10
duration: 25
icon: fas fa-route
updated: 2026-10-31
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
- Optional: **business categories** and **loss reasons** (Methods menu).

## Overview

```steps
Prospect
Lead
Opportunity
Quote (line + routing)
Order
<!-- feature: production-release -->
Released for production
<!-- /feature -->
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
stage, blocking points, next action, amounts (quoted, ordered, invoiced…) and critical
risks.

![Opportunity summary](img/12-opportunite-synthese.png)

### 4.1 List the risks of the deal

A tricky technical point, a tight lead time, hard-to-find material: record it now in the
**risk register**. It follows the deal until delivery.

"Risks" tab, then click "New risk".

![Risks tab](img/13-opportunite-risques.png)

![New risk form](img/14-opportunite-risque-form.png)

1. **Risk**: what could go wrong, in one sentence. Also choose its **Category**
   (technical, lead time, quality…).
2. **Probability**, from 1 (low) to 4 (very high).
3. **Severity**, from 1 to 4. The **Criticality** (probability × severity) is calculated
   automatically.
4. **Mitigation action**: what you will do to avoid it. Also name an **Owner** and set a
   **Due date**.
5. Click "Save".

![Risk register](img/15-opportunite-risques-liste.png)

A **critical** risk (criticality 9 or more, still open) or one whose due date has passed
shows up in the blocking points of the Summary and in the "Open critical risks" card.
To update it, click its row and change its **Status**: "Mitigated", "Closed" or
"Occurred".

> 💡 A risk is never deleted: close it. Closing a critical risk is reserved for people who
> have the right to do so.

---

## 5. Prepare the quote

### 5.1 Create the quote

"Opportunity info" tab. If your administrator has set up **business categories**,
choose one in "Business category", then click "Update" at the bottom of the form.
It will be carried over to the quote, then to the order.

![Business category](img/16-opportunite-categorie.png)

In the "Options" card (on the right, expand it with **+**), click "New Quote".

![New quote from the opportunity](img/17-opportunite-fiche.png)

The quote is created with the **Open** status, with the customer, the address, the contact,
the business category and your default payment and delivery terms.

![Quote created](img/18-devis-fiche.png)

1. The quote information is under the **"i"** tab, the first one.
2. The "Review" tab records the review of the quote (who, when, decision).
3. The **Business category** is taken from the opportunity.
4. The "Options" card groups the PDF, "E-mail", the public link and, under "Copy…",
   the quote copies ("Duplicate quote", "New revision", "Variant…",
   "Save as template…").

> 💡 On the right, the "Information" (totals), "Margin (internal)" and "Delivery lead-time
> simulation" cards are collapsed: open them with **+**. Revisions, variants, templates
> and margin are covered in the "Advanced quotes" guide.

> ⚠️ A business category can require certain operations in the routing (step 5.3): a
> "Required operations missing" banner then flags them on the lines tab, and turning the
> quote into an order is refused while they are missing, unless an authorised person
> gives a justified override.

### 5.2 Add a line

"Quote lines" tab, then click the green **« Ajouter » (Add)** tab on the right edge.

![Lines tab](img/19-devis-lignes-vide.png)

![New line panel](img/20-devis-ligne-panneau.png)

1. **Description**: the item description printed on the quote.
2. **« Quantité » (Quantity)**.
3. **« Prix unitaire » (Unit price)** excl. VAT.
4. Click « Ajouter » (Add).

> 💡 The panel stays open so you can enter the next line. Close it with « Annuler » (Cancel)
> when you have finished. The « Produit » (Product) field lets you start from an existing item.

![Line added](img/21-devis-ligne-ajoutee.png)

### 5.3 Describe the manufacturing routing

The routing lists the operations (cutting, bending…). It is used for costing here, then
becomes the work to do in the workshop once the order is placed.

Open the line menu (small arrow on the right), then « Tâches » (Tasks).

![Line menu](img/22-devis-ligne-menu-taches.png)

Click « Gérer les tâches » (Manage tasks).

![Tasks window](img/23-devis-ligne-modale-taches.png)

Click the blue **« Opération » (Operation)** tab on the right edge, then fill in:

![New operation](img/24-gamme-operation.png)

1. **Service**: the workstation (laser, bending…).
2. **« Temps de réglage » (Setup time)**, in minutes, for the whole batch.
3. **« Temps unitaire » (Unit time)**, in minutes per part.
4. Click « Ajouter l'opération » (Add the operation).

The cost and price are calculated from the service's hourly rate. Repeat for
each step, then click "Back".

![Complete routing](img/25-gamme-complete.png)

> ⚠️ If the service has no hourly rate, enter the **« Coût unitaire » (Unit cost)** and the
> **« Prix unitaire » (Unit price)** yourself: the operation is refused as long as they are 0.
>
> 💡 The **« Modèles standards » (Standard templates)** (at the bottom of the screen) add a typical routing in one click.

> 💡 The quote is sent to the customer from the **"i"** tab, "Options" card ("E-mail", PDF
> or "Public secure link"). The customer can also sign it online, if electronic signature
> is enabled.

---

## 6. Turn the quote into an order

When the customer accepts, "Quote lines" tab: tick the ordered lines, then
click « Créer une commande » (Create an order) and confirm.

![Create the order](img/26-devis-vers-commande.png)

The order is created with its lines **and their routings**. The quote changes to the
"Won" status.

![Order created](img/27-commande-fiche.png)

1. The "Contract review" tab is where you check that the order matches the offer
   (checklist, discrepancies, sign-off).
2. As long as the review is not signed off, a yellow banner reminds you; "Open the review"
   leads to it. It does not hold up the rest of the process: see the "Order contract
   review" guide.

> ⚠️ Only an **Open** or **Sent** quote can be turned into an order.
> You can tick only some of the lines if the customer does not order everything.

> 💡 **The customer says no?** Set the quote to "Lost" with the status arrows at the top of
> the record. The "Quote lost: why?" window asks for a **reason** (price, lead time,
> competitor…) and an optional comment: without a reason, the quote does not become Lost.
> The same applies to an opportunity set to lost. These reasons feed the "Lost business"
> card of the quotes dashboard.

---

## 7. Report production

Each operation in the routing is reported on its **tracking screen**: start, good
and rejected parts, end.

<!-- feature: production-release -->
### 7.1 Release the line for production

In your company, an order line is only manufactured once it is **released for
production** (the engineering office's go-ahead). Without it, the "Play" button of the
tracking screen stays greyed out and a banner with a padlock explains why.

"Lines" tab of the order:

![Line not released](img/28-commande-bpf.png)

1. The "Not released" badge flags a line that has not been released yet.
2. Tick the line.
3. Click "Release for production (1)".

The line then shows the green **Released** badge: the workshop can start.

![Line released](img/29-commande-bpf-donne.png)

> ⚠️ This button is only shown to people allowed to release lines for production. If you
> do not see it, ask the engineering office. Revoking, refusals and special cases: see the
> "Release for production" guide.

### 7.2 Report each operation

<!-- /feature -->
"Lines" tab of the order: click the yellow tasks button of the line,
then « Gérer les tâches » (Manage tasks).

![Order lines](img/30-commande-lignes.png)

Click the name of an operation to open its tracking screen.

![Order routing](img/31-commande-gamme.png)

On the tracking screen:

1. Click "Play" when work starts.

   ![Start](img/32-suivi-play.png)

2. Under "Good quantity", enter the number of good parts then click
   "Set". The +1 / +10 / +100 buttons are for reporting as you go.
   Scrap is reported the same way, under "Rejected quantity".

   ![Report the parts](img/33-suivi-quantite.png)

3. Click "End" when the operation is finished.

   ![Finish](img/34-suivi-fin.png)

Repeat for each operation (the button at the top right, e.g. "20 · Pliage",
moves to the next one). On the order, the « Tâches » (Tasks) column of the line changes to
« Terminé » (Finished).

> 💡 "Pause" stops the timer, for example during a break or a tool change.

---

## 8. Deliver: the delivery note

"Lines" tab of the order: tick the lines to deliver, click
« Nouveau BL » (New delivery note) and confirm.

![Create the delivery note](img/35-commande-vers-bl.png)

The delivery note takes everything that is still to be delivered on the ticked lines. Its PDF
is available in the "Options" card.

> ⚠️ The software **does not check** that production has been reported before delivery:
> it is up to you to make sure.

---

## 9. Invoice

On the delivery note, "Options" card, click "New invoice".

![Invoice from the delivery note](img/36-bl-fiche.png)

The invoice is created with the **In progress** status (draft), with the delivered lines.

![Invoice created](img/37-facture-fiche.png)

> ⚠️ **Invoice from the delivery note**, not from the order. The order's
> "New invoice" button creates its own delivery note: the invoice is then
> not linked to the delivery note you already made, which stays "to be invoiced".
>
> 💡 Its number is assigned as soon as it is created. While it is a draft, its lines
> can still be changed but it has no PDF and cannot be sent by email: issue it to lock it
> and be able to send it (see the "Invoice and get paid" guide).

---

## What next?

- **Issue and send the invoice**, then **record the payment** (« Règlements » (Payments) tab).
- **Follow the deal** on the opportunity summary: amounts quoted, ordered,
  invoiced and collected, blocking points and risks.
- **Sign off the contract review** of the order: "Contract review, amendments and order pricing" guide.
- **Revise or vary a quote** (revision, variant, template, margin): "Advanced quotes" guide.
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

**I cannot find the "Business category" field.**
It only appears once your administrator has created categories (Methods ›
Business categories menu).

**I cannot set the quote to "Lost".**
A loss reason is required: choose it in the window that opens. Reasons are managed in
the Methods › Loss reasons menu.

<!-- feature: production-release -->
**"Play" is greyed out on the tracking screen.**
The order line has not been released for production yet: see step 7.1.

<!-- /feature -->
**The "New opportunity" button has disappeared from the lead.**
An opportunity already exists for this lead: the "Options" card offers to view it.
