# Phase 02 Execution Plan

Status: **Done**

Dokumen ini memecah [Phase 02 POS Core Vertical Slice](../../roadmap/phase-02-pos-core.md) menjadi work package berbasis outcome.

## Required Decision Gate

Implementasi Phase 02 mengikuti [ADR-037 POS Core MVP Policy](../../architecture/decisions/037-pos-core-mvp-policy.md).

## Urutan yang Direkomendasikan

| ID | Work package | Dependency utama | Status |
|---|---|---|---|
| P02-01 | [POS Core Decision Gate](P02-01-pos-core-decision-gate.md) | Phase 01 | Done |
| P02-02 | [Sales Module Foundation](P02-02-sales-module-foundation.md) | P02-01, Catalog API | Done |
| P02-03 | [Shift Lifecycle](P02-03-shift-lifecycle.md) | P02-02 | Done |
| P02-04 | [Draft Order and Item Management](P02-04-draft-order-items.md) | P02-02, P02-03, Catalog | Done |
| P02-05 | [Idempotent Payment and Completion](P02-05-payment-completion.md) | P02-04 | Done |
| P02-06 | [Receipt Snapshot](P02-06-receipt-snapshot.md) | P02-05 | Done |
| P02-07 | [Close Shift and Summary](P02-07-close-shift-summary.md) | P02-05, P02-06 | Done |
| P02-08 | [Cancel/Void Minimum Flow](P02-08-cancel-void-flow.md) | P02-04, P02-05 | Done |
| P02-09 | [POS Core Readiness](P02-09-pos-core-readiness.md) | P02-02..P02-08 | Done |

## Readiness Gate

Sebelum work package implementasi berubah menjadi `Ready`, pastikan:

- ADR-037 berstatus `Accepted`;
- API request/response contract sudah jelas;
- idempotency dan transaction boundary sudah jelas;
- tenant/outlet/device/shift authorization tidak memiliki keputusan terbuka;
- test evidence untuk retry, duplicate prevention, totals consistency, dan tenant isolation sudah direncanakan.

## Architecture Stop Rule

Berhenti dan tanyakan product owner jika ditemukan kebutuhan tax, discount, service charge, split/partial payment, payment gateway, inventory deduction, table/dining mode, reopening order/shift, atau refund policy.
