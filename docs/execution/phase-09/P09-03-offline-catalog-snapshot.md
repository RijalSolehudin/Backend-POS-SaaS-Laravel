# P09-03 — Offline Catalog Snapshot

Status: **Planned**

## Outcome

Device POS dapat mengambil catalog snapshot untuk offline read.

## Scope

- Buat catalog snapshot/version response.
- Tambahkan sync outbox event untuk catalog changes.
- Client receives cache retention policy.

## Implementation Contract

- Ikuti [Phase 09 Implementation Contract](implementation-contract.md).
- Snapshot read-only; offline catalog mutation tidak diizinkan.

## Verification

- Catalog snapshot tests.
- Outbox event tests.
- Tenant/outlet isolation tests.
- `composer quality`.
