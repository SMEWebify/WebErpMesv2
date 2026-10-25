---
title: From request for quotation to supplier invoice
summary: The complete purchasing process — supplier, request for quotation, purchase order, receipt, inspection and supplier invoice, including received electronic invoices.
category: Purchasing
order: 10
duration: 15
icon: fas fa-cart-shopping
can: purchases-menu
updated: 2026-10-25
---

**Goal**: buy material, supplies or subcontracting while keeping a record of each
step, and only pay for what was ordered **and** received.

> 💡 Screenshots show the French interface; button names are given as they appear in the English interface.

**Example used**: the sheet metal shop asks Aciers de l'Ouest for a quote for 4 sheets of 8 mm and
6 sheets of 3 mm, negotiates the price of the 8 mm sheet, places the order, receives it, then records
the supplier's invoice.

## Before you start

- You need the **Purchase** permission ("Purchase" menu).
- A **unit** and a **default VAT rate** must exist (see the Methods guide).
- To buy a catalogue item rather than a free line: the item must be
  set to **Purchased = Yes**, with the supplier in its **Preferreds suppliers**.

## Overview

```steps
Supplier
Request for quotation
Purchase order
Receipt
Supplier invoice
```

Each document is created **from the previous one**. The "Purchase" menu follows the same
order, and its counters ("Waiting to receipt", "Waiting to invoice")
show what is left to do.

> 💡 In a hurry? You can skip the request for quotation: **Purchase list** menu, list tab,
> "New purchase document" button.

---

## 1. Set up the supplier

A supplier is a third party like any other (**Third party** menu, "New third party"). Three
settings make it usable in purchasing.

**1.1 Activate it as a supplier.** "Detail" tab of the record: set **Status
supplier** to "Active", then click "Update".

![Status supplier Active](img/01-fournisseur-etat.png)

> ⚠️ An "Inactive" supplier does not appear in **any** purchasing list.

**1.2 Its default address.** "Address" tab: enter the address and **tick
"By default"**.

![Default address](img/02-fournisseur-adresse.png)

**1.3 Its default contact.** "Contacts" tab: title, first name, last name, e-mail (to
send them documents) and **tick "By default"**.

![Default contact](img/03-fournisseur-contact.png)

> ⚠️ Without a "By default" address **and** contact, creating a request for quotation and
> turning it into an order fail: they are the ones copied onto the documents.
>
> 💡 "Reception control" = Yes (Detail tab) requires an inspection at every
> receipt from this supplier.

---

## 2. Ask the supplier for a quote: the request for quotation

### 2.1 Start from the "Purchase request" screen

**Purchase › Purchase request** menu. The table lists the **needs from customer orders**:
material and subcontracting planned in the routings, not yet purchased.

![Purchase request](img/04-demande-achat.png)

1. **Document type**: "Purchase quotation" to ask for a quote, or "Purchase Order" to
   order directly.
2. **Select suppliers**: one or more. Each supplier receives its
   own request for quotation, so you can compare them.
3. **Label** of the request.
4. Tick the needs concerned ("Add to new document"), then click
   "New purchase document".

![Create the request for quotation](img/05-demande-prix-creation.png)

> 💡 If you tick nothing, you create an empty request for quotation, to fill in afterwards.
> This is the case in our example.
>
> 💡 On the operator screen, a **subcontracting** operation shows a
> "New purchase document" button instead of Play: it takes you to this screen.

### 2.2 Add the lines

"Purchase quotation lines" tab. For each line: **Description**, **Qty** (0 by default,
to fill in) and **Unit Price** (the expected price, or 0), then "Submit". The
"Product" field picks an item from the catalogue.

![Add a line](img/06-demande-prix-ligne.png)

![Lines of the request](img/07-demande-prix-lignes.png)

> ⚠️ Lines can only be changed while the request is **"En cours" (In progress)**.
> Complete them before sending it.

### 2.3 Send the request

"Purchase quotation info" tab, **Options** card: "PDF" to print or save it,
"E-mail" to send it to the supplier's contact. Then set the request to
**"Envoyé" (Sent)** by clicking the matching step at the top.

![Send the request](img/08-demande-prix-envoi.png)

![Request sent](img/09-demande-prix-envoyee.png)

> 💡 The PDF button only appears if the request has an address and a contact.
> "Compare suppliers" shows the answers to the same request side by side.

---

## 3. Order

### 3.1 Turn the request for quotation into an order

When you receive the offer, "Purchase quotation lines" tab:

1. Enter the **Proposed purchase price** from the supplier (here €295 instead of €310).
2. Tick the lines you keep ("+" box).
3. Click "New order".

![Turn into an order](img/10-demande-prix-conversion.png)

The purchase order is created with the negotiated prices and the supplier's default address
and contact.

![Purchase order created](img/11-commande-achat.png)

> ⚠️ If no line is ticked, the page reloads without any message: check the boxes.

### 3.2 Check and send the order

"Purchase lines" tab: check quantities, prices and discounts. The **"Ajouter" (Add)** button, on the
right edge, adds a line.

![Order lines](img/12-commande-achat-lignes.png)

Send the order to the supplier from the **Options** card (PDF or e-mail), then
set it to **"Ordered"**.

![Set to ordered](img/13-commande-achat-statut.png)

![Order in progress at the supplier](img/14-commande-achat-en-commande.png)

> ⚠️ An "Ordered" order is **locked**: its lines can no longer be changed.
> The next statuses ("Partly received", "Received") move forward on their own with the
> receipts.

---

## 4. Receive

**Purchase › Waiting to receipt** menu: everything ordered and not yet received.

1. **Sort third party**: the supplier who delivers.
2. **Delivery note number**: the number of the supplier's delivery note, to find the delivery again.
3. Tick the delivered lines.
4. Choose the **User management** (responsible user), then click "New receipt document".

![Waiting to receipt](img/15-reception-attente.png)

> ⚠️ A ticked line is received **in full**. For a partial delivery, only tick
> the complete lines.
>
> ⚠️ A line already received may still be shown: do not tick it a second time, you
> would create a second receipt.

The receipt note opens. For each line, the **Inspection details** button lets you
record the inspection: accepted and rejected quantities, result, and if needed a
**non-conformity** created in one click.

![Receipt note](img/16-reception-lignes.png)

> 💡 For a line linked to an **item**, the "Mise en stock" (Put into stock) button stores the
> accepted quantity in a stock location.

---

## 5. Record the supplier invoice

**Purchase › Waiting to invoice** menu: receipts not yet invoiced.

1. **Sort third party**: the supplier.
2. **"Référence fournisseur" (Supplier reference)**: the number of **their** invoice.
3. Tick the invoiced receipts.
4. Choose the **User management** (responsible user), then click "New invoice document".

![Waiting to invoice](img/17-facture-attente.png)

![Supplier invoice](img/18-facture-fiche.png)

You can only invoice **what has been received**, and at the order price: this is what
ensures you pay the right amount. Once the invoice is checked, set its status
to "Closed".

> ⚠️ The supplier reference is unique for a supplier: the software refuses
> to record the same invoice twice.
>
> 💡 If the accounting assignments are set up, each invoice generates its purchase
> entries, included in the FEC export.

---

## Received electronic invoices

With electronic invoicing, supplier invoices arrive in **Factur-X** format.
**Purchase › Received invoices** menu: those sent by your platform arrive there
on their own, and you can also upload a Factur-X PDF or an XML file by hand.

![Received invoices](img/19-factures-recues.png)

On each received invoice:

- **"Rapprocher" (Match)** opens "Waiting to invoice" for this supplier, with the received
  document next to the receipts to tick and any difference in amount;
- **"Sans rapprochement" (Without matching)** records an invoice without an order (expenses, subscription);
- **"Refuser" (Reject)** sets the document aside.

## What next?

- **Rate the supplier**: rate them on the order ("Supplier rate") to track their
  quality of service.
- **Link to the deal**: purchases made for a customer order appear on the
  summary of its opportunity (amount purchased, late purchases).

## Frequently asked questions

**My supplier does not appear in the lists.**
Its "Status supplier" is still "Inactive" (step 1.1).

**"No default settings for defaultAddress" when creating the request for quotation.**
The supplier has no "By default" address or contact (steps 1.2 and 1.3).

**I can no longer add a line to my order.**
It is no longer "In Progress": an order sent to the supplier is locked.

**The item search does not find my product in the order.**
The supplier is not in the item's preferred suppliers.

**The supplier only delivered part of the order.**
Receive the delivered lines. The others stay in "Waiting to receipt"
until the next delivery.
