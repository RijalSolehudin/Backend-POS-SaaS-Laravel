# P07-02 — Dining Floor and Table Foundation

Status: **Planned**

## Outcome

Outlet memiliki floor dan table yang dapat dikelola tenant admin.

## Scope

- Buat module `Dining`.
- Buat `dining_floors` dan `dining_tables`.
- Web admin baseline untuk floor/table.
- Tenant/outlet isolation dan status active/inactive.

## Implementation Contract

- Ikuti [Phase 07 Implementation Contract](implementation-contract.md).
- Jangan membuat Sales order atau table session pada P07-02.

## Verification

- Floor/table CRUD tests.
- Cross-outlet/tenant rejection tests.
- `composer quality`.
