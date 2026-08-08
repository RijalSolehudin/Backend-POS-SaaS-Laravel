# P09-02 — Sync Protocol Foundation

Status: **Done**

## Outcome

Backend memiliki protocol sync idempotent untuk device POS.

## Scope

- Buat module `Sync`.
- Buat device state, inbox, dan outbox.
- Endpoint sync push/pull scoped tenant/outlet/device.

## Delivered

- Module `Sync` dibuat dan didaftarkan melalui `SyncServiceProvider`.
- Tabel `sync_device_states`, `sync_inbox_records`, dan `sync_outbox_records` tersedia.
- Endpoint `GET /api/v1/pos/outlets/{outlet}/sync/bootstrap`, `POST /sync/push`, dan `GET /sync/pull` tersedia dengan `auth:sanctum`.
- `ProcessSyncMutation` enforce unique scope tenant/outlet/device/action/client record/sequence dan duplicate replay berdasarkan payload hash.

## Implementation Contract

- Ikuti [Phase 09 Implementation Contract](implementation-contract.md).
- Jangan implement offline order business rules di P09-02.

## Verification

- Sync idempotency tests.
- Device/outlet isolation tests.
- Revoked device rejection tests.
- `composer quality`.
