# Era+

Multi-tenant SaaS for Erasmus+ programme operations. Organizations are tenants, projects are sub-tenants inside an organization, finance and mobility data are project-scoped.

> **Status:** active development. Spec, conventions and feature notes are the source of truth — see [`docs/`](docs/).

## What it does

- **Organizations** register, invite members, define org-scoped roles.
- **Projects** live inside an organization with their own members, roles and data.
- **Finance** — project-scoped entries with Excel export.
- **Mobility** — project-scoped participants with a per-org participant role catalog.
- **Billing** — Stripe subscriptions via Laravel Cashier, scoped per organization.
- **Auth** — OTP-only email login, passkeys, Google/Microsoft/Apple social sign-in.

## Architecture at a glance

Three Filament panels, one `User`, hard tenant boundaries:

| Panel | Host / path | Tenant | For |
|-------|-------------|--------|-----|
| `admin` | `admin.{host}` | — | Platform staff (provisioned out-of-band) |
| `app` | `app.{host}/{org}` | `Organization` | Org members, cross-project surfaces |
| `project` | `app.{host}/{org}/{project}` | `Project` | Finance, mobility, project members |

Tenant-owned models use `BelongsToOrganization`; project-owned models additionally use `BelongsToProject`. Both apply global scopes that read the active panel's tenant and auto-assign foreign keys. Permissions are partitioned by scope via `spatie/laravel-permission` (team = `organization_id` or `project_id`).

Full picture: [`docs/SPEC.md`](docs/SPEC.md) and [`docs/features/multi-panel.md`](docs/features/multi-panel.md).

## Stack

- PHP 8.4 · Laravel 12 · Filament 5 · Livewire 4 · Tailwind 4
- PHPUnit 12 · Pint · Larastan · Laravel Dusk
- SQLite by default (swap via `DB_CONNECTION`); Redis-friendly; GCS for media
- Served locally by [Laravel Herd](https://herd.laravel.com) at `era-plus.test`

## Local setup

Requires PHP 8.4, Composer, Node, and Herd (or any local webserver).

```bash
composer setup       # install, copy .env, key:generate, migrate, npm install, npm run build
composer run dev     # php artisan serve + queue + pail + vite + stripe listen (concurrently)
```

Then open the app at the Herd URL (`https://era-plus.test`). Admin panel: `https://admin.era-plus.test`.

Fill in `.env` as needed — Stripe, Google (OAuth + Cloud Storage), Resend, Anthropic, Nightwatch. See [`.env.example`](.env.example).

## Common commands

```bash
php artisan test --compact                       # run tests
php artisan test --compact --filter=TestName     # single test
composer run analyse                             # phpstan / larastan
vendor/bin/pint --dirty --format agent           # format changed files
php artisan dusk                                 # browser tests
```

## Project layout

```
app/
  Filament/{Admin,Organization,Project}/   # panel-scoped resources & pages
  Livewire/                                # cross-panel Livewire components
  Models/{Organization,Project}/           # tenant-owned models
  Services/                                # domain rules (policies delegate here)
  Support/                                 # string/helper utilities
docs/
  SPEC.md                                  # the system in one page
  conventions.md                           # how we build features — read before overriding
  features/                                # feature design notes
```

## Design notes

Non-obvious rules that outlive any single commit:

- Users are treated as email-verified by default; verification is passive.
- Every subscription belongs to exactly one organization.
- Login is OTP-only — no password reset flow, no "set your password" invite link.
- Totals (finance, counts) are always derived, never stored.
- Deleting a user must not remove historical project data.

More in [`docs/conventions.md`](docs/conventions.md).

## Contributing

Read [`CLAUDE.md`](CLAUDE.md) and [`docs/conventions.md`](docs/conventions.md) first — they encode the hard-won preferences (Filament Resource vs Page, pivot handling, policy structure, naming). Run Pint and the test suite before opening a PR.
