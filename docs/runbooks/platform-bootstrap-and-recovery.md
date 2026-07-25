# Platform Bootstrap and Emergency Recovery Runbook

Status: **Implemented for P01-02**

Dokumen ini berlaku untuk operator infrastruktur yang telah mempunyai shell access sesuai kebijakan deployment. Input identitas pada command adalah audit attribution, bukan pengganti autentikasi server.

## Prerequisites

- Deployment menggunakan HTTPS.
- Migration telah dijalankan pada MariaDB 11.4.
- `APP_KEY` valid dan dibackup secara aman; TOTP secret dan platform session payload bergantung pada key ini.
- Database queue worker dan Laravel scheduler berjalan.
- Mail transport serta optional `PLATFORM_SECURITY_MAILBOX` dikonfigurasi.
- Operator mempunyai incident/change reference bila prosedur organisasi mewajibkan.

## First Platform Administrator

Jalankan hanya sekali:

```shell
php artisan platform:bootstrap
```

Command meminta secara interaktif:

- name dan email Platform Administrator;
- operator name/email;
- reason dan optional ticket reference;
- password dan confirmation melalui hidden prompt;
- final confirmation.

Expected result:

- command menampilkan Platform User ID dan correlation ID, bukan credential;
- account berstatus `pending_mfa_setup`;
- user membuka `/platform/login`, memasukkan password, mendaftarkan TOTP, lalu menyimpan 10 recovery codes;
- invocation berikutnya ditolak dan diaudit.

Jangan memasukkan password pada command argument, shell script, deployment log, atau ticket.

## Emergency Recovery

Gunakan hanya ketika password dan faktor kedua sama-sama tidak dapat dipulihkan melalui alur normal.

1. Verifikasi authority operator dan identitas pemilik account melalui prosedur organisasi.
2. Buat incident/change reference.
3. Pastikan security mailbox dan audit storage dapat diakses.
4. Jalankan:

```shell
php artisan platform:recover-access
```

5. Masukkan email target, operator identity, reason, reference, dan password baru melalui prompt.
6. Baca dampak revocation lalu berikan final confirmation.
7. Simpan correlation ID pada incident record.
8. Minta pemilik account login dan melakukan enrollment TOTP baru.
9. Pastikan pemilik menyimpan recovery codes baru.
10. Review event `platform_access.emergency_recovered` dan email security alert.

Recovery mencabut seluruh Platform session/challenge, TOTP secret, dan recovery codes lama. Account kembali ke `pending_mfa_setup`. Command tidak menghasilkan recovery URL atau MFA secret.

## Routine Operations

Jalankan queue worker:

```shell
php artisan queue:work --tries=3
```

Jalankan scheduler melalui cron/platform scheduler:

```shell
php artisan schedule:run
```

Scheduler menjalankan `platform:prune-security-state` setiap jam untuk session kedaluwarsa dan audit event yang melewati retensi 12 bulan.

## Incident Checks

- Cari correlation ID pada `platform_security_audit_events`.
- Periksa failed queue jobs jika security email tidak diterima.
- Jangan menyalin encrypted session payload, password hash, TOTP secret, atau recovery-code hash ke ticket.
- Jika `APP_KEY` diduga bocor, anggap seluruh encrypted TOTP secret dan session terdampak; lakukan security response di luar recovery satu account.
