# P02-04 — Draft Order and Item Management

Status: **Done**

## Outcome

Cashier dapat membuat draft order dan mengelola item dari catalog aktif dengan snapshot harga deterministic.

## Scope

- Create draft order.
- Add/update/remove item sebelum completion.
- Snapshot product name, SKU, unit price, currency, and category.
- Deterministic subtotal/total calculation.

## Verification

- Product inactive/unavailable ditolak.
- Harga master berubah tidak mengubah draft/final snapshot yang sudah dibuat.
- Retry create draft tidak menghasilkan order ganda.

## Implementation Notes

- Draft order dibuat melalui endpoint POS dengan `Idempotency-Key`.
- Order number mengikuti format ADR-037: `{OUTLET_CODE}-{YYYYMMDD}-{SEQUENCE_4}`.
- Item hanya dapat diubah selama order berstatus `draft`.
- Item menyimpan snapshot `product_sku`, `product_name`, `product_category_id`, `product_category_name`, `unit_price_minor`, dan `currency`.
- Subtotal dan total dihitung ulang deterministik dari line item; tax/discount/service charge tetap `0` sesuai Phase 02 MVP policy.

## Evidence

- `php artisan test tests/Feature/Sales/DraftOrderItemManagementTest.php` — 4 passed, 41 assertions.
