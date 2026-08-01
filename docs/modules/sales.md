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

## Implemented API Surface

- `GET /api/v1/pos/outlets/{outlet}/shifts/current`
- `POST /api/v1/pos/outlets/{outlet}/shifts/open`
- `POST /api/v1/pos/outlets/{outlet}/shifts/{shift}/close`
- `GET /api/v1/pos/outlets/{outlet}/shifts/{shift}/summary`
- `POST /api/v1/pos/outlets/{outlet}/orders`
- `GET /api/v1/pos/outlets/{outlet}/orders/{order}`
- `POST /api/v1/pos/outlets/{outlet}/orders/{order}/items`
- `PUT /api/v1/pos/outlets/{outlet}/orders/{order}/items/{item}`
- `DELETE /api/v1/pos/outlets/{outlet}/orders/{order}/items/{item}`
- `POST /api/v1/pos/outlets/{outlet}/orders/{order}/complete`
- `GET /api/v1/pos/outlets/{outlet}/orders/{order}/receipt`
- `GET /admin/tenants/{tenant}/sales/daily`

## Invariants

- Mutation order tervalidasi terhadap tenant, outlet, user, dan active shift.
- Draft order memerlukan shift `open` milik user/outlet yang sama.
- Create draft order idempotent menggunakan `Idempotency-Key`.
- Order item menyimpan snapshot SKU, nama, category, harga, dan currency dari catalog outlet aktif.
- Complete order dengan payment bersifat idempotent dan exact-payment-only.
- Completed order immutable untuk perubahan item.
- Completion menghasilkan receipt snapshot immutable.
- Close shift menyimpan summary cash dan gross sales hasil rekonsiliasi.
- Lifecycle transition hanya melalui use case yang sah.
- Critical create/mutation operation memiliki idempotency policy.
- Finalized financial history tidak di-hard-delete.
- Monetary snapshot final menggunakan signed integer minor units dan rounding half-up.
- Order total dapat direkonsiliasi dari snapshot komponennya.

## Phase 02 Policy

- Status order MVP: `draft`, `completed`, `cancelled`, `voided`.
- Status shift MVP: `open`, `closed`, `voided`.
- Currency mengikuti tenant, dengan IDR sebagai target awal.
- Tax, service charge, discount, hold/reopen, dine-in/takeaway, dan discrepancy approval berada di luar MVP Phase 02.
