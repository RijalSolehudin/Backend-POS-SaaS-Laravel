# P04-07 — Catalog Admin Operations and Import Baseline

Status: **Planned**

## Outcome

Tenant admin memiliki workflow operasional minimum untuk mengelola catalog expanded dan baseline import/export yang aman untuk pilot.

## Scope

- Perluas halaman/admin actions catalog agar category hierarchy, variant, modifier, availability, dan price override dapat dikelola.
- Tambahkan export CSV/JSON read-only untuk review catalog.
- Tambahkan import baseline bila disetujui: dry-run validation sebelum write.
- Dokumentasikan format import dan error handling.
- Pastikan audit event catalog mencatat actor, target, dan reason untuk perubahan massal.

## Out of Scope

- Rich media/image management.
- Spreadsheet sync.
- Background bulk import queue bila belum diperlukan.
- Approval workflow untuk catalog changes.

## Dependencies

- P04-02 selesai.
- P04-03 selesai.
- P04-04 selesai.
- P04-05 selesai.

## Acceptance Criteria

- Admin dapat mengelola expanded catalog tanpa direct database edit.
- Export catalog dapat dipakai review sebelum pilot.
- Import dry-run tidak menulis data dan mengembalikan error yang bisa ditindak.
- Import write bersifat tenant-scoped dan auditable bila masuk scope.
- Invalid row tidak membuat partial write tanpa laporan.

## Verification

- Feature tests untuk admin operations.
- Feature/console tests untuk export/import bila dibuat.
- `composer quality` lulus.
- `npm run build` lulus bila ada perubahan frontend.

## Architecture Stop Rule

Berhenti jika import membutuhkan queue, file storage policy, image pipeline, atau external spreadsheet integration.
