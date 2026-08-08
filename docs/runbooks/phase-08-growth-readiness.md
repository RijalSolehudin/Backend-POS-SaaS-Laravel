# Phase 08 Growth Readiness

## Scope

Phase 08 covers QR self-order sessions, customer carts with staff confirmation, waiter order entry, payment gateway abstraction/webhook inbox, reservation minimum, single-promotion discount snapshots, and growth analytics export.

## Verification Checklist

- Run `docker compose up -d mariadb-testing`.
- Run `composer quality`.
- Run focused growth tests:

```shell
php artisan test tests/Feature/Growth/GrowthPhaseEightTest.php
```

- Confirm public QR route:

```shell
php artisan route:list --name=api.v1.qr.show
```

## Operational Notes

- QR tokens are opaque random values signed with HMAC; only token hashes are stored.
- QR/customer carts create pending `ordering_order_requests`; staff confirmation is required before writing Sales order items.
- Waiter workflow uses the existing Sales draft order and item actions.
- Payment provider webhook processing requires a valid provider signature and stores unique provider/event ids for replay safety.
- Gateway payloads and analytics filters redact card data. Customer phone is redacted in analytics export filters.
- Reservation stores only minimum customer identity fields and can link to an open table session when seated.
- Promotion MVP allows exactly one discount snapshot per order and rejects stacking.

## Current Evidence

- `composer quality:static` passed.
- `composer test:unit` passed.
- Focused Phase 08 feature tests are authored but require MariaDB testing on `127.0.0.1:33067`.
