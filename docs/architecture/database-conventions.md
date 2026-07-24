# Database Conventions

## Accepted Decisions

- Engine utama: MariaDB.
- Minimum supported series: MariaDB 11.4 LTS.
- Production menggunakan patch terbaru yang tersedia pada series 11.4.
- Development, CI, staging, dan production menggunakan series MariaDB yang sama.
- Model tenancy: shared database/shared schema.
- Primary key domain: ULID 26 karakter.
- ULID disimpan sebagai lowercase `CHAR(26)` dengan character set ASCII dan binary collation.
- Nomor bisnis seperti order number tidak menjadi primary key.

## Naming

- Nama tabel dan kolom menggunakan `snake_case`.
- Foreign key mengikuti `<entity>_id` dan menggunakan tipe ULID yang sama.
- Timestamp Laravel standar digunakan kecuali domain memerlukan waktu khusus.
- Status menggunakan string-backed enum pada aplikasi; constraint database dipertimbangkan per kasus.

## Tenant Ownership

- Tabel tenant-owned menyimpan `tenant_id` langsung ketika dibutuhkan untuk scope, integrity, atau performa.
- Data outlet-owned memiliki `outlet_id`; validasi memastikan outlet berasal dari tenant aktif.
- Unique constraint harus memasukkan tenant/outlet scope, misalnya `(tenant_id, sku)`.
- Index tenant/outlet ditempatkan sebagai prefix untuk pola query utama bila sesuai hasil profiling.

## ULID

- Bentuk kanonik: 26 karakter lowercase.
- Tipe penyimpanan menggunakan `CHAR(26)` dengan character set ASCII dan binary collation.
- Primary key serta seluruh foreign key ULID wajib memiliki tipe, panjang, character set, dan collation identik.
- ULID dapat dibuat oleh server atau authorized offline client.
- Server tetap memvalidasi format, ownership, idempotency, dan collision.
- `BINARY(16)` tidak digunakan pada tahap awal; perubahan representasi memerlukan ADR baru.

## Money

- Floating-point dilarang untuk seluruh business calculation.
- Monetary amount final menggunakan signed `BIGINT` dalam minor units.
- Untuk IDR, satu minor unit sama dengan satu rupiah.
- Nama kolom monetary amount menggunakan suffix `_minor`.
- Currency code disimpan eksplisit bersama aggregate/transaksi terkait.
- Quantity, conversion factor, recipe cost, dan intermediate calculation menggunakan `DECIMAL` dengan precision sesuai domain.
- Percentage rate menggunakan integer basis points ketika presisi `0,01%` mencukupi.
- Nilai finansial final dibulatkan half-up ke minor unit.
- Snapshot transaksi tidak dihitung ulang dari master data historis.
- `FLOAT` dan `DOUBLE` tidak digunakan untuk money, rate, quantity, atau costing business logic.

## Referential Integrity

- Foreign key digunakan secara default untuk relasi penting.
- Cascade delete hanya digunakan untuk child yang tidak memiliki arti audit mandiri.
- Financial records, audit records, dan stock movements tidak di-hard-delete.
- Soft delete bukan pengganti lifecycle status.

## MariaDB Compatibility

Desain tidak boleh mengandalkan fitur PostgreSQL seperti `JSONB`, `UUID[]`, atau `gen_random_uuid()`. Relasi many-to-many menggunakan pivot table kecuali penggunaan JSON telah disetujui untuk data non-relasional.

## Decisions Required Before Implementation

- Isolation/locking strategy untuk nomor transaksi, shift, payment, dan stock.
- Backup, retention, dan point-in-time recovery target.
