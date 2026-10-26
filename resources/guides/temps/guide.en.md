---
title: Time, shifts and leave
summary: Workshop shift patterns, public holidays, leave types and entitlements, employee leave requests and HR approval, balances, attendance report and payroll export.
category: Setup
order: 40
duration: 20
icon: fas fa-user-clock
can: settings-time-menu
updated: 2026-10-26
---

**Goal**: describe when the workshop works (shifts, public holidays), and manage leave from
the request to the payroll export.

> 💡 Screenshots show the French interface; button names are given as they appear in the English interface.

**Who is it for**: the workshop manager (shifts and calendar), HR (leave, attendance,
payroll) and every employee (their leave request).

**Example used**: the workshop moves to two shifts, Monday to Friday. An employee takes the
week of 11 November: 5 working days, minus the public holiday, so 4 days are charged.

## Before you start

- The **Settings time** menu (Times and absences, Work shifts) appears with the
  **settings-time-menu** permission.
- Leave, balances, attendance and payroll export are under **Human resources** and require
  **human-resources-menu**. Only holders of this permission enter leave for another
  employee and approve requests.
- Each employee files requests from their **profile**.

## Overview

```steps
Leave types
Work shifts
Public holidays
Entitlements
Request and approval
Balances and payroll
```

---

## 1. Leave types

Menu **Human resources › Expenses**, **Leave types** tab: paid leave, RTT, time off
in lieu, sick leave, family event, training, unpaid…

![New leave type](img/01-nature-conge.png)

1. **Code** (the French screen labels it "Référence projet"). It is also the **pay item
   code** of the payroll export (CP, RTT…).
2. **Default annual quota**: the yearly entitlement suggested (25 days of paid leave).
3. **Counts against balance**: ticked, the type uses up an entitlement (paid leave, RTT);
   unticked, it is only recorded (sick leave).
4. **Submit**.

![Leave types](img/02-natures-liste.png)

> 💡 An absence **without a type** appears neither in balances nor in the payroll export:
> create the types before the first requests.

---

## 2. Work shifts

Menu **Settings time › Work shifts**: a **shift pattern** describes the worked slots of
each day (day shift, two shifts, three shifts, night shift).

![New shift pattern](img/03-regime-creation.png)

1. **External ID**: a short code (2X8, 3X8…).
2. **Label**.
3. **Use as the workshop's default pattern**: leave it unticked for now.
4. **Submit**.

Then add the slots below the pattern's card:

![Add a slot](img/04-regime-plage.png)

1. **Start** and 2. **End** of the slot. An end earlier than the start crosses midnight
   (night shift).
3. **Apply Monday to Friday**: ticked, the slot is created on all five days, whatever day
   is selected. Untick it for a Saturday slot.
4. **+** adds the slot.

![Two-shift pattern](img/05-regime.png)

The total hours per day and per week are shown; a slot's cross deletes it.

For a machine to follow this pattern, choose it on its record (**Methods › Resources**,
"Shift pattern" field). Its capacity in the **load planning** then becomes the pattern's:
16 h a day for two shifts, 0 on Sunday.

> ⚠️ Tick "default pattern" **after** adding the slots, never on an empty pattern. The
> default pattern applies to every resource without its own and drives the operation date
> calculation: changing it changes the whole planning.

---

## 3. Public holidays

Menu **Settings time › Times and absences**, **public holiday** tab.

![New public holiday](img/06-ferie-creation.png)

1. **Label**.
2. **Fixed**: ticked, the day comes back every year on the same date (1 May, 11 November);
   unticked, it only applies to that date (Easter Monday, bridge day).
3. **Date**.
4. **Submit**.

![Public holidays](img/07-feries.png)

A public holiday is greyed out in the load planning (zero capacity), skipped by the
operation date calculation, and removed from leave counted in working days.

> ⚠️ Enter the year's calendar **before** leave requests: a holiday added later does not
> recompute requests already saved. Your administrator can then run
> `php artisan hr:recompute-absence-days`.
>
> 💡 For a movable holiday (Easter Monday, Ascension), prefer one date per year: the load
> planning currently treats every holiday as recurring each year.

---

## 4. Leave entitlements

Employee record (**Human resources › Users list**), **Leave balances** tab, **Edit** on the
leave type:

![Employee entitlements](img/08-droits.png)

1. **Entitlement**: the period's entitlement (25 days of paid leave).
2. **Carried over**: what remains from the previous period.
3. **Adjustment**: a correction, positive or negative.

The reference period runs from **1 June to 31 May**. The **Generate missing entitlements**
button of the balances screen creates at once the default entitlement of every employee for
every counted type.

---

## 5. The leave request (employee)

The employee opens their **profile** (menu with their name, top right), **Leave request**
tab. It shows their balances, their requests and the form:

![Leave request](img/09-absence-demande.png)

1. **Leave type**.
2. **Day type**, which sets what is counted:
   - **Worked day** (Monday to Friday, excluding holidays);
   - **Working day** (Monday to Saturday, excluding holidays);
   - **Calendar**: every day, weekends and holidays included.
3. **Start date** and 4. **End date**.

The **Absence type** distinguishes a full day, a morning half-day and an absence in hours
(with the number of hours).

![Pending request](img/10-absence-en-attente.png)

The request is **to be approved**: the balance already counts it as "Pending" (4 days
here), so the employee cannot book the same days twice. They can change it until it is
handled.

> ⚠️ The "Afternoon absence" type counts a full day: for a half-day, use "Morning absence"
> or an absence in hours.
>
> 💡 No notification is sent to the approver: HR check the absences screen.

---

## 6. Approval (HR)

Menu **Settings time › Times and absences**, **Absence** tab: all requests, with the
number of days charged. **Edit** on a request:

![Approve the request](img/11-absence-validation.png)

1. **Status**: "Approved" or "Not approved" (refusal).
2. **Update**.

![Leave requests](img/12-absences.png)

HR can also enter an absence for an employee from the form on the right (choose the
**User**: the first one in the list is selected by default).

---

## 7. Balances

Menu **Human resources › Leave balances**: the workshop table, one employee per row and,
for each type, total entitlement, days taken and remaining balance.

![Leave balances](img/13-soldes.png)

> 💡 The **remaining balance** also deducts pending requests, which have no column here:
> "Total − Taken" can therefore be higher than the balance. The details (including the
> "Pending" column) are on the employee record.

---

## 8. Attendance and payroll export

**Attendance report** (Human resources): the time spent per employee over a period, from
production declarations (Activities tab) or the time clock (Clocking tab). **Anomalies**
count clock-ins never closed, double entries and orphan exits.

![Attendance report](img/14-presence.png)

> 💡 The time clock is the **/pointage** page of your instance, to open on a computer at the
> workshop entrance: the employee picks their name, then In or Out.

**Payroll export** (Human resources): the month's variables for your payroll software.

![Payroll export](img/15-export-paie.png)

- One row per employee and per pay item: one per leave type (**approved** absences, in
  days), clocked hours (HTRAV) and hours on operations (HPROD).
- The **Check before sending** box lists open clock-ins and employees without a **payroll
  number** (to fill in on the employee record).
- **CSV** or **Excel** downloads the file, without changing anything.

> 💡 The file uses a neutral format (payroll number, period, pay item code, quantity, unit):
> map the leave type codes to the pay items of your payroll software.

---

## 9. Other settings

- **Machine event** (Times and absences): the stop types (maintenance, breakdown, setup)
  used by the CMMS. A work order with "Machine stopped" removes its period from the
  machine's capacity (see the guide "Maintenance (CMMS)").

  ![Machine events](img/16-evenements-machine.png)

- **Non-productive time** (Times and absences): a descriptive list of non-productive times.
- **Time unit** (Your company): show routing times in hours or minutes. Storage, exports and
  capacities stay in hours.

  ![Time unit](img/17-unite-temps.png)

## Frequently asked questions

**A request counts 5 days instead of 4.**
It uses "Calendar", or the public holiday did not exist yet when the request was made.

**The leave type list only offers "--".**
No leave type is created or active: section 1.

**An approved absence is missing from the payroll export.**
It has no leave type, or it falls outside the selected month.

**The load planning did not change after creating the pattern.**
The pattern is assigned to no resource and is not the default pattern.
