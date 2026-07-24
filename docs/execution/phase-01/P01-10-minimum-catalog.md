# P01-10 — Minimum Catalog

Status: **Planned**

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

- [ ] Implementasikan category use cases dan Web adapter.
- [ ] Implementasikan product use cases dan Web adapter.
- [ ] Implementasikan outlet availability.
- [ ] Implementasikan catalog read API.
- [ ] Terapkan `_minor`, currency, dan decimal serialization rules.
- [ ] Tambahkan authorization, validation, isolation, dan contract tests.

## Verification and Evidence

- Duplicate tenant SKU dan cross-tenant reference ditolak.
- Product inactive/unavailable tidak muncul di Flutter.
- Monetary value keluar sebagai integer minor unit dengan currency eksplisit.
- Evidence Web/API demo dan test matrix dicatat.

## Architecture Check

Berhenti dan tanyakan product owner jika dibutuhkan aturan SKU global, multi-currency conversion, tax inclusion, price history, soft delete, variant, modifier, atau catalog hierarchy tambahan.

