# ADR-037: POS Core MVP Policy

- Status: Accepted
- Date: 2026-08-01

## Context

Phase 02 membangun vertical slice transaksi POS: cashier membuka shift, membuat order, menerima payment, menerbitkan receipt, menutup shift, dan melihat ringkasan. Roadmap Phase 02 mencatat keputusan wajib untuk lifecycle order/payment, idempotency, numbering, calculation, transaction boundary, dan receipt.

Keputusan ini perlu disetujui sebelum implementasi model Sales/Payments dimulai karena berdampak pada schema, invariant finansial, API contract, dan test matrix.

## Decision

### Currency and Calculation

- Phase 02 hanya mendukung currency tenant saat provisioning, dengan target awal `IDR`.
- Monetary final disimpan dan dikirim sebagai signed integer minor units.
- Quantity order item memakai decimal string input, tetapi Phase 02 UI/API hanya menerima quantity positif dengan presisi maksimal 3 digit desimal.
- Phase 02 tidak menerapkan tax, service charge, discount, promotion, atau rounding allocation.
- Order total:

```text
line_subtotal_minor = unit_price_minor * quantity
subtotal_minor = sum(line_subtotal_minor)
discount_minor = 0
service_charge_minor = 0
tax_minor = 0
total_minor = subtotal_minor
```

Jika quantity pecahan membuat hasil bukan integer minor unit, calculation dibulatkan half-up ke minor unit.

### Shift Lifecycle

- Shift status: `open`, `closed`, `voided`.
- Satu cashier hanya boleh mempunyai satu open shift per outlet.
- Order hanya dapat dibuat pada shift `open`.
- Close shift menghasilkan snapshot total order/payment yang dapat direkonsiliasi.
- Reopen shift tidak tersedia pada Phase 02.

### Order Lifecycle

- Order status: `draft`, `completed`, `cancelled`, `voided`.
- `draft`: order dapat menerima add/update/remove item.
- `completed`: immutable financial history; item/payment/receipt snapshot tidak berubah.
- `cancelled`: draft dibatalkan sebelum payment/complete.
- `voided`: completed order dibatalkan secara administratif dengan actor, reason, dan timestamp.
- Phase 02 tidak menyediakan hold/reopen/dine-in table lifecycle.

### Payment Lifecycle

- Payment method Phase 02: `cash` dan `manual_non_cash`.
- Payment status: `recorded`, `voided`.
- Payment amount harus sama dengan order `total_minor`; partial dan split payment tidak tersedia.
- Payment currency harus sama dengan order currency.
- Gateway/callback tidak tersedia pada Phase 02.
- Payment void hanya untuk supporting void completed order dan wajib menyimpan actor/reason.

### Transaction Boundary

- Sales memiliki shift, order, order item, receipt composition, dan reporting snapshot.
- Payments memiliki payment record dan payment idempotency.
- Complete order dengan payment dijalankan melalui application action yang membuka satu database transaction dan memanggil boundary Payments secara eksplisit.
- Payment tidak boleh dibuat untuk order di tenant/outlet/shift yang tidak tervalidasi.

### Idempotency

- Mutasi kritis berikut wajib menerima `Idempotency-Key`:
  - create draft order;
  - complete order with payment;
  - cancel/void order;
  - close shift.
- Idempotency key scoped ke tenant, outlet, user, route/action, dan request fingerprint.
- Replay dengan fingerprint sama mengembalikan hasil pertama.
- Replay dengan fingerprint berbeda menghasilkan conflict `409`.
- Idempotency record disimpan minimal 24 jam.

### Business Numbering

- Order number adalah nomor bisnis terpisah dari ULID.
- Format awal:

```text
{OUTLET_CODE}-{YYYYMMDD}-{SEQUENCE_4}
```

- Tanggal bisnis dihitung dari timezone tenant/outlet.
- Sequence scoped tenant + outlet + business date.
- Sequence diambil dalam database transaction dengan row lock untuk mencegah duplikasi.

### Receipt

- Receipt data adalah snapshot dari order completed dan payment recorded.
- Receipt minimal berisi tenant/outlet name, order number, completed time, cashier id/name snapshot, item name/sku/qty/unit price/line subtotal, subtotal, total, payment method, payment amount, and currency.
- Device-specific printing adapter tidak termasuk Phase 02.

## Consequences

- Phase 02 fokus pada alur transaksi dasar tanpa pajak, diskon, split payment, gateway, atau inventory deduction.
- Schema dan API dapat tetap berkembang secara additive untuk tax/discount/payment gateway pada phase berikutnya.
- Idempotency dan numbering harus diuji sebagai bagian dari happy path dan retry path.

## Approval

Product owner menyetujui keputusan ini setelah diskusi lifecycle order dan shift pada 2026-08-01.
