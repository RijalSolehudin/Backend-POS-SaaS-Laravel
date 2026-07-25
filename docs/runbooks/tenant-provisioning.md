# Tenant Provisioning Runbook

Status: **Implemented for P01-04**

Gunakan Platform Admin Web sebagai kanal provisioning normal. CLI hanya untuk controlled operation ketika Web tidak dapat digunakan dan operator mempunyai privileged shell access.

## Prerequisites

- Migration P01-04 telah lulus pada MariaDB 11.4.
- Platform Administrator telah login dengan TOTP dan mempunyai recent confirmation.
- Tenant code, initial outlet, currency, timezone, owner identity, serta ticket/reason telah diverifikasi.
- Operator telah menentukan kanal aman untuk menyampaikan initial password kepada Tenant Owner.

Jangan menyimpan initial password pada ticket, chat publik, shell argument, deployment log, atau audit reason.

## Platform Admin Web

1. Buka `/platform/tenants`.
2. Pilih **Provision tenant**.
3. Selesaikan password + TOTP recent confirmation bila diminta.
4. Isi tenant name/code, currency, timezone, initial outlet, Tenant Owner, initial password, dan reason/ticket.
5. Submit sekali dan tunggu hasil.
6. Simpan tenant ID/correlation evidence yang relevan pada change record.
7. Sampaikan initial password melalui kanal aman yang telah disetujui.
8. Tenant Owner wajib mengganti password pada login pertama setelah Tenant Admin authentication tersedia.

Browser mengirim idempotency key tersembunyi. Retry request yang sama mengembalikan output provisioning awal tanpa membuat duplikasi.

## Controlled CLI

Jalankan:

```shell
php artisan tenant:provision
```

Command wajib interaktif dan meminta:

- operator identity dan reason/ticket;
- idempotency key, atau menerima generated key;
- tenant, currency, timezone, dan initial outlet;
- Tenant Owner name/email;
- password dan confirmation melalui hidden prompt;
- final confirmation.

Command menampilkan tenant/outlet/owner ID, idempotency key, dan correlation ID. Command tidak menampilkan password.

Jika output hilang setelah request mungkin berhasil, jalankan kembali dengan idempotency key serta input yang sama. Jangan memakai key lama untuk input berbeda.

## Disable Tenant

1. Buka detail tenant di `/platform/tenants/{tenant}`.
2. Pastikan target tenant benar.
3. Isi reason yang dapat diaudit.
4. Selesaikan recent confirmation bila diminta.
5. Konfirmasi dampak lalu disable.

Disable tidak menghapus data. Tenant-context authorization pada work package berikutnya wajib menolak effective access pada request selanjutnya ketika status tenant `disabled`.

## Failure and Incident Checks

- Cari correlation ID pada `tenancy_audit_events`.
- Event sukses: `tenant.provisioned`, `tenant.provisioning_replayed`, `tenant.disabled`, atau `tenant.disable_replayed`.
- Event gagal: `tenant.provisioning_failed` dengan stable failure code pada metadata.
- Pastikan tidak ada partial row tenant/outlet/user/membership/role setelah provisioning gagal.
- Jangan menyalin password hash, request fingerprint, session payload, atau credential ke ticket.

## Local Schema Baseline

P01-04 memindahkan legacy skeleton `users` schema ke module Identity dan mengganti bigint ID dengan ULID. Untuk disposable development database yang dibuat sebelum P01-04, gunakan database baru/fresh migration setelah membuat backup bila ada data yang perlu dipertahankan.

Jangan menjalankan destructive fresh migration pada database berisi data penting. Upgrade data-bearing environment memerlukan migration plan terpisah.
