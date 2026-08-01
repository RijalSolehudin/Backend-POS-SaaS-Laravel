# P02-04 — Draft Order and Item Management

Status: **Planned**

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
