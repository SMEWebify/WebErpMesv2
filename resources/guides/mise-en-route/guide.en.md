---
title: Setting up your instance
summary: The settings to make at the start — company, numbering, accounting, task workflow, revenue target, roles, users, email and imports — in the right order.
category: Setup
order: 5
duration: 30
icon: fas fa-rocket
can: your-company-menu
updated: 2026-10-25
---

**Goal**: prepare a new instance before the first quotes, in the right order, so that
your documents are correct from day one.

> 💡 Screenshots show the French interface; button names are given as they appear in the English interface.

**For whom**: the company administrator (permission **Your company**).

## In what order

```steps
Setup wizard
Factory settings
Accounting
Methods
Task workflow
Revenue target
Roles and users
Email
Imports
```

## 1. The setup wizard

When you first log in, a wizard asks for the information the application cannot work
without: the **company** (name, address, SIREN (French company number), VAT number,
logo), a **VAT** rate, a **payment condition**, a **payment method**, a **delivery
method** and a **unit**. Each value you enter becomes the **default** value.

> ⚠️ At the end of the wizard, check that your account has a **role** (step 6) and
> that a **revenue target** exists for the current year (step 5). Without a target, the
> dashboard sends you back to the entry screen.
>
> 💡 Everything the wizard asks for can be changed later in the screens below.

---

## 2. Factory settings

Menu **Your company › Factory settings**, "Factory settings" tab. This information
appears on all your documents (quotes, orders, invoices).

**Identity**: name, address, postal code, city, country, phone, email.

![Identity](img/01-societe-identite.png)

**Administrative information**: **SIREN** (9 digits), **EU VAT number**,
IBAN and BIC. The SIREN and the VAT number are required for electronic invoicing.

![Administrative information](img/02-societe-administratif.png)

**Default values**: quote validity period, delivery lead time, the company's **VAT**,
**currency** and **first month of the financial year**.

![Default values](img/03-societe-defauts.png)

The form continues with the PDF theme, your **terms and conditions** (a link or a PDF file
added after quotes and orders), the logo and the optional modules. Finish with "Submit".

> ⚠️ After the wizard, check the **country** and the **currency**: if they have never been
> saved, the list shows its first value, not yours.

### Document numbering

"Document code templates" tab: the number format for each document type
(quote, order, delivery note, invoice, purchases…). Example: `FAC{yy}{mm}{id(4)}` gives FAC26100001.

![Numbering](img/04-numerotation.png)

- Date tokens: `{d}` `{dd}` `{m}` `{mm}` `{yy}` `{yyyy}` `{w}` `{ww}`;
  number: `{id}`, or `{id(4)}` for 4 digits.
- **Reset**: start again from 1 every day, week, month or year, or never.

> ⚠️ Set up the numbering **before** the first invoice: once an invoice has a number,
> it never changes.

---

## 3. Accounting

Menu **Accounting**: VAT, payment conditions, payment methods, delivery methods
and accounting allocations. All these screens work the same way: the list on the left,
creation on the right.

![VAT](img/05-tva.png)

> ⚠️ Each list must have a **default** value: without a default VAT and unit, creating
> a quote, order or purchase line is refused. The "By default" setting is made in the
> **"Edit"** window, not at creation.

![Default setting](img/06-tva-defaut.png)

![Payment conditions](img/07-conditions-paiement.png)

The **payment method** also holds the cash account used for payment receipt entries
(account field).

![Payment methods](img/08-modes-paiement.png)

> 💡 The "Edit" window of a VAT rate also holds the **legal mention** printed on
> invoices (exemption, reverse charge…). The **accounting allocations** link each
> VAT rate to its sales and purchase accounts: without them, no entry is generated.

---

## 4. Methods

Units, services and hourly rates, families, sections, resources… Follow the guide
"Methods, the workshop dictionary".

---

## 5. Task workflow

Menu **Your company › Task Workflow**: the statuses that manufacturing operations go
through. Without any status, quotes, orders, purchases and production send you
to this screen.

![Task workflow](img/09-flux-taches.png)

Create the statuses in this order, choosing their title from the list:
**Open**, **Started**, **In progress**, **Finished**, **Suspended**, **To RFQ**,
**RFQ in progress**, **Outsourced**, **Supplied**.

> ⚠️ The titles are not translated and cannot be renamed: the software recognises a
> started operation by "In progress" and a finished one by "Finished". The first line is the
> status of new operations.

---

## 6. Revenue target

Menu **Your company › Estimated budget**: the revenue target,
month by month, shown on the dashboard.

![Revenue target](img/10-objectif-ca.png)

> ⚠️ A target is mandatory for the **current year**: remember to enter the one for
> the following year before 1 January.

---

## 7. Roles and users

### Roles and permissions

Menu **Your company › Roles & permissions**. A **role** groups **permissions**:
one permission per menu (quotes, orders, purchases, accounting…) plus a few specific
rights (reminders, credit limit, approval of quality plans…).

![Roles and permissions](img/11-roles.png)

- Keep the **Admin** role for the administrator: new permissions added by
  updates are given to it automatically.
- Create one role per job (sales, workshop, accountant…), then tick its permissions
  in "Edit".

> ⚠️ The "Delete" buttons act without confirmation.

### Users

Menu **Human resources › Users list**.

![User list](img/12-utilisateurs.png)

A new user registers themselves (registration page, if your installer has
enabled it) or is imported from the company directory (LDAP). They must then **confirm their
email address**: so the email configuration (step 8) must be done first.

To give them their rights: open their record, **HR information** tab, choose their
**Role**, then "Update".

![User role](img/13-utilisateur-role.png)

> ⚠️ For a user without a role, the list shows the first role: it will be given to them
> when you save. Check it before saving. You cannot change your own role.

---

## 8. Email

Menu **Your company › Email settings**: the sending server (SMTP) that sends
quotes, invoices, reminders and notifications.

![Email configuration](img/14-email.png)

Fill in the server, port, encryption, username and password,
the sender address and name, then "Save" and **"Send a test email"**.

> 💡 Email texts are set in **Template mail** (one template per document
> type). The **Email log** keeps a record of every email sent.

---

## 9. Bringing in your data

Menu **Your company › Imports/Exports**: import customers, products, quotes
and orders from a CSV file.

![Customer import](img/15-import-clients.png)

1. Choose the CSV file, and indicate whether it has a **header line**.
2. For each field, enter the **number of the column** that contains it.
3. Import.

> ⚠️ The file must be a **semicolon-separated CSV** (not an Excel file),
> and the columns are **numbered from 0**. Import customers before quotes
> and orders, and do a trial on a few lines.

---

## What next?

Each user can choose the look of the application: user menu (top
right) › **Profile**, **Appearance** tab (skin and main colour). Light
or dark mode is set with the button in the top bar.

![Appearance](img/16-apparence.png)

Your instance is ready: follow the guide **"From prospect to invoice"** for the first
quote.

## Frequently asked questions

**The application keeps sending me back to the setup wizard.**
A company, a VAT rate, a payment condition or method, a delivery method
or a unit is missing.

**"Aucune TVA ou unité par défaut configurée" (No default VAT or unit configured) when creating a line.**
No VAT rate or no unit is set "By default" ("Edit" window).

**The dashboard sends me to the revenue target.**
There is no target for the current year (step 6).

**A user cannot see a menu.**
Their role does not have the permission for that menu (step 7).
