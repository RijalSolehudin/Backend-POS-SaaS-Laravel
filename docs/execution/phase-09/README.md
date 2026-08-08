# Phase 09 Execution Plan

Status: **Implemented — Pending MariaDB Verification**

Dokumen ini memecah [Phase 09 Offline and Scale](../../roadmap/phase-09-offline-scale.md) menjadi work package berbasis outcome.

## Required Decision Gate

Implementasi Phase 09 mengikuti [ADR-044 Offline and Scale MVP Policy](../../architecture/decisions/044-offline-scale-mvp-policy.md).
Detail implementasi wajib mengikuti [Phase 09 Implementation Contract](implementation-contract.md).

## Urutan yang Direkomendasikan

| ID | Work package | Dependency utama | Status |
|---|---|---|---|
| P09-01 | [Offline Scale Decision Gate](P09-01-offline-scale-decision-gate.md) | Phase 08 | Done |
| P09-02 | [Sync Protocol Foundation](P09-02-sync-protocol-foundation.md) | P09-01, Tenancy | Implemented — Pending MariaDB Verification |
| P09-03 | [Offline Catalog Snapshot](P09-03-offline-catalog-snapshot.md) | P09-02, Catalog | Implemented — Pending MariaDB Verification |
| P09-04 | [Offline Order Queue](P09-04-offline-order-queue.md) | P09-02, Sales | Implemented — Pending MariaDB Verification |
| P09-05 | [Conflict Detection and Resolution](P09-05-conflict-detection-resolution.md) | P09-04, Inventory | Implemented — Pending MariaDB Verification |
| P09-06 | [Device Trust and Local Data Security](P09-06-device-trust-local-data-security.md) | P09-02, Tenancy | Implemented — Pending MariaDB Verification |
| P09-07 | [Production Scale and Observability](P09-07-production-scale-observability.md) | P09-02..P09-06 | Implemented — Pending MariaDB Verification |
| P09-08 | [Disaster Recovery and Production Readiness](P09-08-disaster-recovery-production-readiness.md) | P09-07 | Implemented — Pending MariaDB Verification |

## Implementation Notes

- Module `Sync` ditambahkan dengan device state, inbox, outbox, offline order draft/event, conflict, dan performance baseline persistence.
- POS sync API tersedia untuk bootstrap, catalog snapshot, push mutation, dan pull outbox.
- Offline completion hanya menerima cash/manual non-cash dan replay ke Sales agar server tetap membuat order number, payment, dan receipt final.
- Conflict payload mismatch, stale catalog/stock signal, sequence conflict, dan revoked device ditangani tanpa silent overwrite.
- Readiness detail ada di [Phase 09 Offline Scale Readiness Runbook](../../runbooks/phase-09-offline-scale-readiness.md).

## Fixed Implementation Decisions

- Offline mutation scope terbatas.
- Server tetap final authority.
- Sync wajib idempotent per device/action/client record/sequence.
- Financial conflict tidak silent overwrite.
- Revoked device tidak boleh sync mutation baru.

## Stop Rule

Berhenti jika implementasi membutuhkan offline gateway authorization, multi-device collaborative editing untuk order yang sama, cross-region active-active, atau automatic merge financial mutation.
