# ADR-044: Offline and Scale MVP Policy

- Status: Accepted
- Date: 2026-08-01

## Context

Phase 09 menyiapkan offline POS dan production scale. Offline harus dibatasi karena payment, stock, dan financial mutation berisiko duplicate atau conflict saat reconnect.

## Decision

### Offline Scope

- Offline MVP mengizinkan read cached catalog, create local draft order, add/update/remove local items, dan queue order completion request.
- Offline MVP tidak mengizinkan refund, void, transfer inventory, purchase receipt, approval decision, device registration, atau catalog/admin mutation.
- Offline payment hanya cash/manual record lokal; gateway payment offline ditolak.

### Client Authority

- Client boleh membuat temporary local IDs.
- Server tetap authority untuk final ULID, order number, receipt, payment status, inventory deduction, dan audit.
- Sync request membawa client record id, device id, sequence number, idempotency key, and payload hash.

### Sync

- Sync bersifat idempotent per device, action, client record id, and sequence number.
- Server menyimpan sync inbox/outbox.
- Repeated sync tidak membuat duplicate order/payment/movement.
- Device revoked tidak boleh sync mutation baru.

### Conflict Resolution

- Financial state tidak boleh silent overwrite.
- Conflict policy:
  - duplicate idempotency same payload returns existing server resource;
  - same client id different payload rejected;
  - catalog stale price requires server repricing before final completion;
  - stock insufficient after reconnect rejects completion and returns actionable conflict.

### Security

- Local cache harus encrypted oleh platform client.
- Server token lifecycle tetap Sanctum/device-scoped.
- Offline data retention client dibatasi konfigurasi.
- Sync payload tidak membawa secret mentah.

### Scale And Recovery

- Define production targets before implementation: latency, throughput, RPO, RTO, queue delay, scheduler freshness.
- Add load test baseline for POS critical path.
- Backup/restore rehearsal wajib.
- Observability minimum: logs, metrics, queue, scheduler, DB, storage, failed sync, and recovery checks.

## Deferred Scope

- Offline gateway authorization.
- Multi-device same-order collaborative editing.
- Cross-region active-active.
- Automatic conflict merge untuk financial mutation.

## Approval

Product owner menyetujui keputusan ini sebagai pre-accepted Phase 09 implementation policy pada 2026-08-01.
