# P07-07 — Printer Dispatch and Reprint

Status: **Done**

## Outcome

Kitchen chit/receipt print job dapat dikirim, gagal, retry, dan reprint secara auditable.

## Scope

- Buat `kitchen_print_jobs`.
- Tambahkan printer dispatch abstraction.
- Implement retry dan reprint dengan reason.

## Implementation Contract

- Ikuti [Phase 07 Implementation Contract](implementation-contract.md).
- Printer failure tidak membatalkan order/payment.
- Reprint selalu job baru.

## Verification

- Dispatch success/failure tests.
- Retry/reprint tests.
- Audit tests.
- `composer quality`.
