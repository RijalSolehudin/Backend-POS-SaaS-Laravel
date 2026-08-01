# P03-07 — Operational Baseline

Status: **Done**

## Outcome

Pilot outlet memiliki baseline operasional minimum untuk scheduler, queue, cache, backup/restore, monitoring checks, dan load baseline sebelum transaksi riil.

## Scope

- Scheduler baseline untuk token pruning, platform security pruning, dan sales audit retention.
- Queue baseline untuk pilot driver, failed job visibility, retry policy, and worker runbook.
- Cache baseline untuk atomic lock support pada scheduled command dan critical operation.
- Backup/restore rehearsal runbook dengan evidence command dan verification result.
- Monitoring checklist minimum untuk failed jobs, scheduler freshness, database connectivity, queue depth, exception rate, storage pressure, and disk usage.
- Load baseline scenario untuk POS login, catalog read, open shift, create order, complete payment, refund, cash movement, close shift, and daily sales.
- Operational environment checklist for `.env` values needed by Phase 03.

## Out of Scope

- External observability platform integration.
- Production deployment automation.
- Kubernetes, autoscaling, or multi-node orchestration.
- Real payment gateway monitoring.
- Disaster recovery automation.

## Dependencies

- P03-01 selesai.
- P03-05 selesai.
- P03-06 selesai.
- [ADR-038 Operational Safety MVP Policy](../../architecture/decisions/038-operational-safety-mvp-policy.md) accepted.

## Acceptance Criteria

- Operator dapat melihat schedule list dan memastikan job penting terdaftar.
- Queue driver pilot dan failed job handling terdokumentasi.
- Cache store pilot mendukung atomic locks.
- Backup/restore rehearsal memiliki langkah, command, expected output, and rollback note.
- Monitoring checklist dapat dijalankan manual tanpa external SaaS.
- Load baseline mendefinisikan skenario, jumlah request minimum, expected error rate, and pass/fail threshold.
- Semua baseline memiliki dokumentasi runbook yang dapat diikuti developer/operator.

## Verification

- Console tests untuk command/listing bila command baru ditambahkan.
- Runbook review untuk backup/restore dan monitoring checks.
- `php artisan schedule:list` evidence dengan cache store yang aman untuk environment lokal.
- `composer quality` lulus.
- `npm run build` lulus bila ada perubahan frontend.

## Delivered

- Runbook [Operational Baseline](../../runbooks/operational-baseline.md) untuk scheduler, queue, cache, backup/restore rehearsal, monitoring manual, load baseline, dan environment checklist.
- Deployment readiness diperbarui agar mencakup `sales:prune-audit-events`.
- Runbook index menautkan Sales retry/recovery dan operational baseline.

## Evidence

- Laravel Boost SearchDocs digunakan untuk scheduler `withoutOverlapping()`, cache atomic locks, queue worker tries, failed job handling, dan service provider guidance.
- `DB_CONNECTION=mariadb DB_HOST=127.0.0.1 DB_PORT=33067 DB_DATABASE=pos_testing DB_USERNAME=pos DB_PASSWORD=pos-testing-only php artisan schedule:list`
  - `sanctum:prune-expired --hours=24` daily.
  - `platform:prune-security-state` hourly.
  - `sales:prune-audit-events` daily.
- `composer quality`
- `npm run build`

## Architecture Stop Rule

Berhenti dan tanyakan product owner jika baseline membutuhkan external monitoring berbayar, Redis wajib, queue worker manager production, cloud backup provider, or deployment topology final.
