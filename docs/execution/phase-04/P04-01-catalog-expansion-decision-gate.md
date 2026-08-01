# P04-01 — Catalog Expansion Decision Gate

Status: **Done**

## Outcome

Phase 04 memiliki keputusan arsitektur yang cukup untuk memperluas catalog tanpa merusak transaksi historis, pricing deterministik, atau boundary Sales/Catalog.

## Scope

- Tentukan product versus sellable item/SKU model.
- Tentukan variant pricing dan snapshot model.
- Tentukan modifier group, modifier option, min/max selection, required/optional semantics, dan pricing effect.
- Tentukan outlet availability, outlet price override, dan future price scheduling policy.
- Tentukan batas Phase 04 untuk tax/service category linkage.
- Tentukan apa yang tetap ditunda: combo/bundle stock semantics, recipe ownership, inventory deduction, promotion engine, dan external tax compliance.
- Catat keputusan sebagai ADR Phase 04.

## Out of Scope

- Migration atau endpoint catalog baru.
- Implementasi order item modifier/variant.
- Import/export massal.
- Inventory, recipe, kitchen, dan payment gateway integration.

## Dependencies

- Phase 03 selesai.
- Current Catalog MVP dari P01-10 tersedia.
- Current Sales order snapshot dari Phase 02/03 tersedia.

## Acceptance Criteria

- ADR Phase 04 berstatus `Accepted`.
- Product owner menyetujui apakah variant menjadi sellable unit tersendiri atau konfigurasi di bawah product.
- Product owner menyetujui modifier pricing dan selection rules.
- Historical order snapshot rule terdokumentasi.
- Stop rule untuk combo, inventory, recipe, promotion, dan tax/service compliance terdokumentasi.

## Verification

- ADR baru ditambahkan ke `docs/architecture/decisions`.
- Roadmap Phase 04 dapat berubah dari `Not Started` ke `Ready` setelah ADR diterima.
- Work package P04-02 dapat berubah ke `Ready`.

## Delivered

- [ADR-039 Catalog Expansion MVP Policy](../../architecture/decisions/039-catalog-expansion-mvp-policy.md) accepted.
- Product versus sellable variant, category hierarchy, modifier group/option rules, outlet availability/price override, and order item snapshot policy diputuskan.
- Combo/bundle, inventory deduction, recipe costing, scheduled pricing, promotion engine, tax/service compliance, dan multi-currency ditunda.

## Evidence

- Laravel Boost SearchDocs digunakan untuk Laravel 13 migration, enum cast, dan JSON API testing guidance.
- `composer quality`

## Architecture Stop Rule

Berhenti dan tanyakan product owner jika keputusan membutuhkan inventory ledger, recipe costing, combo stock deduction, tax compliance, atau promotion engine.
