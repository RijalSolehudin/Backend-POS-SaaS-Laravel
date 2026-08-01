# Phase 09 Implementation Contract

Dokumen ini mengunci detail teknis Phase 09 agar implementer tidak membuat keputusan offline/scale baru.

## Module

Gunakan module `Sync` untuk offline protocol, inbox/outbox, conflict, and device sync state. Production scale docs/runbooks boleh berada di `docs/runbooks`.

## Tables

| Table | Owner | Purpose |
|---|---|---|
| `sync_device_states` | P09-02 | Last accepted sequence/cursor per device |
| `sync_inbox_records` | P09-02 | Idempotent client mutation inbox |
| `sync_outbox_records` | P09-02 | Server changes for client fetch |
| `sync_conflicts` | P09-05 | Conflict records requiring operator action |
| `offline_order_drafts` | P09-04 | Server-side representation of synced offline drafts |
| `offline_order_events` | P09-04 | Append-only offline order events |
| `performance_baselines` | P09-07 | Load test baseline metadata |

## Enums

| Enum | Values |
|---|---|
| `SyncRecordStatus` | `accepted`, `duplicate`, `rejected`, `conflict` |
| `SyncConflictStatus` | `open`, `resolved`, `dismissed` |
| `OfflineOrderStatus` | `local_draft`, `queued`, `accepted`, `rejected`, `conflict` |

## Offline Allowed Operations

Allowed:

- read cached catalog;
- create local draft order;
- add/update/remove local order items;
- queue completion request for cash/manual payment.

Denied:

- refund;
- void;
- approval decision;
- inventory transfer/procurement receipt;
- device registration/revocation;
- catalog/admin mutation;
- gateway payment authorization.

## Sync Rules

- Request includes tenant id, outlet id, device id, client record id, action, sequence number, idempotency key, payload hash, and payload.
- Unique inbox scope: tenant, outlet, device, action, client record id, sequence number.
- Same unique scope and same hash returns previous result.
- Same unique scope and different hash returns conflict.
- Server assigns final ULID/order number/receipt.

## Conflict Rules

- No silent overwrite for financial state.
- Stale catalog price requires server repricing before completion.
- Insufficient stock after reconnect rejects completion and records conflict.
- Revoked device mutation rejected.
- Conflict resolution requires actor, reason, and audit event.

## Security Rules

- Server never receives client local encryption keys.
- Sync payload must not contain raw secrets.
- Device state includes revoked_at check.
- Client retention setting is returned by bootstrap API.

## Production Targets

Targets must be configured before P09-07 is marked Done:

- POS catalog p95 latency.
- Order complete p95 latency.
- Sync batch p95 latency.
- Queue max delay.
- Scheduler freshness.
- RPO and RTO.

## Error Codes

- `SYNC_DEVICE_REVOKED`
- `SYNC_SEQUENCE_CONFLICT`
- `SYNC_PAYLOAD_CONFLICT`
- `SYNC_OPERATION_NOT_ALLOWED_OFFLINE`
- `SYNC_CONFLICT_REQUIRES_REVIEW`
- `OFFLINE_ORDER_NOT_FOUND`
- `OFFLINE_ORDER_INVALID_STATE`
- `PERFORMANCE_BASELINE_FAILED`
- `RECOVERY_OBJECTIVE_FAILED`

## Testing Baseline

- Sync idempotency and conflict tests.
- Revoked device rejection tests.
- Offline order queue replay tests.
- Catalog stale/stock insufficient conflict tests.
- Load baseline command/report tests.
- Backup/restore rehearsal evidence.
- `composer quality`.
