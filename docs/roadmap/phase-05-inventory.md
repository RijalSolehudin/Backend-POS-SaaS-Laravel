# Phase 05: Inventory

Status: **Ready**

## Outcome

Outlet mempunyai ledger stock yang dapat ditelusuri dan direkonsiliasi.

## Candidate Scope

- Unit dan inventory item.
- Opening balance dan stock movement ledger.
- Stock adjustment dan waste.
- Inter-outlet transfer.
- Stock card, balance, low-stock, dan valuation minimum.

## Architecture Decisions Required

- Accepted decision: [ADR-040 Inventory Ledger MVP Policy](../architecture/decisions/040-inventory-ledger-mvp-policy.md).
- Negative stock ditolak untuk mutation normal.
- Phase 05 memakai satu base unit per item dan fixed decimal quantity sampai 3 digit desimal.
- Batch/expiry ditunda.
- Costing memakai moving average sederhana.
- Stock mutation memakai transaction, idempotency, dan balance locking.
- Adjustment decrease, waste, dan transfer di atas threshold membutuhkan approval.
- Current balance projection wajib ada dan direkonsiliasi dari ledger.
- Transfer dispatched-but-not-received dilaporkan sebagai in-transit.
- Opening balance hanya boleh satu kali per tenant/outlet/item.

## Acceptance Criteria

- Setiap perubahan balance dapat ditelusuri ke movement dan sumbernya.
- Retry tidak membuat movement ganda.
- Transfer memiliki state dan ownership yang konsisten pada kedua outlet.
- Stock card dapat direkonsiliasi dengan balance.

## Out of Scope

- Auto-deduct recipe sampai keputusan dan capability Phase 06 siap.
- Batch/expiry traceability.
- FIFO, landed cost, multi-currency valuation, dan accounting journal.
- Unit conversion kompleks dan packaging hierarchy.
