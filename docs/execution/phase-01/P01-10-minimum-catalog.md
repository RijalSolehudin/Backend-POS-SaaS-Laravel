# P01-10 — Minimum Catalog

Status: **Done**

## Outcome

Tenant actor berwenang dapat mengelola category dan product sederhana melalui Web Admin, dan Flutter hanya membaca catalog aktif yang tersedia pada outlet context.

## Scope

- Category create/update/activate/deactivate.
- Product minimum: name, tenant-unique SKU, category, price minor, currency, active status, dan outlet availability.
- Tenant Admin management UI.
- Flutter catalog read API.
- Tenant/outlet isolation dan numeric/API conventions.

## Out of Scope

- Variant, modifier, combo, recipe, inventory, tax engine, promotion, dan advanced pricing.
- Product import/export.

## Dependencies

- P01-06 Tenancy and Outlets.
- P01-07 Predefined RBAC.
- P01-09 Flutter API Foundation.

## References

- Module: [Catalog](../../modules/catalog.md)
- ADR: [010](../../architecture/decisions/010-money-and-rounding.md), [011](../../architecture/decisions/011-tenant-outlet-request-context.md), [014](../../architecture/decisions/014-web-admin-and-flutter-presentations.md), [015](../../architecture/decisions/015-blade-first-vue-by-exception.md)
- Acceptance criteria: AC-03, AC-31–AC-33

## Use Cases and Invariants

- CreateCategory, UpdateCategory, ChangeCategoryStatus.
- CreateProduct, UpdateProduct, ChangeProductStatus, SetOutletAvailability.
- ListAvailableOutletCatalog.
- SKU unik dalam tenant.
- Category, product, dan outlet reference wajib berasal dari tenant yang sama.
- Flutter hanya menerima product aktif dan tersedia.

## Implementation Checklist

- [x] Implementasikan category use cases dan Web adapter.
- [x] Implementasikan product use cases dan Web adapter.
- [x] Implementasikan outlet availability.
- [x] Implementasikan catalog read API.
- [x] Terapkan `_minor`, currency, dan decimal serialization rules.
- [x] Tambahkan authorization, validation, isolation, dan contract tests.

## Verification and Evidence

- Duplicate tenant SKU ditolak dengan validation error `sku`.
- Cross-tenant category reference ditolak dengan validation error `category_id`.
- Product inactive/unavailable tidak muncul di Flutter catalog API.
- Monetary value keluar sebagai integer `price_minor` dan currency eksplisit.
- Web Admin endpoint tersedia di `admin/tenants/{tenant}/catalog`.
- Flutter endpoint tersedia di `GET /api/v1/pos/outlets/{outlet}/catalog`.
- Automated evidence: `php artisan test tests/Feature/Catalog/MinimumCatalogTest.php` lulus 4 test / 26 assertion.
- Quality evidence: `composer quality` lulus composer validate, Pint, PHPStan, Deptrac 0 violation, unit 11 test / 37 assertion, feature 57 test / 414 assertion.
- Build evidence: `npm run build` lulus.

## Architecture Check

Berhenti dan tanyakan product owner jika dibutuhkan aturan SKU global, multi-currency conversion, tax inclusion, price history, soft delete, variant, modifier, atau catalog hierarchy tambahan.
