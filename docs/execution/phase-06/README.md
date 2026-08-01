# Phase 06 Execution Plan

Status: **In Progress**

Dokumen ini memecah [Phase 06 Recipe and Procurement](../../roadmap/phase-06-recipe-procurement.md) menjadi work package berbasis outcome.

## Required Decision Gate

Implementasi Phase 06 mengikuti [ADR-041 Recipe and Procurement MVP Policy](../../architecture/decisions/041-recipe-procurement-mvp-policy.md).
Detail implementasi wajib mengikuti [Phase 06 Implementation Contract](implementation-contract.md).

## Urutan yang Direkomendasikan

| ID | Work package | Dependency utama | Status |
|---|---|---|---|
| P06-01 | [Recipe and Procurement Decision Gate](P06-01-recipe-procurement-decision-gate.md) | Phase 05 | Done |
| P06-02 | [Recipe Module Foundation](P06-02-recipe-module-foundation.md) | P06-01, P05 | Done |
| P06-03 | [Recipe Versioning and Costing](P06-03-recipe-versioning-costing.md) | P06-02, P05 | Done |
| P06-04 | [Sales Stock Deduction Integration](P06-04-sales-stock-deduction-integration.md) | P06-03, P05 | Done |
| P06-05 | [Procurement Module Foundation](P06-05-procurement-module-foundation.md) | P06-01, P05 | Planned |
| P06-06 | [Purchase Order Approval Lifecycle](P06-06-purchase-order-approval-lifecycle.md) | P06-05 | Planned |
| P06-07 | [Goods Receipt and Purchase Return](P06-07-goods-receipt-purchase-return.md) | P06-06, P05 | Planned |
| P06-08 | [Recipe Procurement Readiness](P06-08-recipe-procurement-readiness.md) | P06-02..P06-07 | Planned |

## Fixed Implementation Decisions

- Recipe dan Procurement adalah module terpisah.
- Recipe wajib versioned dan order deduction menyimpan snapshot version.
- Deduction terjadi saat order completed dan wajib idempotent per sales order item.
- Goods receipt menulis inbound Inventory movement.
- Refund/void tidak otomatis mengembalikan stock.
- Supplier payment, batch/expiry, landed cost, multi-currency purchase, dan accounting payable tidak masuk Phase 06.

## Stop Rule

Berhenti jika implementasi membutuhkan accounting payable, landed cost, batch/expiry compliance, supplier payment, complex unit conversion, atau automatic stock return dari refund.
