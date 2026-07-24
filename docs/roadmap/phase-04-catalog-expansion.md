# Phase 04: Catalog and Pricing Expansion

Status: **Not Started**

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

- Variant/modifier pricing and snapshot model.
- Product versus sellable SKU model.
- Recipe ownership boundary.
- Combo pricing dan stock semantics.
- Price scheduling dan override policy.

## Acceptance Criteria

- Konfigurasi product menghasilkan pilihan POS yang valid dan deterministik.
- Order snapshot merekam seluruh pilihan yang memengaruhi harga.
- Perubahan catalog tidak mengubah transaksi historis.
- Availability dan price tidak bocor lintas outlet/tenant.

