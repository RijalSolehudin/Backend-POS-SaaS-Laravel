# Phase 09 Offline Scale Readiness Runbook

Status: **Implemented — Pending MariaDB Verification**

## Scope

Runbook ini digunakan untuk memverifikasi offline POS sync, catalog snapshot, conflict handling, device trust, performance baseline, dan recovery objective sebelum Phase 09 dinyatakan production-ready.

## Preflight

- Pastikan semua provider module termuat, termasuk `SyncServiceProvider`.
- Jalankan migration pada environment target.
- Pastikan POS device terdaftar dan berstatus `active`.
- Pastikan token POS memiliki `pos_device_id`.
- Pastikan outlet dan tenant aktif.

## Offline Sync Checks

- Bootstrap policy harus mengembalikan retention cache/order dan `requires_local_encryption=true`.
- Server tidak boleh menerima atau menyimpan local encryption key.
- Catalog snapshot harus memiliki `version` deterministic dan hanya berisi item outlet yang tersedia.
- Client mutation harus membawa `client_record_id`, `action`, `sequence_number`, `idempotency_key`, `payload_hash`, dan `payload`.
- Scope idempotency wajib tenant/outlet/device/action/client record/sequence.

## Allowed Offline Mutations

- `offline_order.create_draft`
- `offline_order.add_item`
- `offline_order.update_item`
- `offline_order.remove_item`
- `offline_order.complete_cash`
- `offline_order.complete_manual`

## Denied Offline Mutations

- Refund, void, approval, inventory, procurement, device registry, catalog mutation, dan payment gateway authorization.
- Jika mutation tidak ada di allowlist, hasil sync harus `rejected`.

## Conflict Procedure

- Payload hash berbeda pada scope yang sama harus menjadi `sync_conflicts`.
- Sequence yang lebih kecil/sama dari accepted cursor tanpa inbox existing harus menjadi conflict.
- Stale catalog atau insufficient stock signal harus menjadi conflict dan tidak membuat Sales order.
- Operator resolution wajib mencatat actor, reason, dan timestamp.
- Financial conflict tidak boleh di-auto-merge.

## Performance Evidence

Gunakan command berikut untuk mencatat baseline p95:

```bash
php artisan sync:performance-baseline sync_push_p95 850 --target=1000 --json
```

Baseline yang harus tersedia sebelum go-live:

- `catalog_snapshot_p95`
- `order_replay_p95`
- `sync_push_p95`
- `sync_pull_p95`
- `queue_delay_p95`
- `scheduler_freshness_p95`

## Recovery Evidence

- Catat hasil backup/restore rehearsal sebagai RPO/RTO evidence melalui `CheckRecoveryObjectives`.
- RPO/RTO breach harus menggagalkan readiness sampai ada corrective action.
- Restore rehearsal harus membuktikan inbox/outbox, offline order draft/event, dan conflict records tetap konsisten.

## Verification Commands

```bash
composer quality:static
php artisan test tests/Feature/Sync/SyncPhaseNineTest.php
php artisan sync:performance-baseline sync_push_p95 900 --target=1000 --fail-on-breach --json
```

## Exit Criteria

- Semua Sync feature test lulus di MariaDB.
- Tidak ada architecture violation.
- Tidak ada mutation offline di luar allowlist.
- Revoked device ditolak untuk push mutation baru.
- Baseline performance dan RPO/RTO tercatat.
