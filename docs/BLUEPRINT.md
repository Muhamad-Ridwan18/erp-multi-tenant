# Daksa ERP — SaaS foundation blueprint

Multi-tenant ERP foundation with **DB-per-tenant** and **subdomain** routing.

Reference ERP: **AureusERP** at `D:\DEV\Daksa\erp` (plugins `purchases`, `inventories`, `sales`, `accounts`, `manufacturing`, `barcode`).

## Four mandatory business modules

| Daksa module | Aureus reference | Core entities (Wave 1) |
|--------------|------------------|------------------------|
| **Procurement** | `plugins/webkul/purchases` | Vendors, RFQ/PO, partial receipt → stock, bill |
| **Inventory** | `plugins/webkul/inventories` | Products, warehouses/locations, stock quants, receipt/delivery ops |
| **Finance** | `plugins/webkul/accounts` | Manual + linked invoices/bills, taxes, journals, CoA, payments + JE |
| **Sales** | `plugins/webkul/sales` | Customers, quotations/orders, delivery → stock out, invoice |

Cross-module flows:

```
Sales order confirm  → delivery / stock out
Purchase order confirm → goods receipt / stock in (partial OK)
Sales invoicing      → customer invoice (finance) → post JE → pay
PO billing           → vendor bill (finance) → post JE → pay
Manufacturing        → BOM → MO confirm/produce → consume components + receive FG
```

## Admin UI shell

Admin layout uses **[Tabler](https://github.com/tabler/tabler)** (`@tabler/core` via Vite): vertical sidebar, page header, Bootstrap 5 utilities. Document forms stay Blade + Tom Select (no Filament). Dark mode via `data-bs-theme` + moon/sun toggle.

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
- Product form: type, barcode, category, UoM, cost, sales price

## Current implementation status

| Module | Status | Notes |
|--------|--------|-------|
| Procurement | Wave 1 | Vendors; PO draft→sent→confirm; partial receive via stock ops; bill from received qty |
| Inventory | Wave 1+2 | Products; warehouses/locations; stock quants; receipt/delivery/internal; scrap; order points; barcode scan |
| Manufacturing | Wave 2 | Work centers; BOM + lines; MO draft→confirm→produce (consume components, receive FG) |
| Finance | Wave 1+2 (`accounts`) | Manual + SO/PO-linked invoices/bills; taxes; CoA; journals; post JE; payments; credit notes / vendor refunds |
| Sales | Wave 1 | Customers; quotation/order fields; confirm without stock out; deliver deducts stock |
| Settings | Done | Users, roles, permissions, categories, taxes UI |
| Masters | Wave 1+2 | UoM, currencies, payment terms, scrap/production locations, default work center |

## Later (Wave 3+)

- Lots/serials UI, routes/putaway sophistication
- Full multi-currency FX, bank reconciliation, fiscal positions
- Work orders / routing depth, unbuild orders
- Vendor/customer portal

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

## Demo accounts (after migrate --seed)

| Email | Password | Where |
|-------|----------|-------|
| `platform@daksa.test` | `password` | Central domain |
| `admin@demo.test` | `password` | `demo.{base_host}` |
| `sales@demo.test` | `password` | `demo.{base_host}` |

## Artisan

```bash
php artisan migrate              # central
php artisan tenants:migrate      # all tenant DBs
php artisan tenants:migrate --tenant=demo
```
