# ADR-004: Use ULID for Domain Primary Keys

- Status: Accepted
- Date: 2026-07-21

## Decision

Entitas domain menggunakan ULID kanonik 26 karakter sebagai primary key. Business identifier seperti order number disimpan terpisah.

## Rationale

ULID dapat dibuat oleh server maupun offline client, tidak memerlukan ID remapping, tidak mudah dienumerasi, dan memiliki insertion locality lebih baik daripada UUID v4.

## Consequences

- Index dan foreign key lebih besar daripada bigint.
- Server wajib memvalidasi ownership dan tidak menganggap ULID sebagai kontrol keamanan.
- Detail storage type dan collation MariaDB harus disetujui sebelum migration.

