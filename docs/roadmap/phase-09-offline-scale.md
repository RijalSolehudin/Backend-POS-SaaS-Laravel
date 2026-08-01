# Phase 09: Offline and Scale

Status: **Not Started**

## Outcome

Flutter dapat melanjutkan operasi yang disetujui saat koneksi bermasalah dan sistem mencapai target production maturity.

## Candidate Scope

- Local data store dan synchronization protocol.
- Offline order queue.
- Conflict detection/resolution.
- Device registration dan revocation.
- Multi-outlet operational scaling.
- Performance/load hardening.
- Disaster recovery, retention, dan deployment maturity.

## Architecture Decisions Required

- Accepted decision: [ADR-044 Offline and Scale MVP Policy](../architecture/decisions/044-offline-scale-mvp-policy.md).
- Offline operation dibatasi ke cached catalog, local draft order, local item changes, dan queued completion.
- Server tetap authority untuk final order number, receipt, payment status, inventory deduction, dan audit.
- Sync memakai inbox/outbox, device sequence, idempotency key, dan payload hash.
- Conflict financial tidak boleh silent overwrite.
- Device revoked tidak boleh sync mutation baru.
- Performance, RPO, RTO, dan recovery targets wajib diuji.

## Acceptance Criteria

- Repeated sync bersifat idempotent.
- Conflict tidak diselesaikan dengan silent overwrite untuk data kritis.
- Device yang dicabut tidak dapat menyinkronkan data baru.
- Offline dan online totals menghasilkan hasil deterministik.
- Recovery objectives dan load targets yang disetujui berhasil diuji.
