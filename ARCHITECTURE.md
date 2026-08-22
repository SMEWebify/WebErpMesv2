# Architecture

## Overview
ΣEM (WebErpMesv2) is a Laravel 12 application that combines ERP and MES capabilities.
The codebase follows standard Laravel conventions: Blade provides the layout and page
shells, rich screens are React components mounted into those shells, and both talk to the
same controllers through web and API routes. Assets are bundled with Vite.

> Livewire and Vue.js are no longer part of the stack. Livewire only remains in `vendor/`
> as a transitive dependency of `laravel/pulse`.

## High-level system diagram (logical)

```
┌────────────────────────────────────────────┐
│                Web Browser                 │
│  Blade shells + React components (Vite)    │
│  Alpine.js micro-interactions              │
│  three.js / OpenCascade WASM CAD viewers   │
└──────────────────┬─────────────────────────┘
                   │ HTTP (web + JSON API) / WebSocket
┌──────────────────▼─────────────────────────┐
│                Laravel App                 │
│  Controllers  →  Services  →  Models       │
│  Jobs / Events / Listeners / Observers     │
│  Policies (spatie/laravel-permission)      │
└────────┬──────────────────────┬────────────┘
         │                      │
┌────────▼─────────┐  ┌─────────▼──────────┐
│  Database (SQL)  │  │       Redis        │
│ migrations+seeds │  │ cache, queue, echo │
└──────────────────┘  └────────────────────┘
         │
┌────────▼───────────────────────────────────┐
│  storage/app/private  — attachments (GED)  │
│  served only through authenticated routes  │
└────────────────────────────────────────────┘
```

## Backend (Laravel)
- **Entry points**: `routes/web.php` (localized web routes), `routes/api.php` (Sanctum-protected
  JSON API consumed by the React components and third parties), `routes/auth.php`,
  `routes/channels.php` (broadcast channels) and `routes/console.php` (scheduler).
- **Controllers**: `app/Http/Controllers`, grouped by domain — `Workflow` (quotes, orders,
  deliveries, invoices), `Purchases`, `Products`, `Planning`, `Workshop`, `Quality`,
  `Maintenance`, `Accounting`, `Inspection`, `OSH`, `Companies`, `Admin`, `Api`, `Integrations`.
- **Business logic**: `app/Services` holds the domain logic — calculators (quote, order,
  invoice, credit note), KPI services per module, `StockService` / `StockCalculationService` /
  `StockReservationService` / `StockValuationService`, `Files`, `Integrations`, `N2P`, `Cad`,
  `Exports`, `RgpdAnonymizationService`, `SelectDataService` (cached dropdown data).
- **Models**: `app/Models`, Eloquent entities with `Observers` keeping derived data and caches
  in sync (`app/Observers`).
- **Database**: schema in `database/migrations`, seed data in `database/seeders`.
- **Auth & permissions**: Laravel auth, `spatie/laravel-permission` for roles and permissions,
  `app/Policies` for per-record authorization, `directorytree/ldaprecord-laravel` for LDAP,
  `mcamara/laravel-localization` for localized URLs.
- **Background work**: `app/Jobs`, `app/Events`, `app/Listeners` on the Redis queue. All
  listeners implement `ShouldQueue`. A queue worker is required in every environment where
  emails, integrations or exports must run.
- **Auditing & compliance**: `spatie/laravel-activitylog` for activity trails, soft deletes plus
  `RgpdAnonymizationService` and the `rgpd:*` commands for GDPR obligations.

## Frontend
- **Blade** (`resources/views`): layout, navigation, and page shells that mount React roots.
  AdminLTE 4 is used as the admin theme (`jeroennoten/laravel-adminlte`).
- **React** (`resources/js/components`): every rich screen — index tables, detail pages,
  document line editors, dashboards, planning boards, charts.
- **Alpine.js**: micro-interactions inside Blade only.
- **Styling**: Bootstrap 5 / AdminLTE 4 with custom Sass. Tailwind has been removed.
- **Bundling**: Vite (`vite.config.js`), `npm run dev` in development, `npm run build` for
  production.
- **CAD/document viewers** (`resources/js/components/files`): each engine is `React.lazy()`
  loaded so opening a PDF never downloads the 3D engine.
  - meshes (STL, OBJ, PLY, 3MF, glTF) → three.js
  - B-Rep CAD (STEP, IGES, BREP) → `occt-import-js`, OpenCascade compiled to WebAssembly,
    which removes the need for any server-side converter
  - DXF → `dxf-parser` plus an in-house renderer, orthographic camera

## Document management (GED)
All attachable entities share one storage mechanism:
- `files` rows carry `kind`, `extension`, `disk` and `path`; `fileables` is a polymorphic pivot
  carrying a `role` and an `is_primary` flag.
- Files are written under `storage/app/private/files/{yyyy}/{mm}`, outside the web root, and
  are only reachable through authenticated routes. User-supplied SVG is returned with
  `nosniff` and a sandbox CSP.
- `App\Services\Files\FileableRegistry` whitelists the alias → model mapping so the frontend
  never sends an arbitrary class name.

## Real-time & integrations
- **WebSockets**: Laravel Echo with Pusher/Redis for shop-floor events (task activity, Andon
  alerts) — see `app/Broadcasting`.
- **Electronic invoicing**: `horstoeko/zugferd` produces Factur-X / EN 16931 invoices;
  `app/Services/Integrations/Pdp` implements the PDP gateway abstraction (Qonto driver) and
  reads inbound Factur-X supplier invoices.
- **Nest2Prod**: `app/Services/N2P` pushes orders and synchronizes sheet stock.
- **Documents & exports**: `barryvdh/laravel-dompdf`, `setasign/fpdi`, `webklex/laravel-pdfmerger`
  for PDF, `maatwebsite/excel` / `phpoffice/phpspreadsheet` for Excel and FEC exports,
  `milon/barcode` for barcodes.
- **Monitoring**: `laravel/pulse` for application health, `spatie/laravel-backup` for scheduled
  database and storage backups.

## Data flow (typical request)
1. A user action in the browser triggers an HTTP request — a full page load for a Blade shell,
   or a `fetch` to the JSON API from a React component.
2. The route resolves to a controller action; a form request validates the input.
3. The controller delegates to a service, which orchestrates models inside a transaction when
   several rows must stay consistent.
4. Eloquent reads/writes the SQL database; observers refresh derived values and bust caches.
5. Heavy or external work is dispatched to the Redis queue; events may be broadcast over Echo.
6. The response is a Blade view or JSON; the React component re-renders from the JSON payload.

## Configuration & environments
- **Environment**: `.env` defines database, cache, queue, mail, broadcast and integration
  settings. `php artisan wem:diagnostics` checks that the environment is complete.
- **Local development**: `php artisan serve` plus `npm run dev`.
- **Docker**: `docker-compose.yaml` and `docker/` provide the full local stack (Nginx, PHP-FPM,
  database, Redis).
- **Production**: a queue worker under Supervisor and a `schedule:run` cron entry are both
  required. Cache config and views, but never run `php artisan route:cache` — it freezes the
  locale resolved at cache time and breaks the localized routes.

## Key directories
- `app/Http/Controllers`: HTTP controllers for ERP/MES features, grouped by domain.
- `app/Services`: business logic, calculators, KPI and integration services.
- `app/Models`: core domain entities.
- `app/Jobs`, `app/Events`, `app/Listeners`, `app/Observers`: asynchronous and reactive work.
- `resources/views`: Blade layouts and page shells.
- `resources/js`: React components, viewers and shared JS libraries.
- `database/migrations`: schema definitions.
- `routes/`: web, API, auth, broadcast channel and console routes.
- `config/`: application and package configuration.
- `tests/`: PHPUnit feature and unit tests.
