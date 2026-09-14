# Daksa ERP (SaaS foundation)

New multi-tenant ERP codebase. Aureus at `../erp` is **reference only**.

## This phase

- Tenants + plans + modules
- Custom RBAC (permission catalog, roles per tenant)
- Minimal UI: login, tenant role checklist, platform tenant list

See [docs/BLUEPRINT.md](docs/BLUEPRINT.md).

## Quick start

```bash
cd d:\DEV\Daksa\daksa-erp
php artisan migrate:fresh --seed
php artisan serve --host=127.0.0.1 --port=8001
```

Open http://127.0.0.1:8001/login

| Account | Password |
|---------|----------|
| `platform@daksa.test` | `password` |
| `admin@demo.test` | `password` |
| `sales@demo.test` | `password` |

Try `sales@demo.test` — they can view dashboard permissions but cannot manage roles.
