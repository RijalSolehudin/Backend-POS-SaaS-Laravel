# P02-08 — Cancel/Void Minimum Flow

Status: **Done**

## Outcome

Draft order dapat dibatalkan dan completed order dapat divoid dengan authorization, reason, dan audit.

## Scope

- Cancel draft order.
- Void completed order.
- Void payment record linkage.
- Audit actor/reason/timestamp.

## Verification

- Finalized transaction tidak di-hard-delete.
- Void membutuhkan reason.
- Unauthorized cashier/admin boundary ditolak.

## Implementation Notes

- Draft order cancel tersedia di `POST /api/v1/pos/outlets/{outlet}/orders/{order}/cancel`.
- Cancel request wajib membawa `Idempotency-Key` dan `reason`.
- Completed order void tersedia untuk Tenant Admin owner di `POST /admin/tenants/{tenant}/sales/orders/{order}/void`.
- Void request wajib membawa `idempotency_key` dan `reason`.
- Void completed order mengubah order menjadi `voided`, mengubah payment `recorded` menjadi `voided`, dan menyimpan actor/reason/timestamp.
- Order/payment tidak di-hard-delete; history tetap ada untuk audit.
- Shift summary otomatis tidak menghitung order/payment voided.

## Evidence

- `php artisan test tests/Feature/Sales/CancelVoidFlowTest.php` — 3 passed, 41 assertions.
