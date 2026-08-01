# P03-01 — Operational Safety Decision Gate

Status: **Planned**

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

- [ ] Review ADR-038 dengan product owner.
- [ ] Ubah ADR-038 menjadi `Accepted` atau revisi sesuai keputusan.
- [ ] Update Phase 03 roadmap status menjadi `Ready`.
- [ ] Update work package P03-02 sampai P03-08 bila keputusan berubah.
- [ ] Catat acceptance scenario dan failure path minimum.

## Verification and Evidence

- ADR-038 berstatus `Accepted`.
- Phase 03 execution plan disetujui.
- Tidak ada open decision yang menghalangi P03-02.

## Decision Questions

- Apakah sensitive actions memakai supervisor approval, re-authentication oleh cashier, atau kombinasi keduanya?
- Apakah refund Phase 03 cukup sebagai manual reversal internal, atau harus menyiapkan contract untuk gateway settlement?
- Apakah cash in/out menjadi bagian shift summary sejak Phase 03?
- Berapa lama retention audit dan idempotency untuk data operasional pilot?
- Apa recovery procedure minimum untuk double-submit, timeout setelah payment recorded, dan close shift conflict?
