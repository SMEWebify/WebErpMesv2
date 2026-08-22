# ΣEM - The ERP/MES Dedicated to Sheet Metal & Industrial Mechanics 🏭

<p align="center">
  <a href="https://github.com/SMEWebify/WebErpMesv2/blob/WEM-2.0/README.md">English</a> •
  <a href="https://github.com/SMEWebify/WebErpMesv2/blob/WEM-2.0/docs/README.fr.md">Français</a>
</p>

<p align="center">
  <a href="https://github.com/SMEWebify/WebErpMesv2/stargazers"><img src="https://img.shields.io/github/stars/SMEWebify/WebErpMesv2?style=social" alt="Stars"></a>
  <a href="https://github.com/SMEWebify/WebErpMesv2/network/members"><img src="https://img.shields.io/github/forks/SMEWebify/WebErpMesv2?style=social" alt="Forks"></a>
  <a href="https://github.com/SMEWebify/WebErpMesv2/issues"><img src="https://img.shields.io/github/issues/SMEWebify/WebErpMesv2" alt="Issues"></a>
  <a href="https://github.com/SMEWebify/WebErpMesv2/blob/WEM-2.0/LICENSE"><img src="https://img.shields.io/github/license/SMEWebify/WebErpMesv2" alt="License"></a>
  <a href="https://github.com/SMEWebify/WebErpMesv2"><img src="https://img.shields.io/github/downloads/SMEWebify/WebErpMesv2/total" alt="License"></a>
</p>

<img width="1904" height="952" alt="image" src="https://github.com/user-attachments/assets/90853ec1-59dd-4a9c-9d90-3477103fa8cc" />


## 🎯 Why ΣEM for your workshop?

Designed **by sheet metal and mechanical professionals, for professionals**, ΣEM answers the specific challenges of your trade:

### 🔧 Your activity
- **Industrial sheet metal**: bending, laser/plasma cutting, punching, welding
- **Precision mechanics**: turning, milling, grinding
- **Mold manufacturing**: design, machining, fitting
- **Industrial subcontracting**: multi-customer management, varied series

### 💡 Your day-to-day problems solved

| Problem | ΣEM Solution |
|----------|-------------|
| 📋 **Complex quotes** with many operations | Detailed BOMs, machining routings, automatic time/cost calculation |
| ⏱️ **Real-time production tracking** not possible | Live dashboard, shop-floor time tracking, progress by work order |
| 📦 **Raw material management** (sheets, bars, tubes) | Stock by dimensions, material traceability, replenishment alerts |
| 🔄 **Chaotic shop planning** | Visual planning per machine, priority management, machine load |
| 📊 **Unclear project profitability** | Actual vs. forecast cost tracking, margin analysis per order |
| 🚚 **Limited customer/supplier traceability** | Full history, attached documents, technical notes |

## ✨ Specialized business features

### 🏭 Production Module (MES)
- **Machining routings**: define operations (cutting, bending, welding, machining...)
- **Bills of Materials (BOM)**: raw materials, components, sub-assemblies
- **Shop planning**: visualize load per machine/work center
- **Work orders**: automatic generation from quotes
- **Production time tracking**: real-time tracking by operation
- **Quality control**: control sheets, non-conformities

### 📋 Sales Management
- **Detailed quotes**: multiple lines, options, variants
- **Cost calculation**: material + labor + subcontracting
- **Opportunity tracking**: from lead to delivery
- **Multi-currency** and multi-language support
- **Customer history**: all orders at a glance

### 📦 Inventory & Procurement
- **Dimension-based management**: Sheet 2000x1000x3mm, Tube Ø50x3...
- **Material traceability**: heat numbers, material certificates
- **Stock movements**: receipts, issues, transfers, inventories
- **Alerts**: minimum thresholds, automatic replenishment
- **Suppliers**: pricing, lead times, evaluation

### 💰 Accounting & Invoicing
- **Invoicing**: deposits, progress billing, credit notes
- **VAT**: multi-rate management, declarations
- **Payments**: settlement tracking, reminders
- **Analytics**: profitability by project, customer, period

### 🗂️ Document management with built-in CAD viewers
- **Unified attachments**: products, quotes, orders, delivery notes, invoices, purchases,
  non-conformities, stock movements, companies and opportunities share one file store
- **Private storage**: files live outside the web root and are served through authenticated routes
- **Viewers loaded on demand** (opening a PDF never downloads the 3D engine):
  - **3D meshes**: STL, OBJ, PLY, 3MF, glTF (three.js)
  - **3D CAD**: STEP, IGES, BREP via OpenCascade compiled to WebAssembly — no server-side converter
  - **2D CAD**: DXF, plus PDF, images and spreadsheets

### 🔧 Maintenance (CMMS) & shop floor
- **Maintenance plans and work orders** per machine
- **Andon alerts**: real-time escalation from the shop floor
- **Shop reports**: booked vs. estimated time, scrap, machine load
- **Kanban and GTD boards**, Gantt chart, machine load planning
- **Nesting**: part layout attached to order lines
- **Attendance and energy consumption tracking**

### ✅ Quality, EHS & compliance
- **Non-conformities**, inspection projects, audit planner, FMEA
- **Control device calibration** with automatic alerts
- **EHS**: incidents, risks, trainings, regulatory conformities
- **GDPR**: soft deletes, activity log, anonymization service, data export and erasure,
  automatic weekly purge, self-service requests

### 🔌 Integrations & automation
- **Electronic invoicing**: Factur-X / EN 16931 output, PDP gateway (Qonto driver) and
  inbound Factur-X reading for supplier invoices
- **FEC export** for French accounting
- **Nest2Prod**: order push and sheet stock synchronization
- **LDAP / Active Directory** user import
- **REST API** (Laravel Sanctum) and an integrated AI assistant
- **Automatic backups** (spatie/laravel-backup) and **Laravel Pulse** application monitoring


## 💼 Industry use cases

### Example 1: Fine sheet metal workshop
**Context**: 15 people, bending + laser cutting + welding

**ΣEM usage**:
- Quotes with detailed routings (laser → deburring → bending → welding)
- Planning across 3 press brakes and 2 lasers
- Sheet stock management by format and thickness
- Real-time tracking of work orders in progress

**Result**: +30% planning productivity, -20% dormant stock

### Example 2: Precision mechanics
**Context**: Automotive subcontracting, medium series

**ΣEM usage**:
- BOMs with certified raw materials
- Machining routings (turning → milling → heat treatment → grinding)
- Integrated quality control (inspection sheets per work order)
- Full traceability from material to finished part

**Result**: ISO 9001 compliance, perfect traceability

### Example 3: Mold manufacturer
**Context**: Plastic injection molds, complex projects

**ΣEM usage**:
- Multi-phase quotes (study, roughing, finishing, trials)
- Subcontracting management (heat treatments, polishing)
- Real-time project profitability tracking
- Centralized technical documentation

**Result**: Better cost control, on-time delivery


## 🎬 See ΣEM in action

### 📺 Online demo
**[Try the demo](http://demo.wem-project.org)**

- **Email**: contact@wem-project.org 
- **Password**: password

### 🎥 Presentation video

https://github.com/user-attachments/assets/200e1322-ae60-4270-aa9c-0a28e5ca737a

<img width="1877" height="831" alt="image" src="https://github.com/user-attachments/assets/21b92345-46ad-4af2-9f3a-b38d601eb091" />


### Installation

## 🚀 Quick installation

### Option 1: Docker (Recommended) 🐳

Start in **3 minutes**:

```bash
git clone https://github.com/SMEWebify/WebErpMesv2.git
cd WebErpMesv2
docker compose up --build
```

➡️ Access http://localhost:45060

### Option 2: Local installation 💻

```bash
# Clone and configure
git clone https://github.com/SMEWebify/WebErpMesv2.git
cd WebErpMesv2
cp .env.example .env

# Install dependencies
composer install
npm install

# Configure the application
php artisan key:generate
php artisan migrate --seed

# Run
npm run dev
php artisan serve
```

➡️ Access http://localhost:8000

> 📚 **Detailed guides**: 
> - [Development setup](https://github.com/SMEWebify/WebErpMesv2/wiki/Installation-Steps-(for-dev))
> - [Production deployment](https://github.com/SMEWebify/WebErpMesv2/wiki/Installation-Steps-(for-production))

### ⚙️ Post-Installation Configuration

**Important**: Before adding lines to a quote, configure:

1. **Default VAT**: Go to **Accounting → VAT** and mark an item as default
2. **Default Unit**: Go to **Methods → Units** and mark an item as default

Without these settings, you cannot add lines to quotes.

<img width="1903" height="947" alt="image" src="https://github.com/user-attachments/assets/8a51c3da-1dc8-4c55-91dc-f86a23f4b9d8" />


## 🏗️ Technical architecture

### Modern technology stack
```
WebErpMesv2/
├── app/
│   ├── Http/Controllers/    # API and web controllers (quotes, production, CRM)
│   ├── Models/              # Business entities (orders, products, stocks)
│   └── Services/            # Business logic (stock, files, integrations, GDPR)
├── database/
│   └── migrations/          # Database schemas (BOMs, routings, stock movements)
├── resources/
│   ├── js/                  # React components
│   └── views/               # Blade templates
├── tests/                   # Test suite
└── docker/                  # Docker configuration
```

**Key Technologies**:
- **Backend**: Laravel 12, PHP 8.2+
- **Frontend**: React 19 (rich components), Blade (layout/shell), Alpine.js (micro-interactions)
- **Bundler**: Vite
- **CSS**: Bootstrap 5 / AdminLTE 4
- **Database**: MySQL/PostgreSQL
- **Cache & queues**: Redis
- **Real-time**: Laravel Echo
- **2D/3D viewers**: three.js, occt-import-js (OpenCascade WASM), dxf-parser
- **DevOps**: Docker, Nginx

> Livewire and Vue.js have been removed: rich screens are React components mounted from Blade shells.

### 🧪 Tests

Run the complete test suite:

```bash
php artisan test
```

Run specific tests:

```bash
php artisan test --filter TestName
```

## 🛠️ Custom Artisan Commands (Specific Artisan Commands)

These commands are defined in this repository and complement the default Laravel tooling.

| Command | Description | Example |
| --- | --- | --- |
| `php artisan wem:diagnostics` | Check common local setup requirements (PHP version, extensions, app key, cache/storage permissions, Redis, DB, broadcasting). | `php artisan wem:diagnostics` |
| `php artisan wem:files:import` | One-shot migration of legacy `public/` attachments into the private file store. `--dry-run`, `--skip-move`. | `php artisan wem:files:import --dry-run` |
| `php artisan wem:n2p:push-order {orderId} {--sync}` | Push a specific order to Nest2Prod (sync option bypasses the queue). | `php artisan wem:n2p:push-order 123 --sync` |
| `php artisan wem:n2p:sync-sheet-stock` | Synchronize sheet stock with Nest2Prod. `--days=30`, `--sync`. | `php artisan wem:n2p:sync-sheet-stock --days=7` |
| `php artisan preorders:scan-output` | Scan the output folder and import CSV files as pre-orders. `--path=`, `--pattern=`, `--done-path=`. | `php artisan preorders:scan-output` |
| `php artisan stock:recalculate-cump` | Recompute the full weighted-average-cost history for every product location. `--dry-run`. | `php artisan stock:recalculate-cump --dry-run` |
| `php artisan stock:rebuild-reservations` | Rebuild stock reservations for purchased components. `--product=`. | `php artisan stock:rebuild-reservations` |
| `php artisan quality:dispatch-calibration-alerts` | Notify responsible users when quality control device calibration is due or overdue. | `php artisan quality:dispatch-calibration-alerts` |
| `php artisan emails:send-auto-reports` | Send scheduled automatic email reports based on user preferences. | `php artisan emails:send-auto-reports` |
| `php artisan ldap:import-users` | Import LDAP users into the Laravel database. | `php artisan ldap:import-users` |
| `php artisan rgpd:export-contact {id}` | Export every piece of data held about a contact as JSON (GDPR art. 20). | `php artisan rgpd:export-contact 42` |
| `php artisan rgpd:erase-contact {id}` | Handle a GDPR erasure request for a B2B contact. | `php artisan rgpd:erase-contact 42` |
| `php artisan rgpd:purge` | Purge personal data past its retention period (expired tokens, old email logs, soft-deleted records). | `php artisan rgpd:purge` |
| `php artisan wem:nesting:seed-svg` | Attach demo nesting vectors to order lines. `--count=`, `--force`. | `php artisan wem:nesting:seed-svg` |
| `php artisan loadtest:seed` | Generate a realistic dataset for load testing. `--scale=`. | `php artisan loadtest:seed --scale=0.5` |

### ⏱️ Scheduled tasks & queue

The scheduler (`routes/console.php`) drives the daily backups (`backup:run`, `backup:clean`,
`backup:monitor`), the weekly GDPR purge (`rgpd:purge`) and the monthly activity-log cleanup.
Register it once on the server:

```
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

A queue worker is required for asynchronous work (emails, integrations, exports):

```bash
php artisan queue:work redis --sleep=3 --tries=3
```

> Note: do **not** run `php artisan route:cache` — it freezes the locale resolved at cache
> time and breaks the localized routes.

## 🤝 Contribute to the project

ΣEM is **open source**! Your domain expertise is valuable.

### 🌟 How to help?

**Developers**:
- Fix bugs → [`good first issue`](https://github.com/SMEWebify/WebErpMesv2/labels/good%20first%20issue)
- Add features → see [Roadmap](ROADMAP.md)
- Improve documentation → [Contributing Guide](CONTRIBUTING.md)

**Industry professionals**:
- Test and provide feedback
- Propose domain improvements
- Share your use cases
- Translate the interface

### Development Workflow

```bash
# Fork the repository and clone it
git clone https://github.com/YOUR_USERNAME/WebErpMesv2.git

# Create a feature branch
git checkout -b feature/amazing-feature

# Make your changes and commit
git commit -m "Add amazing feature"

# Push and create a Pull Request
git push origin feature/amazing-feature
```

## 👥 Contributors

Thanks to everyone who keeps this project alive!

<table>
  <tr>
    <td align="center">
      <a href="https://github.com/SMEWebify">
        <img src="https://github.com/SMEWebify.png" width="100px;" alt="SMEWebify"/>
        <br />
        <sub><b>SMEWebify</b></sub>
      </a>
      <br />
      <sub>Creator & Lead Maintainer</sub>
      <br />
      <sub>1,225+ commits</sub>
    </td>
    <td align="center">
      <a href="https://github.com/sunxiaoguang">
        <img src="https://github.com/sunxiaoguang.png" width="100px;" alt="sunxiaoguang"/>
        <br />
        <sub><b>sunxiaoguang</b></sub>
      </a>
      <br />
      <sub>Core Contributor</sub>
      <br />
      <sub>11 commits</sub>
    </td>
    <td align="center">
      <a href="https://github.com/saosangmo">
        <img src="https://github.com/saosangmo.png" width="100px;" alt="saosangmo"/>
        <br />
        <sub><b>saosangmo</b></sub>
      </a>
      <br />
      <sub>Active Contributor</sub>
      <br />
      <sub>8 commits</sub>
    </td>
    <td align="center">
      <a href="https://github.com/RobertoBochet">
        <img src="https://github.com/RobertoBochet.png" width="100px;" alt="RobertoBochet"/>
        <br />
        <sub><b>RobertoBochet</b></sub>
      </a>
      <br />
      <sub>Contributor</sub>
      <br />
      <sub>3 commits</sub>
    </td>
  </tr>
  <tr>
    <td align="center">
      <a href="https://github.com/globalcitizen">
        <img src="https://github.com/globalcitizen.png" width="100px;" alt="globalcitizen"/>
        <br />
        <sub><b>globalcitizen</b></sub>
      </a>
      <br />
      <sub>Contributor</sub>
      <br />
      <sub>1 commit</sub>
    </td>
    <td align="center">
      <a href="https://github.com/nedlir">
        <img src="https://github.com/nedlir.png" width="100px;" alt="nedlir"/>
        <br />
        <sub><b>nedlir</b></sub>
      </a>
      <br />
      <sub>Contributor</sub>
    </td>
    <td align="center">
      <a href="https://github.com/SMEWebify/WebErpMesv2/graphs/contributors">
        <img src="https://via.placeholder.com/100x100/4a5568/ffffff?text=%2B1" width="100px;" alt="More contributors"/>
        <br />
        <sub><b>+1 more</b></sub>
      </a>
      <br />
      <sub><a href="https://github.com/SMEWebify/WebErpMesv2/graphs/contributors">See all →</a></sub>
    </td>
  </tr>
</table>

### Want to be featured here?

Check our [Contributing Guide](CONTRIBUTING.md) and make your first contribution!


## 📊 Project Stats

- ⭐ **180+** Stars
- 🍴 **88** Forks
- 👥 **7+** Active Contributors
- 📝 **2,200+** Commits
- 🎉 **21** Releases (latest: v1.19)
- 🧪 **60+** PHPUnit test files
- 📦 **Open Source** under MIT License

## 📚 Documentation

- 📖 [User Guide](https://github.com/SMEWebify/WebErpMesv2/wiki)
- 🔧 [Development Setup](https://github.com/SMEWebify/WebErpMesv2/wiki/Installation-Steps-(for-dev))
- 🚀 [Production Deployment](https://github.com/SMEWebify/WebErpMesv2/wiki/Installation-Steps-(for-production))
- 🏗️ [Architecture Overview](ARCHITECTURE.md)
- 🤝 [Contributing Guide](CONTRIBUTING.md)
- 🔒 [Security Policy](SECURITY.md)

## 🗺️ Roadmap

Check our [roadmap](ROADMAP.md) to see what's coming next and how you can help!

**Current priorities:**
- 🧪 Test coverage improvement (backend business rules; no frontend tests yet)
- 📚 Complete API documentation ([docs/API.md](docs/API.md))
- 📦 Stock: projected-stock curve, physical inventory screen, automatic replenishment alerts
- 🐳 Multi-tenant Docker deployment (one container + one database per customer)

## 💬 Support & Community

- 💭 [GitHub Discussions](https://github.com/SMEWebify/WebErpMesv2/discussions) - Questions, ideas, feedback
- 🐛 [Issue Tracker](https://github.com/SMEWebify/WebErpMesv2/issues) - Bugs and feature requests
- 📧 [Email](mailto:contact@wem-project.org) - Direct support
- 🌐 [Online demo](http://demo.wem-project.org) - Try it for free

## 📄 License

Project under the **MIT** license - See [LICENSE](LICENSE)

You are free to:
- ✅ Use commercially
- ✅ Modify the code
- ✅ Distribute
- ✅ Use privately

## 🙏 Acknowledgements

Thanks to:
- All our [contributors](https://github.com/SMEWebify/WebErpMesv2/graphs/contributors)
- The workshops that test and provide feedback
- The Laravel and React communities
- Everyone who starred ⭐ the project

---

## 🏭 Built for industry, by industry

ΣEM was born from hands-on experience in industrial sheet metal. Every feature meets a real workshop need.

**Are you a sheet metal worker, mechanic, machinist?**  
Your feedback is invaluable to improve the tool → [Contact us](mailto:contact@wem-project.org)

**Are you a developer passionate about industry?**  
Join us → [Contributing Guide](CONTRIBUTING.md)

---

<p align="center">
  <b>Made with ❤️ for sheet metal and mechanical professionals</b>
  <br />
  <br />
  <a href="https://github.com/SMEWebify/WebErpMesv2/stargazers">⭐ Star the project</a> •
  <a href="https://github.com/SMEWebify/WebErpMesv2/fork">🍴 Fork</a> •
  <a href="CONTRIBUTING.md">🤝 Contribute</a> •
  <a href="http://demo.wem-project.org">🎬 Try the demo</a>
</p>
