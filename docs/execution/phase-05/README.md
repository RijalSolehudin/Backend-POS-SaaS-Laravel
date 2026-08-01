# Phase 05 Execution Plan

Status: **Ready**

Dokumen ini memecah [Phase 05 Inventory](../../roadmap/phase-05-inventory.md) menjadi work package berbasis outcome.

## Required Decision Gate

Implementasi Phase 05 mengikuti [ADR-040 Inventory Ledger MVP Policy](../../architecture/decisions/040-inventory-ledger-mvp-policy.md).
Detail implementasi wajib mengikuti [Phase 05 Implementation Contract](implementation-contract.md).

## Urutan yang Direkomendasikan

| ID | Work package | Dependency utama | Status |
|---|---|---|---|
| P05-01 | [Inventory Decision Gate](P05-01-inventory-decision-gate.md) | Phase 04 | Done |
| P05-02 | [Inventory Module Foundation](P05-02-inventory-module-foundation.md) | P05-01 | Done |
| P05-03 | [Opening Balance and Stock Ledger](P05-03-opening-balance-stock-ledger.md) | P05-02 | Done |
| P05-04 | [Stock Adjustment and Waste](P05-04-stock-adjustment-waste.md) | P05-03 | Done |
| P05-05 | [Stock Card, Balance, and Low Stock](P05-05-stock-card-balance-low-stock.md) | P05-03, P05-04 | Ready |
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

## Fixed Implementation Decisions

- Gunakan module `App\Modules\Inventory` dengan physical structure standar.
- Gunakan table, enum, action naming, idempotency scope, approval default, transfer in-transit policy, opening balance policy, dan business error code dari implementation contract.
- Gunakan `inventory_balances` sebagai projection wajib dan lock row balance saat mutation.
- Gunakan `inventory_stock_movements` sebagai ledger immutable.
- Gunakan moving average berbasis `total_cost_minor`, bukan average cost manual sebagai sumber kebenaran.
- Jangan implement auto-deduct dari Sales, recipe/BOM, batch/expiry, FIFO, procurement, unit conversion kompleks, atau accounting journal di Phase 05.

## Architecture Stop Rule

Berhenti dan tanyakan product owner jika ditemukan kebutuhan recipe deduction, batch/expiry traceability wajib, multi-unit manufacturing, supplier procurement, accounting journal, tax compliance, atau integration hardware scale/barcode yang belum disetujui.
