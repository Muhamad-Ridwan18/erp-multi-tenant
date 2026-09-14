# Daksa ERP — SaaS foundation blueprint

Multi-tenant ERP foundation with **DB-per-tenant** and **subdomain** routing.

Reference ERP: **AureusERP** at `D:\DEV\Daksa\erp` (plugins `purchases`, `inventories`, `sales`, `accounts`/`invoices`).

## Four mandatory business modules

| Daksa module | Aureus reference | Core entities (target) |
|--------------|------------------|----------------------|
| **Procurement** | `plugins/webkul/purchases` | Vendors, PO/RFQ, goods receipt → stock |
| **Inventory** | `plugins/webkul/inventories` | Products, stock moves, warehouses (later) |
| **Finance** | `plugins/webkul/accounts` + `invoices` | Customer invoices, vendor bills, payments |
| **Sales** | `plugins/webkul/sales` | Customers, quotations, sales orders → delivery/stock |

Cross-module flows (from Aureus):

```
Sales order confirm  → delivery / stock out
Purchase order confirm → goods receipt / stock in
Sales invoicing      → customer invoice (finance)
PO billing           → vendor bill (finance)
```

## Admin UI shell

Admin layout uses **[Tabler](https://github.com/tabler/tabler)** (`@tabler/core` via Vite): vertical sidebar, page header, Bootstrap 5 utilities. Document forms stay Blade + Tom Select (no Filament).

## Form UX (vs Aureus reference)

Document forms mimic Aureus patterns:

- Searchable selects (Tom Select)
- Table-style order lines (product / qty / price / disc% / tax% / amount)
- Auto-fill unit price on product pick
- Live line amounts + untaxed / discount / tax / total
- Stock warning when qty exceeds on-hand (sales)
- Tabs: Order lines / Other info / Terms
- Progress stepper on document show pages
- Inline create customer/vendor from SO/PO forms
- Product form: 2/3 + 1/3 layout (identity vs pricing)


## Current implementation status

| Module | Status | Notes |
|--------|--------|-------|
| Procurement | Partial | Vendors + PO draft→confirm→receive (stock in) |
| Inventory | Partial | Products + stock adjust + sale/purchase movements |
| Finance | Partial | Invoices from SO, bills from PO, payments |
| Sales | Partial | Customers, orders draft→confirm |
| Settings | Done | Users, roles, permissions |

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
| `tenant` (runtime) | users, roles, permissions, business tables, cache/jobs |

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

Permission pattern: `{module}.{resource}.{action}` (e.g. `procurement.orders.confirm`).

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
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8001
```

- Platform: http://localhost:8001/login  
- Tenant: http://demo.localhost:8001/login (set `TENANCY_BASE_HOST=localhost`)

## Suggested build order

1. ~~**Procurement** — vendors + PO draft→confirm→receipt (stock in)~~ ✅
2. ~~**Finance** — invoice from confirmed SO; bill from confirmed PO~~ ✅
3. Perdalam **Inventory** — warehouses, transfer, richer stock moves
4. Perdalam **Sales** — quotation, delivery

## Later

- Billing / payment gateway (SaaS subscription)
- Realtime events
- Aureus-level inventory (routes, lots, replenishment)
