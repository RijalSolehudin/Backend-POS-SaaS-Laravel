# P05-06 — Inter-Outlet Transfer Lifecycle

Status: **Ready**

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

## Implementation Contract

- Ikuti [Phase 05 Implementation Contract](implementation-contract.md).
- Buat table `inventory_transfers` dan `inventory_transfer_lines`.
- Buat enum `TransferStatus`.
- Buat action `CreateInventoryTransfer`, `RequestInventoryTransferApproval`, `ApproveInventoryTransfer`, `DispatchInventoryTransfer`, `ReceiveInventoryTransfer`, dan `CancelInventoryTransfer`.
- Transfer line memakai item base unit dan quantity decimal string.
- Transfer default membutuhkan approval untuk quantity positif karena threshold `0.000`.
- Dispatch membuat movement `transfer_out` pada source outlet dan mengubah status menjadi `dispatched`.
- Received membuat movement `transfer_in` pada destination outlet dan mengubah status menjadi `received`.
- Stock yang sudah `dispatched` tetapi belum `received` dilaporkan sebagai `in_transit_quantity`.
- Partial receive dan variance tidak boleh diimplementasikan pada Phase 05.
- Cancel hanya valid pada `draft`, `requested`, atau `approved`.

## Verification

- Feature tests transfer lifecycle.
- Cross-outlet and cross-tenant rejection tests.
- Idempotency tests.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti jika transfer membutuhkan shipment tracking, receiving variance accounting, atau external warehouse ownership yang belum diputuskan.
