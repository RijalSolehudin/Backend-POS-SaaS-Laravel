# ADR-027: MariaDB Container Test Strategy

- Status: Accepted
- Date: 2026-07-24

## Context

Production menargetkan MariaDB 11.4, sementara database test yang berbeda dapat menyembunyikan perbedaan constraint, collation, strict SQL behavior, transaction, locking, dan numeric semantics. Environment lokal dan CI juga perlu dapat direproduksi tanpa bergantung pada konfigurasi MariaDB yang dipasang langsung pada OS.

## Decision

Automated test yang menggunakan database dijalankan terhadap MariaDB 11.4 dalam container, baik pada local development maupun CI.

Aturan:

- Unit test murni tidak menggunakan database bila business rule dapat diuji tanpa persistence.
- Integration dan feature test menggunakan MariaDB, bukan SQLite.
- Local dan CI menggunakan series, character set, collation, dan strict-mode assumptions yang sama.
- Database test terpisah dari development dan production database.
- Test suite harus dapat dimulai dari schema kosong melalui migration.
- Data test tidak bergantung pada execution order dan harus dapat dibersihkan secara deterministik.
- MariaDB patch/container reference diperbarui secara terkontrol dalam series 11.4 sesuai ADR-009.
- SQLite tidak menjadi fallback yang dianggap ekuivalen ketika MariaDB test infrastructure tidak tersedia.

## Consequences

- Test mendeteksi incompatibility dan constraint behavior pada engine yang ditargetkan.
- Local dan CI mempunyai database environment yang lebih konsisten.
- Developer membutuhkan container runtime untuk menjalankan database-backed test resmi.
- Startup integration suite lebih berat daripada in-memory SQLite.
- Pemisahan unit test dan database-backed test menjadi penting untuk menjaga feedback tetap cepat.

## Alternatives Considered

### SQLite locally and MariaDB only in CI

Memberi feedback lokal yang cepat, tetapi dapat menghasilkan local pass/CI failure akibat perbedaan SQL dan database semantics.

### MariaDB installed directly on each environment

Tidak membutuhkan container runtime, tetapi version, configuration, cleanup, dan isolation lebih sulit dibuat konsisten.

