# Phase 02: POS Core Vertical Slice

Status: **Not Started**

## Outcome

Cashier menyelesaikan transaksi dasar melalui Flutter dan API, sementara konfigurasi catalog serta ringkasan operasional tersedia melalui Web Admin.

## Scope

- Open shift.
- Browse simple sellable products.
- Create order dan manage simple items.
- Deterministic subtotal/total calculation.
- Cash/manual payment sesuai keputusan MVP.
- Complete order dan generate receipt data.
- Close shift dan view shift/daily summary.
- Product/category/price minimum dikelola melalui Web Admin.
- Daily sales dan shift summary minimum tersedia melalui Web Admin.
- Minimum cancel/void flow yang telah disetujui.

## Architecture Decisions Required

- Order lifecycle dan allowed transitions.
- Currency awal serta tax, service charge, dan discount calculation order.
- Payment lifecycle serta split/partial payment scope.
- Order number generation dan concurrency policy.
- Idempotency contract.
- Transaction boundary Sales dan Payments.
- Receipt requirements.

## Acceptance Scenario

```text
Cashier login
  -> pilih outlet
  -> buka shift
  -> pilih produk
  -> buat order
  -> terima pembayaran
  -> terbitkan receipt
  -> tutup shift
  -> cocokkan ringkasan harian
```

## Acceptance Criteria

- Retry request tidak membuat order atau payment ganda.
- Totals konsisten antara response order, payment, receipt, dan reporting.
- Closed/invalid shift tidak dapat menerima transaksi baru.
- Harga master yang berubah tidak mengubah order yang telah difinalkan.
- Seluruh operasi ditolak untuk tenant/outlet yang tidak sah.

## Out of Scope

- Offline sync penuh.
- Automated gateway payment.
- Variants/modifiers kompleks.
- Stock deduction, table, dan kitchen.
