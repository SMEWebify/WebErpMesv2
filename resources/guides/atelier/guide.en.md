---
title: Workshop and production
summary: For operators, record work on the tracking screen (start, good parts and scrap, end, alerts); for the workshop manager, steer the workshop with the tasks list, the Kanban, the load planning and the reports.
category: Production
order: 10
duration: 20
icon: fas fa-industry
updated: 2026-10-26
---

**Goal**: record the work done in the workshop in real time, and use it to manage the
load and measure production.

> 💡 Screenshots show the French interface; button names are given as they appear in the English interface.

**Who is it for**: **operators** (sections 1 to 4) and the **workshop manager** (sections 5 to 8).

## Before you start

- **Operations** come from the routing of the order lines (see the guide
  "From prospect to invoice"). A quote operation cannot be recorded.
- The **task workflow** (statuses Open, Started, In progress, Finished…) must be set up
  (see "Setting up your instance").
- **Resources** (machines, workstations) must be linked to their services (see the
  guide "Methods").

## Overview

```steps
Find the operation
Start
Record the parts
Pause / resume
Finish
Manage and measure
```

---

## 1. Workshop mode

The **workshop** screen is made for a shared workstation or a tablet: large tiles, no
side menu. Open your instance address followed by **/workshop** and bookmark it on the
workshop computer.

![Workshop home](img/01-accueil-atelier.png)

1. **Tasks list**: what is left to do.
2. **Production Declaration**: the tracking screen of an operation.

The two other tiles open the stock label scan and the workshop reports
(section 8).

> 💡 On a tablet, the grid button in the top bar opens the app
> launcher. **Attendance** (clock-in and clock-out) is for presence,
> not for production.

---

## 2. Find your work

The **workshop list** shows the order operations, with their service, their
resource, their progress and their due date.

![Workshop list](img/02-liste-atelier.png)

- Filter by **Service**, **Resource** or **Status** (finished operations are
  hidden by default).
- The **Fin** (End) column is coloured: red if the due date has passed, orange if it is
  today, blue if it is still to come.
- The **Voir** (View) button opens the tracking screen of the operation.

The operator can also type the **operation number**, the one on the job card, in
the search of "Production Declaration". The screen also shows the indicators for the
month: operations in progress, hours produced, OEE (TRS).

![Searching for an operation](img/03-recherche-tache.png)

---

## 3. Record on the tracking screen

![Tracking screen](img/04-suivi-avant.png)

The header shows the order, the line and the operation, with two bars: the **time**
clocked against the planned time, and the **parts** produced against the ordered quantity. The button
at the top right moves to the previous or next operation of the same routing.

**Start.** Click **Play** when work begins: the operation switches to "In progress"
and the timer runs.

**Record the parts** as you go:

- **Good quantity**: +1, +10 or +100, or type a number then "Set";
- **Rejected quantity**: −1, −10 or −100 for scrap.

![Operation in progress](img/05-suivi-en-cours.png)

1. **Pause** stops the timer (break, tool change, waiting for material).
2. **+10** records 10 good parts.
3. **−1** records one scrapped part.

The counters update: good parts, bad parts, **Net production**
(good parts minus scrap).

> ⚠️ The quick buttons (+1, +10, −1…) record **immediately**, without confirmation.
> A mistake is corrected by recording the opposite.

**Finish.** Click **End** when the operation is finished. The activity log keeps
every action, with who did it and when.

![Finished operation](img/08-suivi-termine.png)

> 💡 When all the operations of a line are finished, the "Tâches" (Tasks) column of the
> order line changes to **Terminé** (Finished).

### The cards on the right

**Scheduling**: the planned end date, the **Ne pas recalculer** (Do not recalculate) box (the automatic
planning calculation will no longer change it) and the assigned **resource** (machine).

![Scheduling card](img/06-suivi-planification.png)

**Information**: status, quantity, cost, price, setup time, unit time, total
time, **OEE (TRS)** and progress.

![Information tab](img/07-suivi-informations.png)

**In the middle**, the drawing or the 3D model of the part is shown when a file is attached to
the order line or to the item. If the item has an approved **control plan**, an
**Inspection** tab lets you enter the measurements.

> 💡 **Subcontracting and material**: for an operation handed to a supplier, the screen
> offers "New purchase document" instead of Play, then "End" on receipt.

---

## 4. Report a problem

**Quality** card of the tracking screen:

- **New non conformity** immediately creates a non-conformity linked to the operation,
  to be completed in the Quality module;
- **Alerte Andon** (Andon alert) reports a stop or a problem (breakdown, missing material, waiting for
  inspection): fill in the alert type, then "Ajouter l'alerte" (Add the alert).

![Andon alert](img/09-alerte-andon.png)

The workshop manager handles the alerts on the Andon screen (section 8).

---

## 5. The tasks list

Menu **Scheduling › Tasks list**: all the order operations, with
filters by service, resource and status. Clicking a line opens the tracking screen.

![Tasks list](img/10-liste-taches.png)

---

## 6. The Kanban

Menu **Scheduling › Workflow**: one column per status, one card per
operation. Filter on a **service** ("Select Service", then "Filter"): without a filter,
the page shows all operations.

![Kanban](img/11-kanban.png)

> ⚠️ Dragging a card changes the status of the operation: to "In progress", it is
> started; to "Finished", it is finished.

---

## 7. The load planning

Menu **Scheduling › Load planning**: the load of each **service** (or of each
**resource**) day by day, compared with its capacity. The colours go from "Free" to
"Overloaded".

![Load planning](img/12-planning-charge.png)

- **Vue** (View): by service or by resource. The resource view has an **Unassigned
  tasks** line for operations without a machine.
- **Sens** (Direction): as late as possible (ALAP) or as soon as possible (ASAP); **Capacité** (Capacity): infinite or finite.
- The orange badges point out operations whose **resource** or **dates**
  are still to be calculated.
- The load of an operation is placed on its **end date**: a period with no operation
  due stays empty.

> ⚠️ **Simuler** (Simulate) recalculates and **saves** the dates of all operations not
> finished, except those with "Ne pas recalculer" (Do not recalculate) ticked. It is not a preview.
>
> 💡 To recalculate a single order: order page, "Task Calculation Date".

---

## 8. Measure production

**Reports** tile on the workshop home: what was done over the period (today, 7 or
30 days), refreshed every minute.

![Workshop reports](img/13-rapports.png)

- Hours clocked, **good parts**, **scrap** and scrap rate, finished operations;
- active operators, operations **in progress now**, **late** operations;
- breakdown by resource (the actual machine load), by operator and by service;
- Andon alerts for the period.

The **Andon** screen (address **/workshop/andon**) lists the alerts: set them to "In Progress"
when someone is dealing with them, then "Solved".

![Andon screen](img/14-andon.png)

> 💡 Leave the Andon screen or the reports displayed on a large screen in the workshop.

## Frequently asked questions

**The Play button is greyed out.**
The operation is already started or finished.

**"This is a quote task, no action possible."**
The operation belongs to a quote: only order operations can be recorded.

**My machine does not appear in the list of resources.**
It is not linked to the service of the operation (Methods › Ressources).

**The load planning is empty.**
No operation is due in the period shown: change the period with the
arrows.

**The order line stays "En cours" (In progress).**
One of its operations is not finished: open the routing of the line to find it.
