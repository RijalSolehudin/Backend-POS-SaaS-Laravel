# P07-04 — Kitchen Station Routing

Status: **Implemented — Pending MariaDB Verification**

## Outcome

Catalog item dapat dirutekan ke kitchen station yang benar.

## Scope

- Buat module `Kitchen`.
- Buat `kitchen_stations` dan `kitchen_routing_rules`.
- Rule dapat mengacu category/product/variant.
- Fallback station outlet optional.

## Implementation Contract

- Ikuti [Phase 07 Implementation Contract](implementation-contract.md).
- Routing server-side dan tenant/outlet-scoped.

## Verification

- Routing priority tests.
- Missing routing exception report tests.
- Cross-tenant/outlet tests.
- `composer quality`.
