# P05-06 — Inter-Outlet Transfer Lifecycle

Status: **Planned**

## Outcome

Outlet dapat memindahkan stok ke outlet lain dengan lifecycle yang konsisten, auditable, dan tidak membuat balance kedua outlet rancu.

## Scope

- Tambahkan transfer request antar outlet dalam tenant yang sama.
- Tambahkan state lifecycle sesuai ADR, misalnya draft/requested/approved/dispatched/received/cancelled.
- Catat outbound movement saat dispatch sesuai policy.
- Catat inbound movement saat receive sesuai policy.
- Tangani partial receive atau variance bila disetujui ADR.
- Tambahkan audit event untuk tiap perubahan state.

## Out of Scope

- Transfer lintas tenant.
- Multi-hop transfer.
- Logistics/shipping integration.
- Procurement receiving dari supplier.

## Dependencies

- P05-03 selesai.
- Approval policy P05-01 selesai.

## Acceptance Criteria

- Transfer hanya bisa antar outlet dalam tenant yang sama.
- Source outlet dan destination outlet memiliki ledger movement yang dapat ditelusuri.
- Transfer state tidak bisa lompat melewati lifecycle valid.
- Retry tidak membuat movement ganda.
- Cancel hanya boleh pada state yang aman.

## Verification

- Feature tests transfer lifecycle.
- Cross-outlet and cross-tenant rejection tests.
- Idempotency tests.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti jika transfer membutuhkan shipment tracking, receiving variance accounting, atau external warehouse ownership yang belum diputuskan.
