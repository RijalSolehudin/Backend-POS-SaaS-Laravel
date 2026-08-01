# P06-04 — Sales Stock Deduction Integration

Status: **Planned**

## Outcome

Order completed mengurangi Inventory secara idempotent berdasarkan active recipe version.

## Scope

- Buat `recipe_sales_deductions`.
- Integrasikan Sales complete order dengan recipe deduction.
- Deduction menghasilkan Inventory movement `sales_deduction`.
- Simpan snapshot recipe version, ingredient usage, cost, order id, dan order item id.

## Implementation Contract

- Ikuti [Phase 06 Implementation Contract](implementation-contract.md).
- Deduction terjadi sebelum payment/receipt final.
- Insufficient stock menolak completion.
- Retry complete order tidak membuat deduction ganda.

## Verification

- Sales completion deduction tests.
- Insufficient stock failure test.
- Idempotency replay/conflict tests.
- Receipt/order historical snapshot regression.
- `composer quality`.
