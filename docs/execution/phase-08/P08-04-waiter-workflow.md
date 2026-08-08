# P08-04 — Waiter Workflow

Status: **Implemented — Pending MariaDB Verification**

## Outcome

Waiter dapat membuat dan menambah order untuk table/outlet sesuai permission.

## Scope

- Tambahkan waiter order source.
- Tambahkan role/permission check waiter.
- Integrasikan ke table session bila dine-in.

## Implementation Contract

- Ikuti [Phase 08 Implementation Contract](implementation-contract.md).
- Payment tetap mengikuti Sales rules.

## Verification

- Waiter create/add item tests.
- Permission tests.
- Table session regression.
- `composer quality`.
