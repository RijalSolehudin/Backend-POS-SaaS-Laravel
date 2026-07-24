# ADR-009: MariaDB Version and ULID Storage

- Status: Accepted
- Date: 2026-07-23

## Context

Seluruh entitas domain menggunakan ULID dan shared-database tenancy. Versi MariaDB serta representasi ULID memengaruhi kompatibilitas deployment, ukuran index, foreign key, debugging, dan konsistensi seluruh migration.

## Decision

- Minimum supported database series adalah MariaDB 11.4 LTS.
- Production menggunakan patch terbaru yang tersedia dalam series 11.4.
- Development, CI, staging, dan production menggunakan series MariaDB yang sama.
- ULID disimpan sebagai `CHAR(26)` dengan character set ASCII dan binary collation.
- ULID menggunakan lowercase sebagai bentuk kanonik pada database dan API.
- Primary key dan seluruh foreign key ULID menggunakan tipe, panjang, character set, serta collation yang identik.
- Representasi `BINARY(16)` tidak digunakan pada tahap awal.

## Rationale

- MariaDB 11.4 merupakan LTS yang matang dengan maintenance horizon yang memadai.
- Patch tidak dikunci permanen agar corrective dan security updates dapat diterapkan.
- Laravel menyediakan dukungan langsung untuk ULID 26 karakter.
- `CHAR(26)` lebih mudah di-debug dan tidak membutuhkan encode/decode pada setiap model serta foreign key.
- ASCII mencukupi alphabet ULID dan menghindari overhead character set multibyte.
- Binary collation memberikan perbandingan byte yang deterministik dan mempertahankan lexical ordering ULID.

## Rules

- Backend menormalisasi ULID valid menjadi lowercase pada input boundary yang relevan.
- Flutter menyimpan, mengirim, dan menerima representasi lowercase.
- ULID tetap divalidasi; bentuk identifier tidak menggantikan authorization.
- Business identifier seperti order number dan receipt number menggunakan kolom terpisah.
- Schema review harus memverifikasi kesamaan definisi primary dan foreign ULID.
- Upgrade database series atau perpindahan ke binary ULID membutuhkan ADR baru.

## Consequences

- Index ULID lebih besar daripada `BIGINT` atau `BINARY(16)`.
- Raw SQL, export, dan incident debugging tetap mudah dibaca.
- Tim tidak perlu memelihara custom binary casts pada seluruh relasi.
- Deployment tidak menjanjikan kompatibilitas dengan MariaDB sebelum 11.4, MySQL, atau PostgreSQL.

