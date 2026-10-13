# Contributing to ΣEM (WebErpMesv2)

We love your input! We want to make contributing to this project as easy and transparent as possible, whether it's:

- Reporting a bug
- Discussing the current state of the code
- Submitting a fix
- Proposing new features
- Translating the interface
- Sharing how your workshop works, so the software fits real shop floors
- Becoming a maintainer
- Telling us what we're doing wrong (no one here claims to know everything 😉)

You don't need to be a developer to help: feedback from people who run sheet metal, machining or mold shops is just as valuable as code.

## We develop with GitHub

We use GitHub to host code, track issues and feature requests, and accept pull requests.

- 💭 Questions and ideas → [Discussions](https://github.com/SMEWebify/WebErpMesv2/discussions)
- 🐛 Bugs and feature requests → [Issues](https://github.com/SMEWebify/WebErpMesv2/issues)
- 🗺️ Where the project is going → [ROADMAP.md](ROADMAP.md)
- 🌱 A first contribution → issues labelled [`good first issue`](https://github.com/SMEWebify/WebErpMesv2/labels/good%20first%20issue)

## Report bugs using GitHub's [issues](https://github.com/SMEWebify/WebErpMesv2/issues)

Report a bug by [opening a new issue](https://github.com/SMEWebify/WebErpMesv2/issues/new/choose) and picking the bug report template. A good report has:

- what you did (steps to reproduce, ideally from a fresh install),
- what you expected and what happened instead,
- your environment: version or commit, Docker or local install, PHP version, browser,
- screenshots or the relevant lines of `storage/logs/laravel.log`.

**Security issues must not be reported in public issues.** Please follow [SECURITY.md](SECURITY.md) to report them privately.

## Setting up a development environment

### Docker

```bash
git clone https://github.com/YOUR_USERNAME/WebErpMesv2.git
cd WebErpMesv2
docker compose up --build
```

Then open http://localhost:45060.

### Local install

Requirements: PHP 8.4+, Composer, Node.js, MySQL or PostgreSQL, Redis.

```bash
git clone https://github.com/YOUR_USERNAME/WebErpMesv2.git
cd WebErpMesv2
cp .env.example .env
composer install
npm install
php artisan key:generate
php artisan migrate --seed
npm run dev
php artisan serve
```

`php artisan wem:diagnostics` checks that your environment has everything it needs.

Before adding lines to a quote, set a default VAT rate (**Accounting → VAT**) and a default unit (**Methods → Units**).

More details in the wiki: [development setup](https://github.com/SMEWebify/WebErpMesv2/wiki/Installation-Steps-(for-dev)).

## Running the tests

```bash
php artisan test                      # backend (PHPUnit): tests/Feature, tests/Unit
php artisan test --filter TestName    # a single test
npm test                              # frontend (Vitest): resources/js/tests
```

Please make sure both suites pass before opening a pull request, and add tests for the behaviour you change, especially for business rules (stock, invoicing, accounting).

## Making changes

### Workflow

1. Fork the repository and create a branch from **`WEM-2.0`**, the development branch.
2. Make your changes, with tests.
3. Run the test suites.
4. Open a pull request against `WEM-2.0` and describe what you changed and why. Screenshots are welcome for anything visible.

Keep pull requests focused: one fix or one feature per pull request is much easier to review.

### Commit messages

We use [Conventional Commits](https://www.conventionalcommits.org/): `type(scope): summary`, in English or French.

```
feat(accounting): VAT matrix administration screen
fix(companies): date cast on vat_attestation_valid_until
docs(readme): add a contributor
```

Common types: `feat`, `fix`, `refactor`, `test`, `docs`, `i18n`, `chore`, `ci`.

### Things to know about the code base

ΣEM runs in production at real companies, so a few rules matter more than usual:

- **Migrations are additive.** Never drop or rename a column in a single step: add the new column, write to both, switch, and only then remove the old one.
- **No silent breaking changes** on URLs, accounting exports (FEC, invoices, Factur-X) or attached files.
- **Rich screens are React** components (`resources/js/components`) mounted from Blade views. Blade is for the layout and simple forms, Alpine.js for small interactions. Livewire and Vue.js have been removed: please don't reintroduce them.
- **CSS classes are Bootstrap 4** (`ml-1`, `badge-success`, `float-right`), because the AdminLTE 3 shell loads Bootstrap 4.6. Don't convert them to Bootstrap 5 in a regular pull request: the move to AdminLTE 4 / Bootstrap 5 is planned for v3.0.0 and will be done in one go.
- **Index tables** use the shared bricks in `resources/js/components/table/` (`DataTable`, `Pagination`, `StatusBadge`…) rather than a hand-written table.
- **Every user-facing text is translated.** Add your keys to `resources/lang/en` and `resources/lang/fr` at least; other languages (ar, es, vi, zh-CN) are welcome.
- **Never run `php artisan route:cache`**: it freezes the routes on a single locale and breaks localized URLs.

## Translations

Translation files live in `resources/lang/{locale}`. Fixing a wording, completing a missing key or adding a new language are all great first contributions, no PHP knowledge required.

## License of contributions

Since v2.2.0, the project is licensed under the **GNU LGPL v3 or later** (see [LICENSE](LICENSE), [COPYING](COPYING) and [NOTICE](NOTICE)).

Before your first pull request can be merged, you will be asked to sign the [Contributor License Agreement](CLA.md), once, by posting a comment on the pull request. You keep the copyright of your work: the agreement grants the maintainer a license on it, so that the project can keep evolving, and commits to keeping every accepted contribution available under an open source license.

The Nest2Prod name and logos are trademarks and are not covered by the code license; see [TRADEMARKS.md](TRADEMARKS.md).

## Thank you!

Every bug report, test, translation and pull request helps the workshops that rely on this software. Thanks for being part of it. 🏭
