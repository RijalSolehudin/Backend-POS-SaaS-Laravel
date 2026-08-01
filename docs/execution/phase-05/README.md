# Phase 05 Execution Plan

Status: **Planned**

Dokumen ini memecah [Phase 05 Inventory](../../roadmap/phase-05-inventory.md) menjadi work package berbasis outcome.

## Required Decision Gate

Implementasi Phase 05 belum boleh dimulai sebelum inventory policy dicatat sebagai ADR `Accepted`.

## Urutan yang Direkomendasikan

| ID | Work package | Dependency utama | Status |
|---|---|---|---|
| P05-01 | [Inventory Decision Gate](P05-01-inventory-decision-gate.md) | Phase 04 | Ready |
| P05-02 | [Inventory Module Foundation](P05-02-inventory-module-foundation.md) | P05-01 | Planned |
| P05-03 | [Opening Balance and Stock Ledger](P05-03-opening-balance-stock-ledger.md) | P05-02 | Planned |
| P05-04 | [Stock Adjustment and Waste](P05-04-stock-adjustment-waste.md) | P05-03 | Planned |
| P05-05 | [Stock Card, Balance, and Low Stock](P05-05-stock-card-balance-low-stock.md) | P05-03, P05-04 | Planned |
| P05-06 | [Inter-Outlet Transfer Lifecycle](P05-06-inter-outlet-transfer-lifecycle.md) | P05-03 | Planned |
| P05-07 | [Inventory Reconciliation and Recovery](P05-07-inventory-reconciliation-recovery.md) | P05-04, P05-05, P05-06 | Planned |
| P05-08 | [Inventory Readiness](P05-08-inventory-readiness.md) | P05-02..P05-07 | Planned |

## Readiness Gate

Sebelum work package implementasi berubah menjadi `Ready`, pastikan:

- ADR Phase 05 berstatus `Accepted`;
- negative stock policy jelas;
- unit conversion dan precision jelas;
- costing method minimum jelas;
- transfer lifecycle dan ownership antar outlet jelas;
- adjustment/waste approval policy jelas;
- idempotency, locking, dan reconciliation rule jelas;
- auto-deduct dari Sales/recipe tetap out of scope sampai Phase 06.

## Architecture Stop Rule

Berhenti dan tanyakan product owner jika ditemukan kebutuhan recipe deduction, batch/expiry traceability wajib, multi-unit manufacturing, supplier procurement, accounting journal, tax compliance, atau integration hardware scale/barcode yang belum disetujui.
