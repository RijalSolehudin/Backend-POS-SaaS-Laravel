# Development Roadmap

Roadmap menggunakan vertical slices. Setiap phase harus menghasilkan capability yang dapat diverifikasi, bukan sekadar kumpulan migration/model/controller.

## Status Legend

- `Not Started`: belum memenuhi entry criteria.
- `Ready`: seluruh keputusan wajib telah disetujui.
- `In Progress`: implementasi sedang berjalan.
- `Blocked`: ada dependency atau keputusan yang menghalangi.
- `Done`: acceptance criteria dan Definition of Done terpenuhi.

## Roadmap

| Phase | Outcome | Status |
|---|---|---|
| [01](phase-01-foundation.md) | Foundation dan architectural guardrails | Done |
| [02](phase-02-pos-core.md) | MVP POS vertical slice end-to-end | Done |
| [03](phase-03-operational-safety.md) | Reliability, audit, dan operational safety | Ready |
| [04](phase-04-catalog-expansion.md) | Catalog, variants, modifiers, dan pricing lanjutan | Not Started |
| [05](phase-05-inventory.md) | Inventory ledger dan stock operations | Not Started |
| [06](phase-06-recipe-procurement.md) | Recipe costing dan procurement | Not Started |
| [07](phase-07-dining-kitchen.md) | Table service, kitchen, dan printer | Not Started |
| [08](phase-08-growth.md) | QR order, payment gateway, dan growth features | Not Started |
| [09](phase-09-offline-scale.md) | Offline sync, scale, dan production maturity | Not Started |

## Architecture Decision Gate

Sebuah phase tidak boleh berubah menjadi `Ready` apabila keputusan arsitektur yang memengaruhinya masih `Open`. Keputusan harus:

1. Diajukan dengan context dan trade-off.
2. Dikonfirmasi product owner.
3. Dicatat sebagai ADR `Accepted`.
4. Direfleksikan pada acceptance criteria terkait.

## Definition of Done untuk Setiap Phase

- Keputusan arsitektur phase telah diterima.
- Capability memenuhi acceptance criteria bisnis.
- Tenant isolation dan authorization telah diuji.
- Happy path, validation failure, dan critical failure path memiliki automated tests.
- Mutation finansial/stock memiliki transaction, idempotency, locking, dan retry behavior yang terdefinisi bila relevan.
- Audit metadata tersedia untuk aksi sensitif.
- API contract dan module documentation diperbarui.
- Tidak ada unresolved critical security issue.
- Observability minimum untuk failure kritis tersedia.
- Product owner menerima hasil berdasarkan demonstrasi alur, bukan keberadaan file/class.

## Scope Control

- Fitur baru masuk ke module backlog terlebih dahulu.
- Fitur tidak ditambahkan ke phase aktif tanpa menilai dampak pada acceptance criteria dan timeline.
- Optional feature tidak boleh menjadi dependency tersembunyi MVP.
- Estimasi dibuat setelah open decisions dan acceptance criteria phase diselesaikan.
