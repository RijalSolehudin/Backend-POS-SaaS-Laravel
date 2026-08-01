# Deployment Readiness

Checklist ini menutup baseline operasional Phase 01 sebelum deploy ke staging/production.

## Runtime

- PHP, Composer, Node.js, dan MariaDB mengikuti versi pada `README.md`.
- `APP_ENV=production` dan `APP_DEBUG=false`.
- HTTPS terminates sebelum Laravel menerima traffic.
- `SESSION_SECURE_COOKIE=true` untuk Tenant Admin.
- `PLATFORM_SESSION_SECURE_COOKIE=true` untuk Platform Admin.
- Queue worker dan scheduler aktif.

## Database

- Jalankan `php artisan migrate --force` dari database kosong pada MariaDB 11.4 LTS.
- Pastikan strict SQL mode aktif.
- Jalankan `composer quality` pada MariaDB-backed test database sebelum release.

## Scheduler

Scheduler wajib menjalankan:

```shell
php artisan schedule:run
```

Baseline scheduled tasks:

- `platform:prune-security-state` setiap jam.
- `sanctum:prune-expired --hours=24` setiap hari.

## Security

- Credential, token, TOTP secret, recovery code, SQL detail, dan raw integration response tidak boleh dimasukkan ke audit reason, issue tracker, chat, atau log.
- API error menggunakan `application/problem+json` dan tidak mengekspos stack trace.
- Semua API response membawa `X-Request-ID`.
- Audit table yang perlu dipantau:
  - `platform_security_audit_events`
  - `tenancy_audit_events`

## Release Evidence

Simpan bukti berikut pada release note internal:

- commit SHA;
- hasil `composer quality`;
- hasil `npm run build`;
- hasil migration fresh/staging migration;
- link OpenAPI;
- ringkasan demo end-to-end Phase 01;
- daftar temuan security review dan status resolusi.
