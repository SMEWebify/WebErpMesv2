# ΣEM - Boost Your Manufacturing Efficiency 🚀

<p align="center">
  <a href="https://github.com/SMEWebify/WebErpMesv2/blob/WEM-2.0/README.md">English</a> •
  <a href="https://github.com/SMEWebify/WebErpMesv2/blob/WEM-2.0/docs/README.fr.md">Français</a>
</p>

![image](https://github.com/SMEWebify/WebErpMesv2/assets/75578469/bcc022c1-465e-44fb-a7ce-011f9096eba7)

## Why ΣEM ?

Manufacturing often faces a gap between quoting and production. ΣEM bridges that gap, providing an all-in-one solution for:

- 🔄 Real-Time Production Tracking: From quote to delivery, track orders and production progress seamlessly.
- 🤝 CRM & Order Management: Integrated tools to streamline business processes and customer relationships.
- 📦 Stock & Resource Management: Optimize inventory, raw materials, and resources with ease.
- 🌐 Community-Driven: Open-source with ongoing improvements from a passionate developer community.

<img width="1877" height="831" alt="image" src="https://github.com/user-attachments/assets/21b92345-46ad-4af2-9f3a-b38d601eb091" />


### Try the Demo 👀  
[Demo page](http://demo.wem-project.org) 

login : contact@wem-project.org 

Password : password

### Install :construction_worker:
for test or help to develop follow this link : [wiki page (install to dev)](https://github.com/SMEWebify/WebErpMesv2/wiki/Installation-Steps-(for-dev))

for run to production, follow this link :  [wiki page (install to run)](https://github.com/SMEWebify/WebErpMesv2/wiki/Installation-Steps-(for-production))
![image](https://github.com/user-attachments/assets/f527881c-a7c4-460a-9b06-f647c91402d8)


### Post-installation configuration
Before adding lines to a quote, set a **Default VAT** and a **Default Unit**.
In the application, go to **Accounting → VAT**, then to **Methods → Units**
and mark an item as the default value. Without these settings, it is impossible
to add lines to a quote.

### Project structure at a glance

- `app/Http/Controllers` — API and web controllers for quotes, production, inventory, and CRM modules.
- `app/Models` — Eloquent models representing core business entities (orders, products, stocks, etc.).
- `database/migrations` — Schema definitions for ERP and MES tables, including BOMs, routings, and stock movements.
- `resources/js` — Vue.js front-end code compiled via Laravel Mix (see `webpack.mix.js`).
- `resources/views` — Blade templates for server-rendered pages and layouts.
- `docker/` and `docker-compose.yaml` — Development stack definitions (PHP-FPM, Nginx, database, Redis).

### Local development (without Docker)

Use this workflow if you prefer running the stack natively:

```bash
cp .env.example .env             # Configure your environment variables
composer install                 # Install PHP dependencies
php artisan key:generate         # Generate the application key
php artisan migrate --seed       # Create and seed the database
npm install                      # Install JS dependencies
npm run dev                      # Build front-end assets (use npm run watch for active dev)
php artisan serve                # Start the application at http://localhost:8000
```

### Running the test suite

Execute the backend tests with:

```bash
php artisan test
```

You can also target a subset of tests by appending the `--filter` option.

### Docker setup

A docker-compose configuration is provided for local development. Start the stack with:

```bash
docker compose up --build
```

The application will be available at http://localhost:45060.




https://github.com/user-attachments/assets/200e1322-ae60-4270-aa9c-0a28e5ca737a







