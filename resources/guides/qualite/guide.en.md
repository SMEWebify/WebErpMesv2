---
title: Quality — non-conformities and inspection
summary: The Quality module day to day — defect reference lists, measuring devices and calibration, non-conformities, corrective actions, derogations, control plans and first article report, internal audits and customer returns.
category: Quality
order: 10
duration: 25
icon: fas fa-ruler-combined
can: quality-menu
updated: 2026-10-26
---

**Goal**: handle a defect from start to finish (report, analysis, corrective action,
possible derogation, closing) and keep track of the workshop's inspection equipment.

> 💡 Screenshots show the French interface; button names are given as they appear in the English interface.

**Who is it for**: the quality manager. Operators also report non-conformities from the
workshop.

**Example used**: a batch of 12 brackets comes out of the press brake at 92° instead of
90°. The back gauge moved during a tool change.

## Before you start

- The **Quality** menu appears with the **quality-menu** permission. Internal audits
  require **audit-menu**, customer returns **returns-menu**.
- To balloon and approve control plans: **inspection-plans-edit** and
  **inspection-plans-approve**.
- **Services** (bending, laser…) come from Methods (see the guide "Methods").

## Overview

```steps
Settings
Measuring devices
Non-conformity
Corrective action
Derogation
Closing
```

The **Quality** menu gathers: **Dashboard** (with measuring devices and settings),
**Actions**, **Derogations**, **Non-conformities**, **Inspection** (control plans),
**Process mapping**, **FMEA**, **Internal audits** and **Customer returns**.

---

## 1. The dashboard

![Quality dashboard](img/01-tableau-de-bord.png)

- **Open Actions / Non-conformities / Derogations**: records with the "In progress" status.
- **Dispute rate**: the share of order lines that have a non-conformity.
- **Resolution time**: the average time between creating and closing a non-conformity.
- Below: internal / external split, customers generating the most non-conformities, and
  the status distribution.

> 💡 Resolution time only counts non-conformities closed with the **Resolved** button
> (section 7): that button records the closing date.

---

## 2. Set up the defect reference lists

**Settings** tab: three lists, filled in once, reused by every non-conformity.

- **Failure type**: what is found (out-of-tolerance dimension, scratch, rust…);
- **Cause type**: why (setting, material, program, handling…);
- **Correction type**: what is done (rework, scrap, 100 % sorting…).

Expand a card with **+**, then:

![Settings](img/02-reglage.png)

1. **External ID**: a short, unique code.
2. **Label**.
3. **Submit**.

> 💡 Keep these lists short and stable: they classify non-conformities and reveal those
> that recur.

---

## 3. Track measuring devices

**Measuring device** tab: calipers, protractors, micrometers, gauges…

![New measuring device](img/03-appareil-creation.png)

1. **External ID**: the device's tag, unique.
2. **Serial number**, also unique.
3. **Calibration due date**: the date of the next calibration.
4. **Submit**, after choosing the responsible **Service** and **User** (both required).

![Devices and alerts](img/04-appareils-liste.png)

- **Red** banner: calibration overdue;
- **Yellow** banner: calibration due within 7 days.

Every morning at 7:30, the person responsible for each device concerned also gets a notification
(scheduled task: the scheduler must run on the server).

> 💡 A device past its calibration date can still be used to enter a measurement, but it is
> flagged "Calibration overdue" in the device list of the inspection screen.

---

## 4. Report a non-conformity

Menu **Non-conformities**, **New non-conformity** card at the bottom of the page (click its
title to expand it).

![New non-conformity](img/05-nc-creation.png)

1. **Label**: the defect, in one sentence. The number (external ID) is suggested.
2. **User**: the person in charge (required).
3. **Failure type**, with the detailed finding below.
4. **Cause type**, with the analysis.
5. **Correction type**, with what was done.

Fill in the **Type** (Internal if the defect comes from you, External if it comes from a
customer or supplier), the **Service**, the **Customer**, the quantity **Qty** and the
**drawing revision**, then **Submit**.

![Non-conformities list](img/06-nc-liste.png)

The list filters by status, type and search. Clicking a row shows the details; the PDF
button prints the record.

**Other ways to create a non-conformity:**

- from the **operation tracking screen** in the workshop, "New non-conformity" button: it
  is linked to the order and the operation;
- from the **public link of a delivery note**, by the customer;
- from an **out-of-tolerance measurement** entered during an inspection (section 8).

> ⚠️ In the workshop, the button creates the non-conformity **immediately**, without
> confirmation: a double click creates two.

---

## 5. Start a corrective action

Menu **Actions**: an action fixes the cause so the defect does not come back.

![New action](img/07-action-creation.png)

1. **Label**: replace the number copied by default with the action's title.
2. **Non-conformity**: the record the action answers.
3. **Cause description**.
4. **Action description**.

The **Type** switch turned on means **Internal**. Choose the **User** in charge, then
**Submit**.

![Quality actions](img/08-actions-liste.png)

When the action is done and checked, open it with **Edit** and set its status to
**Approved**.

> ⚠️ When editing, the three descriptions (problem, cause, action) are required, although
> they were not at creation. Fill them in from the start.
>
> 💡 Actions are sorted from oldest to newest: the latest one is at the end of the list.

---

## 6. Request a derogation

Menu **Derogations**: when the customer may accept out-of-tolerance parts, the derogation
records the request and its answer.

![New derogation](img/09-derogation-creation.png)

1. **Proposal description**: what you suggest to the customer.
2. **Customer reply description**.
3. **Decision description**.

All four text areas are **required**: write "pending" until the customer answers, then
complete the derogation with **Edit**. Link it to the non-conformity like an action.

---

## 7. Close the non-conformity

Once handled, open the non-conformity with its pencil.

![Closing](img/10-nc-cloture.png)

1. **Resolved** sets the record to "Resolved" and records the closing date.

![Resolved non-conformity](img/11-nc-resolue.png)

> ⚠️ The **Resolved** button closes the window **without saving** other changed fields. If
> you changed something, click **Update** first, reopen the record, then click
> **Resolved**.
>
> 💡 Choosing "Resolved" in the status list does not record a closing date: the record
> would be ignored by the resolution time. Use the button. **Reopen** sets the record back
> to "In progress".

---

## 8. Control plans and first article

Menu **Inspection**: the **Quality plans** screen gathers the items' control plans.

![Quality plans](img/12-plans-valides.png)

- **To balloon**: items ordered by a customer that requires a control plan (box on the
  customer record) or set to "Control plan: Always", and that do not have one yet;
- **Drafts** and **Approved**: plans being ballooned, then approved;
- **In progress** and **Closed**: inspections carried out on these plans.

The **ballooning tool** shows the item's drawing (the PDF in its documents): draw a
rectangle around each dimension to check, with its nominal, tolerances and sampling.

![Ballooned drawing](img/13-bulleur.png)

An **approved** plan can no longer be changed: "New version" creates a draft that copies
its dimensions.

During production, the **Inspection** tab of the operation tracking screen shows the
ballooned drawing and a measurement grid. An out-of-tolerance measurement offers to
**report a non-conformity**, pre-filled. The **Inspect** button of an approved plan starts
an inspection outside production (goods receipt, audit).

The **first article report** gathers the part identification, material and dimension
results. It is signed "verified" then "approved", and its PDF is filed in the order's
documents.

![First article report](img/14-rapport-fai.png)

> 💡 Ballooning will get its own guide.

---

## 9. Internal audits and customer returns

**Internal audits**: ISO 9001 planner with annual plans, calendar, processes and checklists
(clauses 4 to 10, pre-filled). An audit is started from the calendar; its findings (major or
minor non-conformity, observation, strength) feed the dashboard.

![Internal audits](img/15-audits.png)

**Customer returns** (also in the Delivery notes menu): a return is linked to a delivery
and, if needed, to a non-conformity. Enter the diagnosis, then generate the rework tasks.

![Customer returns](img/16-retours.png)

## Frequently asked questions

**The Failure, Cause and Correction lists only offer "N/A".**
The reference lists are empty: fill in the Settings tab of the dashboard (section 2).

**My action does not save when edited.**
The three descriptions (problem, cause, action) are required when editing.

**Resolution time stays at 0 days.**
No non-conformity has been closed with the "Resolved" button.

**How do I see which actions answer a non-conformity?**
The "NC" column of the actions and derogations lists shows the linked record; the
non-conformity itself does not list them.
