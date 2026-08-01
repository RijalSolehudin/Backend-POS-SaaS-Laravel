# P09-04 — Offline Order Queue

Status: **Planned**

## Outcome

Offline draft/order completion request dari device dapat disinkronkan secara idempotent.

## Scope

- Buat offline order draft/event tables.
- Accept queued local order changes.
- Convert accepted offline completion into Sales order/payment.

## Implementation Contract

- Ikuti [Phase 09 Implementation Contract](implementation-contract.md).
- Server tetap membuat final order number dan receipt.
- Gateway payment offline ditolak.

## Verification

- Offline order replay tests.
- Duplicate prevention tests.
- Sales integration tests.
- `composer quality`.
