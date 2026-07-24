# Payments Module

## Owns

- Payment method configuration.
- Payment attempt/record dan payment status.
- Refund dan payment gateway transaction pada phase lanjutan.
- Idempotency untuk operasi pembayaran.

## MVP Use Cases

- Record cash payment.
- Record manual non-cash payment bila disetujui.
- Determine paid/unpaid state bersama kontrak Sales.
- Reverse/void payment sesuai policy yang disetujui.

## Invariants

- Payment amount menggunakan signed integer minor units dan currency yang konsisten dengan transaksi.
- Retry tidak menghasilkan pembayaran ganda.
- Payment tidak di-hard-delete.
- Refund/reversal mereferensikan payment asal dan memiliki audit metadata.

## Open Decisions

- Partial dan split payment dalam MVP.
- Refund parsial.
- Payment gateway provider dan callback model.
- Batas transaksi atomik antara Sales dan Payments.
