# P09-05 — Conflict Detection and Resolution

Status: **Done**

## Outcome

Conflict offline dapat dideteksi, dicatat, dan diselesaikan tanpa silent overwrite.

## Scope

- Buat `sync_conflicts`.
- Detect stale catalog, duplicate payload mismatch, insufficient stock, and invalid state.
- Operator resolution dengan actor/reason.

## Delivered

- `sync_conflicts` menyimpan conflict type, payload evidence, actor resolution, reason, dan timestamp.
- Payload hash mismatch pada scope sync yang sama menghasilkan conflict terbuka.
- Sequence stale dan stale catalog/stock signal menghasilkan conflict, bukan silent overwrite.
- `ResolveSyncConflict` menyelesaikan/dismiss conflict dengan guard tenant operator.

## Implementation Contract

- Ikuti [Phase 09 Implementation Contract](implementation-contract.md).
- Financial conflict tidak auto-merge.

## Verification

- Conflict scenario tests.
- Resolution audit tests.
- Recovery/report tests.
- `composer quality`.
