# P07-06 — Realtime KDS Updates

Status: **Implemented — Pending MariaDB Verification**

## Outcome

KDS mendapat update realtime yang aman tenant/outlet dan dapat pulih lewat API snapshot.

## Scope

- Tambahkan broadcast events untuk ticket created/updated.
- Tambahkan authorization channel tenant/outlet.
- Tambahkan KDS snapshot endpoint.

## Implementation Contract

- Ikuti [Phase 07 Implementation Contract](implementation-contract.md).
- Realtime tidak menjadi source of truth.

## Verification

- Channel authorization tests.
- Snapshot fallback tests.
- Event payload redaction tests.
- `composer quality`.
