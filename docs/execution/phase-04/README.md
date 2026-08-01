# Phase 04 Execution Plan

Status: **Planned**

Dokumen ini memecah [Phase 04 Catalog and Pricing Expansion](../../roadmap/phase-04-catalog-expansion.md) menjadi work package berbasis outcome.

## Required Decision Gate

Implementasi Phase 04 harus dimulai dari [P04-01 Catalog Expansion Decision Gate](P04-01-catalog-expansion-decision-gate.md). Product owner perlu menyetujui ADR untuk model sellable item, variant/modifier pricing, outlet override, dan snapshot sebelum migration/API baru dibuat.

## Urutan yang Direkomendasikan

| ID | Work package | Dependency utama | Status |
|---|---|---|---|
| P04-01 | [Catalog Expansion Decision Gate](P04-01-catalog-expansion-decision-gate.md) | Phase 03 | Ready |
| P04-02 | [Category Hierarchy and Display Ordering](P04-02-category-hierarchy-display-ordering.md) | P04-01 | Planned |
| P04-03 | [Sellable Variants](P04-03-sellable-variants.md) | P04-01 | Planned |
| P04-04 | [Modifier Groups and Options](P04-04-modifier-groups-options.md) | P04-03 | Planned |
| P04-05 | [Outlet Availability and Price Overrides](P04-05-outlet-availability-price-overrides.md) | P04-03, P04-04 | Planned |
| P04-06 | [Order Item Snapshot Integration](P04-06-order-item-snapshot-integration.md) | P04-03, P04-04, P04-05 | Planned |
| P04-07 | [Catalog Admin Operations and Import Baseline](P04-07-catalog-admin-operations-import-baseline.md) | P04-02..P04-05 | Planned |
| P04-08 | [Catalog Expansion Readiness](P04-08-catalog-expansion-readiness.md) | P04-02..P04-07 | Planned |

## Readiness Gate

Sebelum work package implementasi berubah menjadi `Ready`, pastikan:

- ADR Phase 04 berstatus `Accepted`;
- model `product`, `variant`, `sellable`, dan `modifier` sudah jelas;
- pricing calculation dan order snapshot sudah jelas;
- outlet availability/override tidak membuka kebocoran tenant/outlet;
- perubahan schema bersifat additive terhadap transaksi historis Phase 02/03;
- combo/bundle, recipe ownership, stock deduction, dan tax/service calculation yang belum disetujui tetap out of scope.

## Architecture Stop Rule

Berhenti dan tanyakan product owner jika ditemukan kebutuhan inventory deduction, recipe costing, combo/bundle stock semantics, multi-currency, scheduled pricing, promotion engine, accounting export, atau payment/tax compliance detail yang belum disetujui.
