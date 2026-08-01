# Development Todolist

Todolist aktif telah dipindahkan ke [Development Roadmap](roadmap/README.md).

Dokumen versi sebelumnya tersedia di [Development Todolist v1](archive/development-todolist-v1.md). Checkbox versi lama tidak mencerminkan kondisi implementasi repository dan hanya dipertahankan sebagai arsip historis.

## Current Planning Tasks

- [x] Memilih MariaDB sebagai database utama.
- [x] Memilih shared-database multi-tenancy.
- [x] Menetapkan Web Admin Blade + Alpine.js untuk back-office dan Flutter untuk operasional POS.
- [x] Memilih ULID sebagai domain primary key.
- [x] Memilih Laravel modular monolith dengan application use cases/actions.
- [x] Menyetujui struktur fisik standar setiap domain module.
- [x] Memilih Laravel Sanctum API token untuk autentikasi Flutter.
- [x] Menyetujui lifecycle token per user-device dengan expiration 30 hari tanpa idle auto-lock.
- [x] Memilih MariaDB 11.4 LTS dan ULID lowercase `CHAR(26)` dengan ASCII binary collation.
- [x] Memilih representasi money hybrid: signed `BIGINT` minor units, `DECIMAL` presisi, dan half-up rounding.
- [x] Memilih tenant/outlet request context eksplisit melalui URL API.
- [x] Menyetujui `/api/v1`, resource envelope, RFC 9457 errors, dan API deprecation policy.
- [x] Menyetujui device registry terpisah dengan single-outlet binding untuk terminal POS.
- [x] Memisahkan session-authenticated Web Admin dari Sanctum-authenticated Flutter API.
- [x] Menetapkan Blade + Alpine.js sebagai default Web Admin dan Vue hanya untuk complex UI tertentu.
- [x] Menetapkan controlled tenant provisioning tanpa public registration pada MVP.
- [x] Menetapkan Platform Admin Web sebagai kanal utama dan CLI hanya untuk bootstrap/emergency.
- [x] Menetapkan Platform Admin dan Tenant Admin sebagai dua area dalam aplikasi/deployment Laravel yang sama.
- [x] Memisahkan `platform_users` dan auth provider dari tenant `users`.
- [x] Menyetujui Web session timeout, mandatory Platform TOTP MFA, dan recovery policy.
- [x] Menyelesaikan architecture decision gate Phase 01.
- [x] Membatasi tenant user ke satu tenant dengan multi-outlet assignment.
- [x] Menggunakan predefined MVP roles tanpa custom role builder.
- [x] Menyetujui acceptance criteria Phase 01.
- [x] Mengubah status perencanaan Phase 01 dari `Not Started` menjadi `Ready`.
- [x] Menyetujui pemecahan Phase 01 menjadi execution work package per capability.
- [x] Membuat draft execution plan Phase 01 dengan sebelas work package berstatus `Planned`.
- [x] Menyelesaikan architecture/readiness review P01-01 Modular Foundation.
- [x] Menyetujui status P01-01 Modular Foundation menjadi `Ready`.
- [x] Memberikan instruksi untuk memulai implementasi Phase 01 dari P01-01.
- [x] Menyelesaikan implementasi awal P01-01 Modular Foundation.
- [x] Menyelesaikan MariaDB container dan first real use-case integration verification P01-01.
- [x] Menyelesaikan verification P01-01 sampai P01-06 pada MariaDB-backed `composer quality`.
- [x] Menyelesaikan P01-07 Predefined RBAC.
- [x] Menyelesaikan P01-08 POS Device Registry.
- [x] Menyelesaikan P01-09 Flutter API Foundation.
- [x] Menyelesaikan P01-10 Minimum Catalog.
- [x] Menyelesaikan P01-11 Security, Audit, and Operational Readiness.

Implementasi Phase 01 Foundation telah selesai. Tahap berikutnya berada pada Phase 02 POS Core.

Phase 01 selesai penuh setelah P01-11 menutup security, audit, operational readiness, dan evidence gate.
