# Daksa ERP — SaaS foundation blueprint

Multi-tenant ERP foundation with **DB-per-tenant** and **subdomain** routing.

## Concepts

```
Platform Admin  → central domain, central DB (tenants & plans)
Tenant          → {slug}.base-host, own MySQL/SQLite database
Plan            → which modules are enabled (Starter / Business)
Role            → inside tenant DB, picks permissions from catalog
Permission      → seeded into each tenant DB from config
```

## Domains

| Host | App |
|------|-----|
| `erp.webyouneed.id` (central) | Platform console |
| `{slug}.erp.webyouneed.id` | Tenant workspace |

DNS: point `*.erp.webyouneed.id` (and apex) to the server. App config:

```
TENANCY_CENTRAL_DOMAINS=erp.webyouneed.id,www.erp.webyouneed.id
TENANCY_BASE_HOST=erp.webyouneed.id
TENANCY_DB_PREFIX=daksa_t_
```

## Databases

| Connection | Contents |
|------------|----------|
| `central` | tenants, plans, modules, subscriptions, platform users |
| `tenant` (runtime) | users, roles, permissions, pivots, cache/jobs |

Creating a tenant **automatically**:

1. Inserts central tenant row (`database` = `daksa_t_{slug}`)
2. `CREATE DATABASE` (or SQLite file)
3. Runs `database/migrations/tenant/*`
4. Seeds permission catalog + optional admin user

## Auth checks

```php
Gate::authorize('sales.orders.view');
```

Platform admins (central only) bypass permission checks.

## Demo accounts (after migrate --seed)

| Email | Password | Where |
|-------|----------|-------|
| `platform@daksa.test` | `password` | Central domain |
| `admin@demo.test` | `password` | `demo.{base_host}` |
| `sales@demo.test` | `password` | `demo.{base_host}` |

## Nginx (example)

```nginx
server {
    listen 80;
    listen 443 ssl;
    server_name erp.webyouneed.id *.erp.webyouneed.id;

    root /var/www/Daksa-Erp/public;
    index index.php;

    # ssl_certificate ... (wildcard recommended)

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
    }
}
```

## Artisan

```bash
php artisan migrate              # central
php artisan tenants:migrate      # all tenant DBs
php artisan tenants:migrate --tenant=demo
```

## Run locally

```bash
# hosts or use *.localhost (Chrome resolves *.localhost)
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8001
```

- Platform: http://localhost:8001/login  
- Tenant: http://demo.localhost:8001/login (set `TENANCY_BASE_HOST=localhost`)

## Next

- Billing / payment gateway
- Realtime events
- Sales / Inventory CRUD modules
