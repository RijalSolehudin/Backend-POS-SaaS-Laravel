# P02-02 — Sales Module Foundation

Status: **Done**

## Outcome

Sales module mempunyai schema, domain model, dan application foundation untuk shift/order/payment vertical slice tanpa membuka mutation workflow penuh.

## Scope

- Sales module service provider, migrations, route skeleton, and Deptrac boundary.
- Core tables: shifts, orders, order items, idempotency records, order number counters.
- Shared value objects/data objects untuk money totals dan request context.
- Baseline API error/idempotency helpers bila disetujui ADR-037.

## Out of Scope

- Complete order/payment.
- Receipt.
- Summary/reporting.

## Dependencies

- P02-01.
- Phase 01 API, Catalog, Tenancy, RBAC, and device context.

## Implementation Checklist

- [x] Buat Sales module physical structure.
- [x] Tambahkan migration dengan ULID MariaDB conventions.
- [x] Tambahkan enum lifecycle dari ADR-037.
- [x] Tambahkan idempotency foundation.
- [x] Tambahkan static and migration tests.

## Verification and Evidence

- Sales module registered via `SalesServiceProvider`.
- Migration creates `sales_shifts`, `sales_order_number_counters`, `sales_orders`, `sales_order_items`, `sales_payments`, and `sales_idempotency_records`.
- Lifecycle enums match ADR-037: shift `open/closed/voided`, order `draft/completed/cancelled/voided`, payment method `cash/manual_non_cash`, payment status `recorded/voided`.
- Database backstops exist for open shift key, order number scope, order counter scope, and idempotency scope.
- Automated evidence: `php artisan test tests/Feature/Sales/SalesModuleFoundationTest.php` lulus 3 test / 105 assertion.
- Quality evidence: `composer quality` lulus composer validate, Pint, PHPStan, Deptrac 0 violation, unit 11 test / 37 assertion, feature 65 test / 630 assertion.
- Build evidence: `npm run build` lulus.
