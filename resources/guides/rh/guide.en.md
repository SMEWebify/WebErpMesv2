---
title: Human resources — employee record and expense reports
summary: Keep each employee's record (HR information, payroll number, contracts, confidential file) and handle expense reports, from the receipt photographed by the employee to HR approval.
category: Human resources
order: 10
duration: 15
icon: fas fa-users
can: human-resources-menu
updated: 2026-10-26
---

**Goal**: gather on a single record everything the company knows about an employee, and
handle expense reports without paper.

> 💡 Screenshots show the French interface; button names are given as they appear in the English interface.

**Who is it for**: the person in charge of HR, and every employee for their expense
reports.

**Example used**: a press brake operator hired on a permanent contract on 1 September. They
come back from a trade show in Lyon with a restaurant receipt to be refunded.

## Before you start

- The **Human resources** menu appears with the **human-resources-menu** permission, which
  is also needed to change an employee record and its contracts, and to approve expense
  reports.
- Each employee enters their expense reports from their **profile**, with no particular
  permission.
- Leave, the attendance report and the payroll export are covered by the guide "Time,
  shifts and leave"; the authorisation matrix by "Occupational health and safety".

## Overview

```steps
Employee record
Contract
File
Expense report
Receipt
Approval
```

The **Human resources** menu gathers: **Expenses** (approval, expense categories, leave
types), **Users list**, **Attendance report**, **Leave balances** and **Payroll export**.

---

## 1. The employee list

Menu **Human resources › Users list**: one employee per row, with their connection status,
job, roles and, if blocked, the end date of the block. The arrow at the end of the row
opens their record.

![Employee list](img/01-salaries.png)

> 💡 An employee is an ERP user account: they sign up themselves, then the administrator
> gives them a role (see the guide "Setting up your instance"); the HR record is then
> completed here.

---

## 2. The employee record

**HR information** tab:

![HR information](img/02-fiche-rh.png)

1. **Job title**: the position held.
2. **Payroll number**: the employee's number in your payroll software. It is used by the
   payroll export; without it, the export falls back on the internal ID and flags it.
3. **Role**: the role that opens the ERP menus. "—" leaves it unchanged.

Below: pay grade, workstation, joining, confirmation and termination dates, employment
status, section and the employee's **supervisor**. **Update** saves.

> 💡 The role can only be changed with the company administration permission, and never on
> one's own account.
>
> ⚠️ The **Blocked until** card (left column) prevents the employee from logging in until
> the chosen date: keep it for a departure or a compromised account.

---

## 3. Contracts

**Contract** tab, **New contract** card:

![New contract](img/03-contrat.png)

1. **Type of contract**: permanent, fixed-term, temp, apprenticeship…
2. **Start date** (and **End date** for a fixed-term contract).
3. **Weekly duration** in hours.

Fill in the **status** (Trial during the trial period, then Active), the **section**, the
**signature date**, the **trial period** in days, the **position** and, if needed, the
coefficient and salaries. **Submit**.

![Employee contracts](img/04-contrats.png)

The **Active** contract counts: its weekly duration converts absences entered in hours into
days.

---

## 4. The employee file

**Documents** tab: signed contract, payslips, sick notes, diplomas, ID documents.

![Employee file](img/05-documents.png)

Drop a file in the upload area, then choose its **document type** (contract, payslip, sick
note, diploma, ID…).

> ⚠️ This file is **confidential**: only the employee concerned and HR can read it, add a
> file or delete one. The rest of the company has no access, unlike item or order
> documents.

---

## 5. The expense report (employee)

The employee opens their **profile** (menu with their name, top right), **Expense report**
tab, and creates one report per trip or per month:

![New expense report](img/06-note-creation.png)

1. **Label**: the trip or period.
2. **Date**.
3. **Submit**.

The report's **View** button opens its page: expenses on the left, **receipts** on the
right.

---

## 6. The receipt and the expense

First drop the photo of the receipt (or the PDF invoice) in the **Receipts** card:

![Receipt uploaded](img/07-justificatif.png)

1. **Receipts**: drag the file or click to choose it. On a phone, the browser offers to take
   the photo.

Then, in the entry card:

![Enter the expense](img/08-depense.png)

1. **Receipt**: the uploaded receipt. The list only offers receipts not yet entered.
2. **Read with AI**: reads the receipt and pre-fills date, place, description, amount and
   VAT. Nothing is saved: check before submitting.
3. **Category**: fuel, hotel, meals…

![Amount and VAT](img/08b-depense-montant.png)

1. **Amount**: the amount paid, including VAT.
2. **Tax**: the VAT **rate** (10 for 10 %), not its amount.
3. **Submit** adds the expense to the report.

The expense can also be linked to a customer **order** (rechargeable costs); the **payer**
is whoever advanced the money.

![Expense report](img/09-note.png)

> ⚠️ **Read with AI** sends the receipt image to the AI provider set up by your
> administrator. Without a provider, enter the expense by hand.

---

## 7. Submit the report

Once all expenses are entered, the employee goes back to their profile, **Expense report**
tab, and opens the report with **Edit**:

![Submit the report](img/10-note-soumission.png)

1. **Status**: "Submitted".
2. **Update**.

A submitted report can no longer be changed: its expenses and receipts are frozen until HR
decides.

---

## 8. Approval (HR)

Menu **Human resources › Expenses**, **Expense validation** tab: submitted reports with
their amount. **View** opens the report and its receipts.

![Reports to approve](img/11-validation-liste.png)

**Edit** on the report:

![Approve the report](img/12-validation.png)

1. **Status**: **Approved**, or **Returned** to send it back to the employee (missing
   receipt, amount to correct).
2. **Update**.

A returned report can be changed again by the employee, who corrects and resubmits it.

| Status | Who | Editable by the employee |
|---|---|---|
| Done / To submit | the employee prepares the report | yes |
| Submitted | waiting for HR | no |
| Returned | sent back to the employee | yes |
| Approved | approved by HR | no |

> 💡 The refund itself happens outside the ERP (transfer or payroll): approval only means
> the report is good to pay.

---

## 9. Expense categories

**Expense category** tab: the list offered to employees (fuel, hotel, lunch, dinner…).
**New expense category** adds one.

![Expense categories](img/13-categories.png)

## Frequently asked questions

**The employee cannot see their receipt in the "Receipt" list.**
It is already linked to another expense, or it was uploaded to another report.

**The report does not appear in the approval tab.**
It is not "Submitted": the employee must submit it from their profile.

**Can a colleague see my receipts?**
No: like the employee file, receipts are only visible to the report's employee and to HR.

**I cannot change an employee's role.**
It takes the company administration permission, and nobody changes their own role.
