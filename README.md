# ΣEM - Boost Your Manufacturing Efficiency 🚀

<p align="center">
  <a href="https://github.com/SMEWebify/WebErpMesv2/blob/WEM-2.0/README.md">English</a> •
  <a href="https://github.com/SMEWebify/WebErpMesv2/blob/WEM-2.0/docs/README.fr.md">Français</a>
</p>

<p align="center">
  <a href="https://github.com/SMEWebify/WebErpMesv2/stargazers"><img src="https://img.shields.io/github/stars/SMEWebify/WebErpMesv2?style=social" alt="Stars"></a>
  <a href="https://github.com/SMEWebify/WebErpMesv2/network/members"><img src="https://img.shields.io/github/forks/SMEWebify/WebErpMesv2?style=social" alt="Forks"></a>
  <a href="https://github.com/SMEWebify/WebErpMesv2/issues"><img src="https://img.shields.io/github/issues/SMEWebify/WebErpMesv2" alt="Issues"></a>
  <a href="https://github.com/SMEWebify/WebErpMesv2/blob/WEM-2.0/LICENSE"><img src="https://img.shields.io/github/license/SMEWebify/WebErpMesv2" alt="License"></a>
</p>

![image](https://github.com/SMEWebify/WebErpMesv2/assets/75578469/bcc022c1-465e-44fb-a7ce-011f9096eba7)

## Why ΣEM ?

Manufacturing often faces a gap between quoting and production. ΣEM bridges that gap, providing an all-in-one solution for:

- 🔄 **Real-Time Production Tracking**: From quote to delivery, track orders and production progress seamlessly.
- 🤝 **CRM & Order Management**: Integrated tools to streamline business processes and customer relationships.
- 📦 **Stock & Resource Management**: Optimize inventory, raw materials, and resources with ease.
- 🌐 **Community-Driven**: Open-source with ongoing improvements from a passionate developer community.

<img width="1877" height="831" alt="image" src="https://github.com/user-attachments/assets/21b92345-46ad-4af2-9f3a-b38d601eb091" />

## 🚀 Quick Start

### Try the Demo 👀  
**[Live Demo](http://demo.wem-project.org)**

- **Login**: contact@wem-project.org 
- **Password**: password

### Installation

#### 🐳 Docker (Recommended)

```bash
git clone https://github.com/SMEWebify/WebErpMesv2.git
cd WebErpMesv2
docker compose up --build
```

The application will be available at http://localhost:45060

#### 💻 Local Development

```bash
# Clone and setup
git clone https://github.com/SMEWebify/WebErpMesv2.git
cd WebErpMesv2
cp .env.example .env

# Install dependencies
composer install
npm install

# Configure application
php artisan key:generate
php artisan migrate --seed

# Build and run
npm run dev
php artisan serve
```

Visit http://localhost:8000

> 📚 **Detailed guides**: 
> - [Development setup](https://github.com/SMEWebify/WebErpMesv2/wiki/Installation-Steps-(for-dev))
> - [Production deployment](https://github.com/SMEWebify/WebErpMesv2/wiki/Installation-Steps-(for-production))

### ⚙️ Post-Installation Configuration

**Important**: Before adding lines to a quote, configure:

1. **Default VAT**: Go to **Accounting → VAT** and mark an item as default
2. **Default Unit**: Go to **Methods → Units** and mark an item as default

Without these settings, you cannot add lines to quotes.

<img width="831" alt="Configuration screenshot" src="https://github.com/user-attachments/assets/f527881c-a7c4-460a-9b06-f647c91402d8" />

## 🏗️ Project Structure

```
WebErpMesv2/
├── app/
│   ├── Http/Controllers/    # API and web controllers (quotes, production, CRM)
│   └── Models/              # Business entities (orders, products, stocks)
├── database/
│   └── migrations/          # Database schemas (BOMs, routings, stock movements)
├── resources/
│   ├── js/                  # Vue.js frontend
│   └── views/               # Blade templates
├── tests/                   # Test suite
└── docker/                  # Docker configuration
```

**Key Technologies**:
- **Backend**: Laravel 11, PHP 8.2
- **Frontend**: Vue.js 3, Tailwind CSS
- **Database**: MySQL/PostgreSQL
- **Cache**: Redis
- **DevOps**: Docker, Nginx

## 🧪 Testing

Run the complete test suite:

```bash
php artisan test
```

Run specific tests:

```bash
php artisan test --filter TestName
```

## 🤝 Contributing

We welcome contributions! Whether you're fixing bugs, adding features, or improving documentation, your help is appreciated.

### Getting Started

1. Check out our [Contributing Guide](CONTRIBUTING.md)
2. Look for issues labeled [`good first issue`](https://github.com/SMEWebify/WebErpMesv2/labels/good%20first%20issue)
3. Join the discussion in [GitHub Discussions](https://github.com/SMEWebify/WebErpMesv2/discussions)

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

Thanks to all the amazing people who have contributed to this project!

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

## 📹 Video Demo

https://github.com/user-attachments/assets/200e1322-ae60-4270-aa9c-0a28e5ca737a

## 📊 Project Stats

- ⭐ **182** Stars
- 🍴 **88** Forks  
- 👥 **7+** Active Contributors
- 📝 **1,225+** Commits
- 🎉 **20** Releases
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
- 📄 EN16931 electronic invoicing API
- 🧪 Test coverage improvement
- 📚 Complete API documentation

## 💬 Community & Support

- 💭 [GitHub Discussions](https://github.com/SMEWebify/WebErpMesv2/discussions) - Questions and ideas
- 🐛 [Issue Tracker](https://github.com/SMEWebify/WebErpMesv2/issues) - Bug reports and feature requests
- 🌐 [Website](http://demo.wem-project.org) - Official demo

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🙏 Acknowledgments

Special thanks to:
- All our amazing [contributors](https://github.com/SMEWebify/WebErpMesv2/graphs/contributors)
- The Laravel and Vue.js communities
- Everyone who has starred, forked, or used this project

---

<p align="center">
  Made with ❤️ by the WebErpMesv2 community
  <br />
  <br />
  <a href="https://github.com/SMEWebify/WebErpMesv2/stargazers">⭐ Star us on GitHub</a> •
  <a href="https://github.com/SMEWebify/WebErpMesv2/fork">🍴 Fork the project</a> •
  <a href="CONTRIBUTING.md">🤝 Contribute</a>
</p>
