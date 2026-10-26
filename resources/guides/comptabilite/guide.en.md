---
title: Accounting — setup and link with the accountant
summary: What the ERP posts as accounting entries and how to set it up — VAT codes, accounting allocations, payment methods and terms, VAT regimes and matrices, period locking and FEC export.
category: Setup
order: 30
duration: 20
icon: fas fa-calculator
can: accounting-menu
updated: 2026-10-26
---

**Goal**: set up accounting once and for all, with your accountant, so that every invoice,
payment and supplier invoice produces correct entries in the FEC file (the French
standard audit file).

> 💡 Screenshots show the French interface; button names are given as they appear in the English interface.

**Who is it for**: the manager or the person who prepares documents for the accounting
firm.

## Before you start

- The **Accounting** menu appears with the **accounting-menu** permission.
- The **Regimes / Natures**, **VAT matrices** and **Periods** screens also require
  **accounting-manage** (given to the Admin role).
- The FEC export is in **Your company › Imports/Exports** (**your-company-menu**
  permission).
- Ask your accountant for: the chart of accounts (sales, purchases, output and input VAT),
  the bank and cash accounts, and the expected FEC format.

## Overview

```steps
VAT codes
Allocations
Payments
Matrices (advanced)
Periods
FEC export
```

The **Accounting** menu opens a tabbed page: Payment terms, Payment method, VAT, Accounting
allocation, Delivery method, Fixed assets, Regimes / Natures, and under **More**: the two
VAT matrices and Periods.

**What the ERP posts:**

| Journal | When | Entry |
|---|---|---|
| **VENT** (sales) | when each invoice line is created, from the draft onwards | customer 411 debited (incl. VAT), revenue and output VAT credited |
| **VENT** (credit note) | when the credit note is created | the reverse of the sale |
| **ENCAIS** (receipts) | when a payment is recorded | cash account debited, customer 411 credited |
| **ACHAT** (purchases) | when a supplier invoice is created from receipts | expense and input VAT debited, supplier 401 credited |

Changing or deleting a document **before** export deletes and recreates its entry;
**after** export, a reversing entry is posted on the current date. An entry sent to the
accountant is therefore never changed.

> ⚠️ Fixed assets and reverse charge (VAT due on purchases) produce no entries: the
> accounting firm still enters them.

---

## 1. VAT codes

**VAT** tab: one code per rate, and one code per exemption case.

![VAT codes](img/01-tva-liste.png)

The **Default** code is suggested on quote, order and purchase lines. Set it in the code's
**Edit** window.

For 0 % sales, create **one code per case**, each with its category:

![0 % VAT code](img/02-tva-modification.png)

1. **EN 16931 category**: K (intra-EU supply), G (export outside the EU), AE (reverse
   charge), E (exempt); S and Z for standard and zero rates.
2. **Exemption reason — code (BT-121)**, for example VATEX-EU-IC.
3. **Exemption reason — text (BT-120)**, the legal wording.
4. **Legal mention (invoice)**.

The reason and category are sent in the electronic invoice (Factur-X).

> ⚠️ Two codes with the same rate need different categories: two "0 %" codes without a
> category are rejected.
>
> ⚠️ The **legal mention** is not printed on the invoice PDF yet: meanwhile, add it to the
> wording of your terms and conditions.

---

## 2. Accounting allocations

**Accounting allocation** tab: this is where each line finds its accounts. An allocation
links a **type** and a **VAT code** to a revenue (or expense) account and a VAT account.

![Accounting allocations](img/03-affectations.png)

| Type | Used for |
|---|---|
| **Sale** | every customer invoice line |
| **Purchases** | supplier invoice lines linked to a customer order |
| **Purchase (stock)** | supplier invoice lines without a customer order (stock, internal order) |

The Deposit, Tax and Other types are not used.

- **Account code**: the revenue or expense account written to the FEC (706000, 601000…).
- **VAT account code**: the VAT account (445710, 445660…).
- **Account number**: just an identifier of the allocation, not used in entries.

> ⚠️ You need **one allocation per type and per VAT code**, including each 0 % code and the
> "Purchase (stock)" type. A line without a matching allocation is saved **without an
> entry, and without any message**: it will be missing from the FEC.

---

## 3. Payments and delivery

**Payment method**: the **Account code** is the cash account debited by the receipt
(512000 for the bank, 530000 for cash). Left empty, 512000 is used.

![Payment methods](img/04-methodes-paiement.png)

**Payment terms**: shown on quotes, orders and invoices; the **default** term is suggested
for new customers.

![Payment terms](img/05-conditions-paiement.png)

> ⚠️ The number of months and days and the "End of month" box do not compute the due date
> yet: an invoice is always created due in **30 days**. Adjust the due date on the invoice
> if needed.

**Delivery method**: a label used on customers and quotes, with no accounting effect.

---

## 4. VAT regimes, natures and matrices (advanced)

When the same item is sold to customers under different regimes (France, EU, export,
franchise), the matrices pick the accounts from the pair **customer regime × item
nature**.

![Regimes and natures](img/06-regimes-natures.png)

- The **regime** is chosen on the customer record, **Detail** tab, **VAT regime** card
  (with the certificate for a franchise):

  ![Customer VAT regime](img/09-societe-regime.png)

- The **nature** is chosen on the item record, **Product detail** tab (manufacturing,
  material, service, shipping…).

A rule of the **Sales VAT matrix** gives the sales account and output VAT account for a
regime × nature pair:

![Sales VAT matrix](img/07-matrice-ventes.png)

The **Purchase VAT matrix** does the same for suppliers: purchase account, input VAT, and a
marker for reverse charge.

![Purchase VAT matrix](img/08-matrice-achats.png)

> 💡 Without a regime on the customer, a nature on the item, or a rule for the pair, the
> line falls back on the accounting allocation (section 2): an incomplete matrix blocks
> nothing.
>
> ⚠️ The matrix only provides **accounts**: the VAT rate remains the one of the VAT code
> chosen on the line. For sales, it currently only applies to invoice lines that carry an
> item.

---

## 5. Lock periods

**More › Periods** tab: after each handover to the accountant, lock the month.

![Locked periods](img/10-periodes.png)

Choose the year and month, then **Lock**. A row's padlock unlocks the period.

| Once the month is locked… | |
|---|---|
| blocked | adding or deleting an invoice line, changing an invoice header, changing a supplier invoice |
| **not** blocked | changing the quantity or price of a draft line, recording a payment dated in the month, creating a credit note |

> 💡 The lock protects invoices, not the journal: do not date a payment in a month already
> sent.

---

## 6. Hand over to the accountant

Menu **Your company › Imports/Exports**, **FEC export** tab (see also the guide "Invoice
and get paid"):

![FEC export](img/11-fec.png)

1. **ENCAIS**: tick it, it is unticked by default.
2. **Start date** and 3. **End date**: the month to send, then "Submit" to list the entries.

Then tick the entries, and **CSV**, **XLS** or **PDF**.

> ⚠️ The export is **final**: exported entries leave the list and cannot be exported again.
> Keep the file, then lock the period.
>
> ⚠️ Entries of **draft** invoices are listed too: issue or delete drafts before exporting.
>
> 💡 Have your accountant validate the file format on the first handover.

## Frequently asked questions

**An invoice is missing from the FEC export.**
There is no Sale allocation for the VAT code of its lines (section 2). Create it: lines
invoiced afterwards will get their entry.

**A supplier invoice is missing.**
Its lines are not linked to a customer order: a "Purchase (stock)" allocation is needed for
their VAT code.

**Receipts go to account 512000.**
The payment method has no account code: fill it in.

**I see "Annulation …" entries.**
An exported document was changed or deleted: the ERP posted a reversing entry instead of
changing the entry already sent.

**I cannot see the Matrices and Periods tabs.**
You are missing the accounting-manage permission.
