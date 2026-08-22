# Roadmap

This document lists what has recently landed and what we plan to work on next. It is a living
document, not a commitment to dates. Priorities move with real workshop feedback — if something
here matters to you, say so in a
[discussion](https://github.com/SMEWebify/WebErpMesv2/discussions) or an
[issue](https://github.com/SMEWebify/WebErpMesv2/issues).

## ✅ Recently shipped

- **Full React frontend migration** — Vue.js and Livewire removed; every rich screen (index
  tables, document line editors, dashboards, planning boards) is a React component mounted from
  a Blade shell.
- **Vite** replaces Laravel Mix; Tailwind removed in favour of a single Bootstrap 5 / AdminLTE 4
  stack.
- **Unified document management (GED)** — one file store shared by every attachable entity,
  private storage outside the web root, authenticated download routes.
- **In-browser CAD viewers** — STL/OBJ/PLY/3MF/glTF via three.js, STEP/IGES/BREP via OpenCascade
  compiled to WebAssembly (no server-side converter), DXF via a dedicated renderer.
- **Electronic invoicing** — Factur-X / EN 16931 output, PDP gateway abstraction with a Qonto
  driver, and inbound Factur-X reading for supplier invoices.
- **Automatic backups** (`spatie/laravel-backup`) with scheduled run, cleanup and monitoring.
- **GDPR tooling** — soft deletes, activity log, anonymization service, contact export and
  erasure commands, weekly automatic purge, self-service requests.
- **Stock hardening** — weighted average cost (CUMP) recalculation, stock reservations,
  locking on transfers, negative-quantity validation.

## 🔨 In progress

- **Test coverage** — backend business rules first (stock, calculators, document workflows).
  There is currently no frontend test suite.
- **API documentation** — completing [docs/API.md](docs/API.md) for the public REST endpoints.

## 🗺️ Next

### Stock & procurement
- **Projected stock**: time curve combining physical stock, pending purchase orders and
  reservations, with a "shortage expected at D+X" column and a chart on the product page.
- **Physical inventory**: the `inventory_details` table exists but is not wired to any screen —
  counting, variance analysis and regularization need to be built.
- **Automatic replenishment alerts**: `mini_qty` currently only drives UI colouring; no event,
  notification or email is triggered when stock drops below the threshold.
- **Automatic BOM allocation** when a task is closed, instead of the current manual step.
- **FIFO/FEFO consumption**: batch expiry dates are stored but allocation does not sort by them.
- **Receiving quarantine**: an inspection step before received goods enter available stock.

### Production & planning
- Finer machine-load and capacity planning.
- Deeper shop-floor reporting (booked vs. estimated time, scrap analysis).

### Deployment
- **Multi-tenant Docker deployment**: one container and one database per customer, managed
  behind a reverse proxy, to replace per-customer manual installation.

### Quality & compliance
- Broader traceability reporting for ISO 9001 audits.
- Documented retention periods per data type.

## 🙌 Good places to help

- Pick up a [`good first issue`](https://github.com/SMEWebify/WebErpMesv2/labels/good%20first%20issue).
- Add PHPUnit coverage for a business rule you rely on.
- Translate the interface into your language.
- Report what does not match your shop's reality — domain feedback is worth as much as code.

See the [Contributing Guide](CONTRIBUTING.md) to get started.
