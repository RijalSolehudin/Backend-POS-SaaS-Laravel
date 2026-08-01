# P02-06 — Receipt Snapshot

Status: **Done**

## Outcome

Completed order menghasilkan receipt data snapshot yang konsisten dengan order dan payment.

## Scope

- Receipt composition from completed order/payment.
- Receipt API endpoint.
- Minimum receipt fields per ADR-037.

## Verification

- Receipt total sama dengan order/payment.
- Receipt tidak berubah setelah catalog master berubah.

## Implementation Notes

- Receipt dibuat dalam transaction completion yang sama dengan order/payment.
- Receipt disimpan pada `sales_receipts` dengan `snapshot` JSON immutable.
- Endpoint receipt: `GET /api/v1/pos/outlets/{outlet}/orders/{order}/receipt`.
- Snapshot minimal mencakup tenant/outlet, order number, completed time, cashier snapshot, item snapshot, totals, payment method, payment amount, dan currency.
- Receipt number menggunakan order number pada Phase 02.

## Evidence

- `php artisan test tests/Feature/Sales/ReceiptSnapshotTest.php` — 2 passed, 36 assertions.
