# P02-05 — Idempotent Payment and Completion

Status: **Done**

## Outcome

Cashier dapat menyelesaikan order dengan full cash/manual payment secara idempotent.

## Scope

- Payment record foundation.
- Complete order with payment API.
- Exact-payment-only MVP policy.
- Order/payment total consistency.
- Retry and conflict behavior.

## Verification

- Retry payment tidak menggandakan payment.
- Amount/currency mismatch ditolak.
- Completed order immutable.

## Implementation Notes

- Completion dilakukan melalui `POST /api/v1/pos/outlets/{outlet}/orders/{order}/complete`.
- Request wajib membawa `Idempotency-Key`.
- Payment method Phase 02: `cash` dan `manual_non_cash`.
- Payment wajib full amount: `amount_minor` harus sama dengan `order.total_minor`.
- Payment currency wajib sama dengan order currency.
- Draft order kosong ditolak sebelum completion.
- Completion membuat satu `sales_payments` berstatus `recorded`, mengubah order menjadi `completed`, dan mengisi `completed_at`.
- Completion juga memperbarui running total shift: `gross_sales_minor`, dan `expected_cash_minor` untuk payment `cash`.

## Evidence

- `php artisan test tests/Feature/Sales/PaymentCompletionTest.php` — 4 passed, 38 assertions.
