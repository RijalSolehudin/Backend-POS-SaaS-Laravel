# Operational Baseline

Runbook ini menjadi baseline operasional minimum untuk pilot outlet Phase 03. Semua langkah dapat dijalankan manual tanpa external observability SaaS.

## Scheduler

Worker scheduler harus aktif di environment pilot:

```bash
php artisan schedule:run
```

Evidence lokal:

```bash
DB_CONNECTION=mariadb DB_HOST=127.0.0.1 DB_PORT=33067 DB_DATABASE=pos_testing DB_USERNAME=pos DB_PASSWORD=pos-testing-only php artisan schedule:list
```

Expected scheduled tasks:

| Command | Frequency | Purpose |
|---|---:|---|
| `sanctum:prune-expired --hours=24` | daily | membersihkan token POS yang expired melewati retention window |
| `platform:prune-security-state` | hourly | membersihkan state security platform yang expired dan audit melewati retention |
| `sales:prune-audit-events` | daily | membersihkan Sales audit event melewati retention pilot |

Semua scheduled task penting memakai `withoutOverlapping()`. Jika scheduler crash meninggalkan lock lama, jalankan `php artisan schedule:clear-cache` setelah memastikan tidak ada proses lama yang masih berjalan.

## Queue

Pilot memakai database queue.

Required env:

```dotenv
QUEUE_CONNECTION=database
QUEUE_FAILED_DRIVER=database-uuids
```

Jalankan worker:

```bash
php artisan queue:work --tries=3 --timeout=90
```

Operational checks:

- `php artisan queue:failed` harus kosong sebelum pilot dimulai.
- Jika ada failed job, review exception, payload, tenant/outlet context, dan correlation ID.
- Retry hanya setelah penyebab dipahami: `php artisan queue:retry {uuid}`.
- Untuk deployment, stop worker lama dengan `php artisan queue:restart`, lalu pastikan worker baru berjalan.

## Cache

Pilot memakai database cache agar schedule lock dan atomic lock tidak hilang saat proses PHP restart.

Required env:

```dotenv
CACHE_STORE=database
```

Database cache mendukung atomic locks untuk `withoutOverlapping()`. Jangan gunakan `array` atau `file` pada multi-process pilot karena lock tidak cukup aman antar proses/server.

## Backup And Restore Rehearsal

Sebelum pilot, jalankan rehearsal pada database non-production.

1. Catat commit SHA dan waktu rehearsal.
2. Ambil backup MariaDB dari database pilot/staging.
3. Restore ke database kosong.
4. Jalankan migration status dan smoke checks.
5. Jalankan `php artisan sales:recovery-check --json`.
6. Jalankan demo path POS dari login sampai close shift pada data restore.

Expected result:

- restore berhasil tanpa error SQL;
- migration status tidak menunjukkan pending migration yang tidak disengaja;
- recovery check tidak menemukan financial ambiguity;
- demo path bisa menjelaskan gross sales, refunds, net sales, expected cash, counted cash, dan variance.

Rollback note: jika restore rehearsal gagal, jangan gunakan backup tersebut untuk pilot recovery. Ambil backup ulang, simpan error SQL, dan review schema/app commit mismatch.

## Monitoring Checklist

Jalankan checklist ini minimal sebelum outlet buka dan setelah outlet tutup.

| Check | Command/source | Pass condition |
|---|---|---|
| Database connectivity | `php artisan migrate:status` | command berhasil dan schema terbaca |
| Scheduler freshness | `php artisan schedule:list` | tiga scheduled tasks baseline muncul |
| Failed jobs | `php artisan queue:failed` | kosong |
| Queue depth | query table `jobs` | backlog wajar dan turun saat worker aktif |
| Exception rate | `storage/logs/laravel.log` | tidak ada error berulang pada flow POS |
| Storage pressure | `df -h` | disk cukup untuk log, cache, dan backup |
| Sales ambiguity | `php artisan sales:recovery-check` | exit code `0` |

## Load Baseline

Jalankan minimal sebelum pilot dengan data outlet/staging yang realistis.

| Scenario | Minimum request count | Pass threshold |
|---|---:|---|
| POS login | 20 | error rate 0%, p95 terasa responsif untuk operator |
| Catalog read | 50 | error rate 0%, response konsisten per outlet |
| Open shift | 5 | idempotent untuk cashier yang sama |
| Create draft order | 50 | tidak ada duplicate order number |
| Add/update/remove item | 100 | total order konsisten |
| Complete payment | 50 | tidak ada duplicate payment/receipt saat retry |
| Approval + void | 10 | approval consumed satu kali |
| Approval + refund | 10 | refund tercatat satu kali dan net sales benar |
| Cash in/out | 20 | expected cash shift berubah sesuai movement |
| Close shift | 5 | variance tercatat dan audit event ada |
| Daily sales | 20 | gross/refund/net cocok dengan shift summary |

## Environment Checklist

Minimum env untuk pilot Phase 03:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:...
APP_URL=https://...
DB_CONNECTION=mariadb
QUEUE_CONNECTION=database
QUEUE_FAILED_DRIVER=database-uuids
CACHE_STORE=database
SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
PLATFORM_SESSION_SECURE_COOKIE=true
MAIL_MAILER=...
PLATFORM_SECURITY_MAILBOX=...
```

Jangan menjalankan pilot dengan `CACHE_STORE=array`, `QUEUE_CONNECTION=sync`, atau `APP_DEBUG=true`.
