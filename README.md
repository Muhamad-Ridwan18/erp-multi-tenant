# Daksa ERP (SaaS foundation)

Multi-tenant ERP with **DB-per-tenant** and **subdomain** routing.

## This phase

- Central DB: tenants, plans, modules, subscriptions, platform users
- Each tenant gets its own database (`daksa_t_{slug}`) on create
- Subdomain: `{slug}.{TENANCY_BASE_HOST}`
- Custom RBAC inside each tenant DB

See [docs/BLUEPRINT.md](docs/BLUEPRINT.md).

## Quick start

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8001
```

| URL | Account |
|-----|---------|
| http://localhost:8001/login | `platform@daksa.test` / `password` |
| http://demo.localhost:8001/login | `admin@demo.test` / `password` |

Set in `.env`:

```
TENANCY_CENTRAL_DOMAINS=localhost,127.0.0.1
TENANCY_BASE_HOST=localhost
```

## Production notes

1. DNS wildcard `*.erp.webyouneed.id` → server IP (you configure)
2. Nginx `server_name erp.webyouneed.id *.erp.webyouneed.id`
3. Prefer wildcard SSL
4. `DB_DRIVER=mysql`, central DB `daksa_erp`
5. App user needs `CREATE DATABASE` privilege (or equivalent) for provisioning
