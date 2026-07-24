# Sales Module

## Owns

- Cashier shift dan cash drawer activity dasar.
- Order dan order item lifecycle.
- Business order number.
- Discount application pada transaksi, subject to ownership decision.
- Receipt data composition, tidak termasuk device-specific printing adapter.

## MVP Use Cases

- Open shift.
- Create order.
- Add/update/remove order item sebelum finalization.
- Calculate order totals menggunakan aturan yang disetujui.
- Confirm/cancel/void/complete order sesuai lifecycle final.
- Close shift dan menghasilkan reconciliation summary.

## Invariants

- Mutation order tervalidasi terhadap tenant, outlet, user, dan active shift.
- Order menyimpan snapshot komersial item.
- Lifecycle transition hanya melalui use case yang sah.
- Critical create/mutation operation memiliki idempotency policy.
- Finalized financial history tidak di-hard-delete.
- Monetary snapshot final menggunakan signed integer minor units dan rounding half-up.
- Order total dapat direkonsiliasi dari snapshot komponennya.

## Open Decisions

- Status lifecycle final.
- Currency awal serta tax, service charge, discount calculation order, dan allocation rule.
- Hold/reopen behavior.
- Dine-in/takeaway dalam MVP.
- Cash drawer counting dan discrepancy approval.
