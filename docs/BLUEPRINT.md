# Daksa ERP — SaaS foundation blueprint

Multi-tenant ERP foundation (separate from Aureus reference). This phase covers **tenants**, **plans/modules**, and **custom RBAC** only.

## Concepts

```
Platform Admin  → manages tenants & plans
Tenant          → company that rents the ERP
Plan            → which modules are enabled (Starter / Business)
Role            → per-tenant, picks permissions from catalog
Permission      → global catalog (sales.orders.view, …)
```

- **Plan** = what the company paid for (modules on/off)
- **Role** = who inside the company may do what (within enabled modules)

## Tables

| Table | Purpose |
|-------|---------|
| `tenants` | Renting companies |
| `plans` / `modules` / `plan_module` | Subscription packages |
| `subscriptions` | Tenant ↔ plan |
| `permissions` | Global catalog |
| `roles` | Tenant-scoped roles |
| `role_permission` / `role_user` | Assignments |
| `users.tenant_id` | null = platform staff |

## Adding a permission

1. Add entry in [`config/permissions.php`](../config/permissions.php)
2. Run `php artisan db:seed --class=PermissionCatalogSeeder`
3. Tenant admins can assign it only if the module is in their plan

Naming: `{module}.{resource}.{action}`  
Example: `sales.orders.confirm`

## Auth checks

```php
Gate::authorize('sales.orders.view');
// or
$request->user()->can('sales.orders.view');
```

Platform admins bypass all permission checks.

## Demo accounts (after migrate --seed)

| Email | Password | Role |
|-------|----------|------|
| `platform@daksa.test` | `password` | Platform admin |
| `admin@demo.test` | `password` | Tenant Admin (all plan permissions) |
| `sales@demo.test` | `password` | Sales (limited) |

## Run locally

```bash
cd d:\DEV\Daksa\daksa-erp
php artisan migrate:fresh --seed
php artisan serve --port=8001
```

Open http://127.0.0.1:8001/login

## Next (not in this phase)

- Billing / payment gateway
- Realtime events
- Sales / Inventory CRUD modules
- Subdomain or path-based tenant routing
