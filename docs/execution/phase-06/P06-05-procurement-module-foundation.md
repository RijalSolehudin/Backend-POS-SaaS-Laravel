# P06-05 — Procurement Module Foundation

Status: **Planned**

## Outcome

Module Procurement tersedia dengan supplier dan supplier item tenant-scoped.

## Scope

- Buat module `Procurement`.
- Buat `procurement_suppliers` dan `procurement_supplier_items`.
- Mapping supplier item ke Inventory item.
- Web admin baseline untuk supplier dan supplier item.

## Implementation Contract

- Ikuti [Phase 06 Implementation Contract](implementation-contract.md).
- Supplier item tidak boleh membuat stock movement.

## Verification

- Feature tests supplier CRUD/status.
- Supplier item mapping tests.
- Cross-tenant reference rejection.
- `composer quality`.
