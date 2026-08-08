# POS F&B Documentation

Dokumentasi ini adalah sumber kebenaran untuk perencanaan sistem POS F&B. Dokumen di sini menjelaskan kebutuhan dan keputusan; dokumen ini bukan bukti bahwa fitur telah diimplementasikan.

## Status Proyek

- Tahap: Phase 01 Foundation done
- Implementasi aplikasi: P01-01 sampai P01-11 selesai; Phase 02 menjadi tahap berikutnya
- Target awal: MVP POS vertical slice

## Cara Membaca

1. [Vision and Scope](product/vision-and-scope.md)
2. [Business Rules](product/business-rules.md)
3. [Architecture Overview](architecture/overview.md)
4. [Web Admin Conventions](architecture/web-admin-conventions.md)
5. [Development Conventions](architecture/development-conventions.md)
6. [Architecture Decisions](architecture/decisions/README.md)
7. [Development Roadmap](roadmap/README.md)
8. [Execution Plans](execution/README.md)
9. [Module Map](modules/README.md)
10. [Operational Runbooks](runbooks/)
11. [Testing Documentation](testing/README.md)

## Struktur Dokumentasi

| Area | Tujuan |
|---|---|
| `product/` | Visi, scope, aktor, dan aturan bisnis |
| `architecture/` | Arsitektur, konvensi teknis, dan keputusan lintas modul |
| `architecture/decisions/` | Architecture Decision Records (ADR) |
| `api/` | Kontrak OpenAPI untuk endpoint REST yang sudah tersedia |
| `modules/` | Batas dan tanggung jawab setiap domain module |
| `roadmap/` | Urutan delivery, acceptance criteria, dan Definition of Done |
| `execution/` | Work package implementasi, dependency, verifikasi, dan evidence |
| `runbooks/` | Prosedur operasional terkontrol untuk bootstrap, recovery, dan deployment |
| `testing/` | Manual test case, checklist QA, dan alur verifikasi end-to-end |
| `archive/` | Dokumen versi lama untuk referensi historis |

## Aturan Perubahan

- Keputusan arsitektur harus dikonfirmasi oleh product owner sebelum berstatus `Accepted`.
- Pilihan yang belum dikonfirmasi harus ditulis sebagai `Proposed` atau `Open`.
- Roadmap tidak boleh menduplikasi aturan bisnis secara rinci; gunakan tautan ke dokumen modul.
- Execution plan memecah phase per capability, bukan per file atau technical task kecil.
- Work package harus berhenti dan meminta keputusan product owner jika menemukan keputusan arsitektur baru.
- Checkbox hanya ditandai selesai setelah acceptance criteria dan Definition of Done terpenuhi.
- Implementasi, migrasi database, dan kode aplikasi tidak dilakukan hanya karena tercantum di dokumentasi.

## Dokumen Lama

- [Development Todolist v1](archive/development-todolist-v1.md)
- [POS F&B Development Plan v1](archive/pos-fnb-development-plan-v1.md)
