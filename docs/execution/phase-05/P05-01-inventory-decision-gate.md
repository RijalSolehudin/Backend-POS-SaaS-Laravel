# P05-01 — Inventory Decision Gate

Status: **Done**

## Outcome

Phase 05 memiliki keputusan arsitektur yang cukup untuk membangun inventory ledger tanpa membuat balance tidak dapat diaudit.

## Scope

- Tentukan item inventory versus product catalog boundary.
- Tentukan unit, unit conversion, dan decimal precision.
- Tentukan negative stock policy.
- Tentukan costing method minimum untuk valuation.
- Tentukan batch/expiry scope Phase 05.
- Tentukan adjustment, waste, dan transfer approval lifecycle.
- Tentukan idempotency, locking, dan retry policy untuk stock mutation.
- Tegaskan bahwa recipe auto-deduction dari Sales ditunda sampai Phase 06.
- Catat keputusan sebagai ADR Phase 05.

## Out of Scope

- Migration atau endpoint inventory.
- Auto-deduct stock dari order completed.
- Recipe/BOM dan costing recipe.
- Procurement/supplier workflow.
- Accounting journal.

## Dependencies

- Phase 04 selesai.
- Catalog expanded tersedia untuk menjadi referensi bisnis, tetapi belum otomatis mengurangi stock.
- Operational safety Phase 03 tersedia untuk approval/audit pattern.

## Acceptance Criteria

- ADR Phase 05 berstatus `Accepted`.
- Product owner menyetujui apakah negative stock ditolak atau diizinkan dengan audit.
- Product owner menyetujui unit precision dan conversion scope.
- Product owner menyetujui costing method minimum.
- Product owner menyetujui transfer lifecycle antar outlet.
- Stop rule untuk recipe, procurement, batch/expiry, dan accounting terdokumentasi.

## Verification

- ADR baru ditambahkan ke `docs/architecture/decisions`.
- Roadmap Phase 05 dapat berubah dari `Not Started` ke `Ready` setelah ADR diterima.
- Work package P05-02 dapat berubah ke `Ready`.

## Delivered

- [ADR-040 Inventory Ledger MVP Policy](../../architecture/decisions/040-inventory-ledger-mvp-policy.md) accepted.
- Inventory item/unit boundary, negative stock policy, base unit precision, moving average costing, transfer lifecycle, approval policy, idempotency, locking, dan recovery policy diputuskan.
- Batch/expiry, recipe auto-deduction, procurement, FIFO/landed cost, unit conversion kompleks, dan accounting journal ditunda.
- Roadmap Phase 05 berubah ke `Ready`.
- P05-02 berubah ke `Ready`.

## Evidence

- Laravel Boost ApplicationInfo digunakan untuk memverifikasi stack Laravel aktif.
- `composer quality`

## Architecture Stop Rule

Berhenti jika keputusan membutuhkan recipe ownership, supplier purchasing, accounting valuation journal, atau batch/expiry compliance yang belum disetujui.
