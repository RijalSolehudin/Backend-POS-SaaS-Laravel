# P07-03 — Table Session Lifecycle

Status: **Planned**

## Outcome

Table occupancy dapat dibuka, dipindah, digabung, dan ditutup tanpa merusak Sales order.

## Scope

- Buat `dining_table_sessions` dan `dining_table_session_orders`.
- Implement open, transfer, merge, close, cancel.
- Link table session ke Sales order.

## Implementation Contract

- Ikuti [Phase 07 Implementation Contract](implementation-contract.md).
- Satu table hanya punya satu open session.
- Close session mensyaratkan linked order selesai/cancelled/voided.

## Verification

- Session lifecycle tests.
- Merge/transfer tests.
- Sales state regression tests.
- `composer quality`.
