# P08-07 — Promotion Discount MVP

Status: **Done**

## Outcome

Order dapat memakai satu discount fixed/percentage yang dihitung server-side dan tersnapshot.

## Scope

- Buat promotion rule.
- Apply one promotion per order.
- Simpan sales order discount snapshot.
- Update total calculation sesuai ADR.

## Implementation Contract

- Ikuti [Phase 08 Implementation Contract](implementation-contract.md).
- Stacking promotion ditolak.
- Tax/service calculation tidak masuk scope.

## Verification

- Fixed/percentage calculation tests.
- Snapshot immutability tests.
- Conflict/invalid promotion tests.
- `composer quality`.
