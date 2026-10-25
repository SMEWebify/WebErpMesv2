---
title: Methods, the workshop dictionary
summary: What each category of the Methods menu is for (units, services, families, sections, resources, tools…), where it is reused and in which order to set it up.
category: Setup
order: 10
duration: 15
icon: fas fa-cogs
can: methods-menu
updated: 2026-10-25
---

**Goal**: understand the role of each category of the **Methods** menu and set them up
in the right order.

> 💡 Screenshots show the French interface; button names are given as they appear in the English interface.

Methods describe **how your workshop works**: what you can do (services), with what
(resources, tools), how you count (units) and how you classify your items (families). You
only fill them in once, but **everything else depends on them**: quote costing, routings,
the load planning, the operator screen and subcontracting purchases.

## Before you start

- You need the **Methods** permission, usually reserved for the methods manager or the
  administrator.
- Prepare the list of your workstations (laser, press brake, welding…), their **hourly
  rate** and their **capacity** in hours per week.
- The **users** must already exist: each section has a person in charge.

## In which order to set up

Each category relies on the previous one: follow this order.

```steps
Units
Services
Families
Sections
Resources
Locations
Tools
Inter-operation delays
Standard bills of materials
```

| Category | What it is for | Where it is reused | You first need |
|---|---|---|---|
| Units | Counting: piece, kg, metre… | Quote, order and purchase lines, items | — |
| Services | What the workshop can do, and at what price | Routings, costing, planning, operator screen, purchases | — |
| Families | Classifying items | Item sheet, nesting, inventory | a service |
| Sections | Grouping workstations (workshop, cell) | Resources, planning, HR, safety | a user |
| Resources | Machines and manual workstations | Load planning, task assignment, operator screen | a section and a service |
| Locations | The workshop areas | Methods overview | a resource |
| Tools | Bookable tooling | Routing operations, tooling stock | — |
| Inter-operation delays | The waiting time between two operations | Planning date calculation | productive services |
| Standard bills of materials | Reusable standard routings | "Modèles standards" (Standard templates) card of routings | services |

![The Methods menu](img/01-menu.png)

> 💡 All screens are laid out the same way: the list on the left, with an "Edit" button
> on each row, and the creation card on the right, confirmed with "Submit".
>
> ⚠️ The **External ID** (the code) can no longer be changed after creation, and a category
> cannot be deleted (except inter-operation delays). Choose short, stable codes:
> LASER, BEND, WELD…

---

## 1. Units: how you count

The unit tells how a quantity is counted: piece, kilogram, metre, square metre…

![Units](img/02-unites.png)

1. **External ID**: short code (U, KG, M).
2. **Label**: displayed name.
3. **Type**: mass, length, area, volume or other. This is only classification information.

> ⚠️ **One unit must be "By default".** It is the one offered on every new quote, order or
> purchase line: without it, line creation is refused.
> The "By default" setting is in the "Edit" window of a unit. Choosing one automatically
> removes this setting from the previous one.

---

## 2. Services: what the workshop can do

This is the most important category. A service is a **skill** (laser cutting, bending,
welding, subcontracted painting…) or a **purchase type** (sheet metal, screws and bolts…).

![Services](img/03-services.png)

1. **Type**: it decides where the service appears (see the table below).
2. **Hourly rate**: cost of one hour on this workstation. This is what costs the operations.
3. **Margin**: in %, added to the cost to give the selling price.

| Type | Used for | Appears in |
|---|---|---|
| Productive | An operation done in-house | Routing, planning, operator screen |
| Sub-contracting | An operation given to a supplier | Routing; the operator screen offers a purchase order instead of Play |
| Raw material, Raw material (Sheet), Raw material (Profile), Raw material (block) | The material | Material bill of materials of routings |
| Supplies | Screws and bolts, consumables | Material bill of materials |
| Composed component | A manufactured sub-assembly | Bill of materials, sub-assemblies, item creation from a line |

**How the service costs an operation.** In a routing, choosing a service fills in the
label, then calculates:

- cost = (setup time ÷ quantity + unit time) × hourly rate;
- price = cost × (1 + margin).

![The service costs the operation](img/12-operation-service.png)

> ⚠️ A service **without an hourly rate** gives a cost of 0: the operation is then refused,
> unless you enter the cost and the price yourself. Enter a rate on every productive
> service.
>
> 💡 Create at least one service of type **Composed component**, with a family attached:
> this is what enables the "Create item from this line" button on a quote or order line.
>
> 💡 The **"Moyen de débit (nesting)"** (cutting machine, nesting) switch marks the cutting
> machines used by sheet nesting.

---

## 3. Families: classifying items

A family groups similar items (sheet metal, tube, screws and bolts…). It is required on
every item.

![Families](img/04-familles.png)

1. **Service**: the family is filed under a service (a "Sheet metal" family under the
   material service, for example). This is what filters the components offered later.
2. **"Moyen de débit (nesting)"** (cutting type, nesting): **"Tôle (2D)"** (Sheet, 2D) for
   sheet formats, **"Barre (1D)"** (Bar, 1D) for bars and tubes. Nesting and the material
   requirements calculation use it.

---

## 4. Sections: grouping workstations

A section is a group of workstations: a workshop, a cell, a department ("Cutting
workshop", "Welding workshop"…).

![Sections](img/05-sections.png)

Enter the **Label** and the person in charge (**User management** field). Sections organise
resources in the load planning and in the overview. You also find them in the
Human resources and OSH modules.

---

## 5. Resources: machines and workstations

A resource is **what does the work**: a machine (laser, press brake) or a manual
workstation (assembly team). It carries the **capacity** for the planning.

![Resources](img/06-ressources.png)

1. **Hour capacity by week**: the available hours. The load planning compares the load
   with this capacity.
2. **Section**: where the resource is.
3. **Service**: what it does first. The **Additional services** list the other operations
   it can do, in order of preference.

Other useful settings:

- **Labour**: tick it for a workstation made of people rather than a machine.
  **Operators per hour** gives the staff needed (0.5 = one operator for two machines).
- **Work shift**: the working hours of the resource ("Settings time" menu). Without a shift,
  the capacity is spread over 5 days.
- **Qualified users**: the operators allowed on this workstation.

> ⚠️ **A service without a resource is never scheduled.** Its tasks stay in "Unassigned
> tasks" and its capacity is zero in the planning. Check in the overview that every
> productive service has at least one resource.

---

## 6. Locations: the workshop areas

A location ("Locations" entry of the menu) is a workshop area attached to a resource.
It is used to find workstations in the **Methods overview**.

![Workshop locations](img/07-emplacements.png)

> 💡 These are **not** stock locations: those are managed in the Stock menu.

---

## 7. Tools: bookable tooling

Tools (punches, dies, tool holders…) can be attached to a routing operation. The software
then refuses to book the same tool twice for the same time slot.

![Tools](img/08-outils.png)

1. **Etat** (status): leave the switch on so that the tool is offered in routings.
2. **Unit Cost**.
3. **Qty**: the number of copies.

> 💡 In the "Edit" window, the **"Article de stock"** (stock item) block creates or links an
> item to track the tool's stock and restock it ("Réapprovisionner" (Restock) button).

---

## 8. Inter-operation delays: the time between two operations

Some operations do not follow each other immediately: drying after painting, cooling after
welding… A delay is declared **per pair of operations**, in workshop hours.

![Inter-operation delays](img/09-delais.png)

1. **"Opération de départ"** (From operation).
2. **"Opération d'arrivée"** (To operation).
3. **Delay** in workshop hours, then "Submit".

The planning adds this delay between two consecutive tasks of the same line. A pair that is
not listed has no delay.

---

## 9. Standard bills of materials: standard routings

A standard bill of materials is a **routing template** (operations and materials) that you
reuse: "Laser + bending", "Welded and painted assembly"…

![Standard bills of materials](img/10-nomenclatures.png)

Create the template (External ID, Label), then click "View" to enter its operations and
materials. The screen is the same as the routing of a quote line.

![Routing of a standard template](img/11-nomenclature-gamme.png)

On the routing of a quote or order line, the **"Modèles standards"** (Standard templates)
card offers one button per template: one click adds all its operations to the routing.

> ⚠️ The template costs are calculated for one piece and copied as they are: check
> the setup times on the line's routing when the quantity is large.

---

## Checking the setup: the overview

**Scheduling › Methods overview** menu: one card per service, with its number of
resources and its tasks in progress.

![Methods overview](img/13-vue-ensemble.png)

> 💡 A productive service that shows **Resource : 0** will never be scheduled:
> attach a resource to it (step 5).

## Frequently asked questions

**"Aucune TVA ou unité par défaut configurée" (No VAT or default unit configured) when creating a quote line.**
No unit is "By default": set one (step 1).

**My operation is refused in the routing.**
Its cost is 0: the service has no hourly rate. Enter it (step 2), or enter the cost and
the price by hand.

**The service does not appear in the routing list.**
Only the **Productive** and **Sub-contracting** types appear there. The other types
appear in the material bill of materials.

**My tasks stay in "Unassigned tasks" in the planning.**
No resource is attached to their service (step 5).

**"Create item from this line" fails on a quote line.**
A service of type **Composed component** with at least one family is missing (steps 2 and 3).

**I entered the wrong code.**
The code cannot be changed: create a new entry with the right code and change the label
of the old one so that it is no longer chosen by mistake.
