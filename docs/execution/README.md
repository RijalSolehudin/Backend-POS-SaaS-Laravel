# Execution Plans

Dokumen pada area ini menerjemahkan outcome dan acceptance criteria roadmap menjadi work package yang dapat dieksekusi dan ditinjau. Dokumen execution tidak menggantikan roadmap, ADR, atau dokumentasi modul.

## Aturan

- Satu work package mewakili capability atau hasil yang dapat diverifikasi, bukan satu file, class, migration, atau endpoint.
- Implementasi hanya boleh dimulai ketika work package berstatus `Ready` dan ada instruksi implementasi terpisah dari product owner.
- Detail implementasi harus mengikuti ADR yang telah berstatus `Accepted`.
- Jika pelaksanaan menemukan keputusan baru tentang data, ownership, boundary modul, authentication, authorization, security, transaksi, atau kontrak eksternal, pekerjaan berhenti pada titik tersebut.
- Keputusan baru harus dibahas dengan product owner dan dicatat sebagai ADR sebelum pekerjaan dilanjutkan.
- Checklist implementasi bukan bukti selesai. Status `Done` membutuhkan acceptance criteria, verifikasi, dan evidence.

## Status

```text
Planned -> Ready -> In Progress -> In Review -> Done
                 \-> Blocked
```

- `Planned`: scope awal sudah ditulis, tetapi readiness belum disetujui.
- `Ready`: dependency dan keputusan sudah lengkap serta product owner mengizinkan work package masuk antrean implementasi.
- `In Progress`: implementasi sedang dikerjakan.
- `In Review`: implementasi selesai dan sedang diverifikasi.
- `Done`: seluruh acceptance criteria terkait lulus dan evidence dicatat.
- `Blocked`: ada dependency atau keputusan yang menghentikan pekerjaan.

## Daftar Phase

- [Phase 01 — Foundation](phase-01/README.md)
- [Phase 02 — POS Core Vertical Slice](phase-02/README.md)
