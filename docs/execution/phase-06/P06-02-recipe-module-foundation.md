# P06-02 — Recipe Module Foundation

Status: **Planned**

## Outcome

Module Recipe tersedia dengan recipe header tenant-scoped dan mapping awal ke catalog variant.

## Scope

- Buat module `Recipe`.
- Buat table `recipe_recipes`.
- Tambahkan field minimum: `tenant_id`, `name`, `sku`, `status`, `requires_recipe`.
- Buat web admin baseline untuk create/update/deactivate recipe.
- Tambahkan tenant isolation dan audit minimum.

## Implementation Contract

- Ikuti [Phase 06 Implementation Contract](implementation-contract.md).
- Jangan membuat deduction, procurement, atau Inventory movement pada P06-02.

## Verification

- Feature tests recipe CRUD/status.
- Tenant isolation tests.
- `composer quality`.
