# P02-09 — POS Core Readiness

Status: **Done**

## Outcome

Phase 02 memenuhi acceptance scenario end-to-end dan siap demo product owner.

## Scope

- End-to-end automated scenario.
- Cross-tenant/outlet/device/shift isolation matrix.
- Idempotency/retry matrix.
- Totals consistency matrix.
- OpenAPI and runbook update.

## Verification

- `composer quality` lulus pada MariaDB.
- `npm run build` lulus.
- Demo path cashier login sampai close shift terverifikasi.

## Readiness Matrix

### End-to-End Demo Path

- POS login with registered device.
- Browse available outlet catalog.
- Open shift.
- Create draft order with idempotency retry.
- Add catalog item and verify deterministic totals.
- Complete order with exact cash payment and idempotency retry.
- Generate receipt snapshot.
- Close shift.
- Verify shift summary and Tenant Admin daily sales summary.
- Verify closed shift rejects new order creation.

### Isolation Matrix

- POS token bound to one outlet cannot operate another outlet, even in the same tenant.
- POS token bound to one tenant cannot read another tenant outlet catalog.
- Tenant Admin user cannot open another tenant sales summary.

### Idempotency Matrix

- Retry create draft order returns the same order.
- Retry payment completion does not duplicate payment.
- Cancel/void flows have dedicated idempotency coverage in P02-08.

### Totals Consistency Matrix

- Order total equals payment amount.
- Receipt order/payment totals equal completed order/payment.
- Shift gross sales and recorded payments equal completed non-voided order/payment totals.
- Daily sales summary matches completed non-voided orders and recorded non-voided payments.

## Evidence

- `php artisan test tests/Feature/Sales/PosCoreReadinessTest.php` — 2 passed, 43 assertions.
- `composer quality` — required final quality gate.
- `npm run build` — required final frontend build gate.
