# Phase 04: Catalog and Pricing Expansion

Status: **Done**

## Outcome

Catalog mendukung kebutuhan menu F&B setelah transaksi sederhana stabil.

## Candidate Scope

- Category hierarchy dan ordering.
- Product variants dan modifier groups.
- Outlet-specific availability dan pricing.
- Combo/bundle setelah semantics disetujui.
- Image dan import/export.
- Tax/service category linkage.

## Architecture Decisions Required

- Accepted decision: [ADR-039 Catalog Expansion MVP Policy](../architecture/decisions/039-catalog-expansion-mvp-policy.md).
- Combo pricing dan stock semantics ditunda.
- Recipe ownership boundary ditunda ke phase inventory/recipe.
- Scheduled pricing ditunda; Phase 04 hanya base price dan outlet override.

## Acceptance Criteria

- Konfigurasi product menghasilkan pilihan POS yang valid dan deterministik.
- Order snapshot merekam seluruh pilihan yang memengaruhi harga.
- Perubahan catalog tidak mengubah transaksi historis.
- Availability dan price tidak bocor lintas outlet/tenant.

## Delivered

- Category hierarchy dan display ordering.
- Product variants untuk pilihan jual POS.
- Modifier groups/options dengan required, optional, min, dan max selection.
- Outlet-specific availability dan price override untuk product, variant, dan modifier option.
- Order item snapshot untuk variant/modifier selection dan receipt historis.
- Catalog export read-only dan import dry-run baseline.
- Readiness checklist: [Phase 04 Catalog Readiness](../runbooks/phase-04-catalog-readiness.md).

## Deferred Scope

- Combo/bundle.
- Recipe ownership dan inventory deduction.
- Scheduled pricing.
- Promotion engine.
- Tax/service calculation.
- Catalog image/media management.
