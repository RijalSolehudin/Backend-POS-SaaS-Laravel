# P06-06 — Purchase Order Approval Lifecycle

Status: **Done**

## Outcome

Purchase order memiliki lifecycle dan approval yang auditable sebelum goods receipt.

## Scope

- Buat PO header dan line.
- Implement lifecycle draft/submitted/approved/ordered/cancelled.
- Tambahkan approval untuk submitted menuju approved.
- Reason wajib untuk cancel.

## Implementation Contract

- Ikuti [Phase 06 Implementation Contract](implementation-contract.md).
- PO tidak mengubah Inventory sampai goods receipt.
- PO line quantity dan price memakai base unit supplier item/inventory item.

## Verification

- PO lifecycle tests.
- Approval tests.
- Idempotency tests.
- `composer quality`.
