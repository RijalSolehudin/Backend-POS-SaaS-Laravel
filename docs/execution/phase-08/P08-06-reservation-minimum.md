# P08-06 — Reservation Minimum

Status: **Done**

## Outcome

Outlet dapat mencatat reservation minimum dan menghubungkannya ke table session saat customer datang.

## Scope

- Buat `reservations`.
- Lifecycle pending/confirmed/seated/cancelled/no_show.
- Link reservation ke table session.

## Implementation Contract

- Ikuti [Phase 08 Implementation Contract](implementation-contract.md).
- Customer identity optional dan data minimum.

## Verification

- Reservation lifecycle tests.
- Table session link tests.
- Privacy/redaction tests.
- `composer quality`.
