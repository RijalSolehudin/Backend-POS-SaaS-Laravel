# Phase 04: Catalog and Pricing Expansion

Status: **Ready**

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
