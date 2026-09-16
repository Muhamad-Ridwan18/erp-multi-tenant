# Daksa ERP — SaaS foundation blueprint

Multi-tenant ERP foundation with **DB-per-tenant** and **subdomain** routing.

Reference ERP: **AureusERP** at `D:\DEV\Daksa\erp` (plugins `purchases`, `inventories`, `sales`, `accounts`).

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

## Current implementation status (Wave 1)

| Module | Status | Notes |
|--------|--------|-------|
| Procurement | Wave 1 | Vendors; PO draft→sent→confirm; partial receive via stock ops; bill from received qty |
| Inventory | Wave 1 | Products (Aureus fields); warehouses/locations; stock quants; receipt/delivery/internal ops |
| Finance | Wave 1 (`accounts`) | Manual + SO/PO-linked invoices/bills; taxes; CoA; journals; post writes JE; payments |
| Sales | Wave 1 | Customers; quotation/order fields; confirm without stock out; deliver deducts stock |
| Settings | Done | Users, roles, permissions, categories, taxes UI |
| Masters | Wave 1 | UoM, currencies, payment terms, seeded on tenant provision |

## Out of Wave 1 (later)

- Manufacturing, barcode
- Routes/putaway/replenishment depth, lots/serials UI
- Full multi-currency FX, bank reconciliation, fiscal positions
- Credit notes / refunds UI polish
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
