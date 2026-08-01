# P03-01 — Operational Safety Decision Gate

Status: **Done**

## Outcome

Keputusan arsitektur wajib Phase 03 disetujui, dicatat, dan diterjemahkan menjadi acceptance criteria sebelum implementasi operational safety dimulai.

## Scope

- Approval atau re-authentication model untuk sensitive actions.
- Refund dan payment reversal semantics.
- Cash in/out dan shift discrepancy policy.
- Audit trail, actor attribution, retention, dan redaction.
- Locking, retry, concurrency, dan failure recovery policy.
- Deployment, backup, monitoring, queue, cache, dan scheduler baseline.
- Pilot readiness checklist.

## Out of Scope

- Implementasi refund, reversal, cash movement, approval UI, backup automation, atau monitoring adapter.
- Integrasi payment gateway.
- Inventory deduction dan accounting export.
- Offline synchronization.

## Dependencies

- Phase 02 POS Core selesai.
- [ADR-038 Operational Safety MVP Policy](../../architecture/decisions/038-operational-safety-mvp-policy.md) disetujui product owner.

## Implementation Checklist

- [x] Review ADR-038 dengan product owner.
- [x] Ubah ADR-038 menjadi `Accepted` atau revisi sesuai keputusan.
- [x] Update Phase 03 roadmap status menjadi `Ready`.
- [x] Update work package P03-02 sampai P03-08 bila keputusan berubah.
- [x] Catat acceptance scenario dan failure path minimum.

## Verification and Evidence

- ADR-038 berstatus `Accepted`.
- Phase 03 execution plan disetujui.
- Tidak ada open decision yang menghalangi P03-02.

## Decision Results

- Sensitive action memakai supervisor approval; cashier re-authentication hanya confirmation ringan.
- Refund Phase 03 hanya full refund manual dan selalu mereferensikan original order/payment.
- Cash in/out masuk shift summary sejak Phase 03.
- Audit financial event disimpan minimal 2 tahun; operational security event minimal 1 tahun.
- Idempotency record minimal 24 jam.
- Queue pilot memakai database queue.
- Backup/restore rehearsal Phase 03 memakai runbook manual dengan evidence.

## Decision Questions

- Partial refund ditunda setelah MVP pilot.
- Default cash discrepancy tolerance adalah `0` minor unit.
- Supervisor approver minimum adalah Tenant Owner atau Outlet Manager.
- Backup/restore rehearsal cukup lewat runbook manual pada Phase 03.
- Queue driver pilot memakai database queue.
