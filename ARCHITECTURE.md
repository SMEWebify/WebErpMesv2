# Architecture

## Overview
ΣEM (WebErpMesv2) is a Laravel 12 application that combines ERP and MES capabilities.
The codebase follows standard Laravel conventions with a server-rendered Blade UI,
API endpoints for dynamic features, and a Vue.js front-end bundle compiled with
Laravel Mix.

## High-level system diagram (logical)

```
┌──────────────────────────┐
│       Web Browser        │
│  Blade views + Vue apps  │
└─────────────┬────────────┘
              │ HTTP/WebSocket
┌─────────────▼────────────┐
│      Laravel App         │
│ Controllers / Services   │
│ Jobs / Events / Policies │
│    Eloquent Models       │
└─────────────┬────────────┘
              │
┌─────────────▼────────────┐
│      Database (SQL)      │
│ migrations + seeders     │
└──────────────────────────┘
```

## Backend (Laravel)
- **Entry points**: HTTP requests are routed in `routes/` (web + API) to controllers
  under `app/Http/Controllers`.
- **Business logic**: Domain behavior is represented by Eloquent models in `app/Models`
  and related service classes where applicable.
- **Database**: Schema is managed via `database/migrations`, with seed data in
  `database/seeders`.
- **Auth & permissions**: Laravel authentication with `spatie/laravel-permission`
  for roles/permissions and `mcamara/laravel-localization` for localization support.
- **Background work**: Laravel queue/jobs and events (standard `app/Jobs`,
  `app/Events`, `app/Listeners` conventions) are used for async work when enabled.

## Frontend
- **Server-rendered UI**: Blade templates in `resources/views` provide the base layout.
- **SPA-style components**: Vue 3 components and JS in `resources/js` are bundled
  with Laravel Mix (`webpack.mix.js`).
- **Styling**: Tailwind CSS, Bootstrap, AdminLTE, and custom Sass assets are compiled
  via Mix.

## Real-time & integrations
- **WebSockets**: Laravel Echo and Pusher client dependencies are included for
  real-time updates (e.g., production tracking notifications).
- **PDF/Excel**: Libraries for PDF generation and Excel export are used in backend
  workflows (e.g., quotes, orders, reports).

## Data flow (typical request)
1. User action in browser triggers HTTP request.
2. Route matches controller action.
3. Controller orchestrates model/service calls.
4. Eloquent reads/writes to SQL database.
5. Response returns Blade view or JSON; front-end Vue updates UI when applicable.

## Configuration & environments
- **Environment**: `.env` defines DB, cache, queue, mail, and broadcast settings.
- **Local development**: `php artisan serve` for backend and `npm run dev` for assets.
- **Docker**: `docker-compose.yaml` provides a local stack for running the full app.

## Key directories
- `app/Http/Controllers`: HTTP controllers for ERP/MES features.
- `app/Models`: Core domain entities.
- `resources/views`: Blade templates.
- `resources/js`: Vue components and JS assets.
- `database/migrations`: Schema definitions.
- `routes/`: Web and API routes.
- `config/`: Application and service configuration.
