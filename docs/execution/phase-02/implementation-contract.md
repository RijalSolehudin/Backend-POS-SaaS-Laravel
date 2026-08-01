# Phase 02 Implementation Contract

Dokumen ini mengunci detail teknis Phase 02 POS Core agar work package P02-01 sampai P02-09 dapat dipahami tanpa membuat ulang keputusan lifecycle, calculation, idempotency, atau receipt.

## Module Ownership

- Module `Sales` memiliki shift, order, order item, payment record, receipt snapshot, idempotency record, order number counter, dan reporting snapshot.
- Catalog tetap menjadi sumber product aktif untuk POS read model.
- Tenancy tetap menjadi sumber tenant/outlet/device/user POS context.
- Sales boleh membaca Catalog melalui application boundary, bukan menghitung ulang dari tabel internal Catalog untuk transaksi historis.

## Tables

Gunakan table berikut sebagai baseline Phase 02:

- `sales_shifts`
- `sales_order_number_counters`
- `sales_orders`
- `sales_order_items`
- `sales_payments`
- `sales_receipts`
- `sales_idempotency_records`

Semua table wajib tenant-scoped. Resource yang outlet-scoped juga wajib menyimpan `outlet_id`.

## Lifecycle

| Domain | Values |
|---|---|
| Shift | `open`, `closed`, `voided` |
| Order | `draft`, `completed`, `cancelled`, `voided` |
| Payment | `recorded`, `voided` |
| Payment method | `cash`, `manual_non_cash` |

Rules:

- Satu cashier hanya boleh memiliki satu open shift per outlet.
- Order hanya dibuat saat shift `open`.
- Draft order boleh add/update/remove item.
- Completed order immutable untuk item, payment, dan receipt snapshot.
- Cancel hanya untuk draft order.
- Void hanya untuk completed order dan disempurnakan di Phase 03.
- Reopen shift/order tidak masuk Phase 02.

## Calculation

- Currency hanya currency tenant.
- Money disimpan sebagai signed integer minor unit.
- Quantity request diterima sebagai decimal string dengan presisi maksimal 3 digit.
- Formula:

```text
line_subtotal_minor = unit_price_minor * quantity
subtotal_minor = sum(line_subtotal_minor)
discount_minor = 0
service_charge_minor = 0
tax_minor = 0
total_minor = subtotal_minor
```

- Jika calculation quantity pecahan menghasilkan minor unit non-integer, gunakan half-up rounding ke minor unit.
- Payment amount harus sama dengan `order.total_minor`.
- Partial payment, split payment, tax, discount, promotion, dan gateway tidak masuk Phase 02.

## Idempotency

Gunakan `sales_idempotency_records`.

Scope unique:

- `tenant_id`
- `outlet_id`
- `user_id`
- `action`
- `idempotency_key`

Mutation yang wajib idempotent:

- create draft order;
- add/update/remove order item;
- cancel draft order;
- complete order with payment;
- close shift.

Replay dengan request hash sama mengembalikan resource yang sama. Replay dengan hash berbeda mengembalikan `IDEMPOTENCY_CONFLICT`.

## Business Numbering

- Order number bukan primary key.
- Format: `{OUTLET_CODE}-{YYYYMMDD}-{SEQUENCE_4}`.
- Business date dihitung dari timezone tenant.
- Sequence scoped tenant + outlet + business date.
- Sequence wajib diambil dalam database transaction dengan row lock.

## Receipt

- Receipt adalah snapshot dari completed order dan recorded payment.
- Receipt tidak membaca catalog live.
- Receipt minimal berisi tenant/outlet snapshot, order number, completed time, cashier snapshot, items, subtotal, total, payment method, amount, dan currency.
- Printing adapter tidak masuk Phase 02.

## Testing Baseline

Setiap work package Phase 02 wajib memiliki:

- feature tests untuk lifecycle happy path;
- validation/failure path;
- tenant/outlet/device isolation;
- idempotency replay dan conflict untuk mutation kritis;
- total consistency test;
- `composer quality`.

Jalankan `npm run build` bila mengubah frontend asset.

## Stop Rule

Berhenti jika implementasi membutuhkan tax, discount, service charge, split/partial payment, payment gateway, inventory deduction, table/dining mode, reopening order/shift, atau refund policy.
