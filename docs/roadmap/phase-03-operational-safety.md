# Phase 03: Operational Safety

Status: **In Progress**

## Outcome

MVP memiliki kontrol finansial, auditability, failure recovery, dan kesiapan pilot outlet.

## Scope

- Void, cancel, refund/reversal sesuai policy.
- Cash in/out dan shift discrepancy sesuai kebutuhan.
- Supervisor approval atau re-authentication bila disetujui.
- Audit trail dan actor attribution.
- Concurrency, retry, dan failure recovery hardening.
- Security, observability, backup/restore rehearsal, dan load baseline.
- Pilot readiness checklist.

## Architecture Decisions Required

- Proposed decision: [ADR-038 Operational Safety MVP Policy](../architecture/decisions/038-operational-safety-mvp-policy.md).
- Approval model untuk sensitive actions.
- Refund dan payment reversal semantics.
- Audit retention dan sensitive-data policy.
- Locking/isolation policy transaksi kritis.
- Deployment, backup, monitoring, queue, dan cache baseline.

## Acceptance Criteria

- Setiap aksi sensitif dapat ditelusuri ke actor, alasan, dan waktu.
- Concurrent/retried requests tidak merusak saldo, order, atau payment state.
- Failure scenario kritis memiliki recovery procedure yang diuji.
- Daily reconciliation dapat menjelaskan discrepancy.
- Pilot checklist diterima sebelum outlet menggunakan sistem untuk transaksi riil.
