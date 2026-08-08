# Phase 07 Dining Kitchen Readiness

## Scope

Phase 07 covers Dining floor/table management, table sessions, Kitchen station routing, ticket lifecycle, KDS realtime notification with snapshot fallback, and best-effort printer jobs.

## Verification Checklist

- Run `docker compose up -d mariadb-testing`.
- Run `composer quality`.
- Run focused Phase 07 suites:

```shell
php artisan test tests/Feature/Dining/DiningFloorTableFoundationTest.php tests/Feature/Dining/TableSessionLifecycleTest.php tests/Feature/Kitchen/KitchenPhaseSevenTest.php
```

- Confirm `php artisan route:list --name=api.v1.pos.outlets.kds.snapshot` shows the KDS snapshot route.
- Confirm `php artisan channel:list` shows `tenant.{tenantId}.outlet.{outletId}.kds`.

## Operational Notes

- Table occupancy source of truth is `dining_table_sessions.open_table_key`.
- Table transfer keeps the session `open` and records `previous_table_id`.
- Table merge moves linked orders to the target open session and marks the source session `merged`.
- Kitchen ticket creation is idempotent per tenant/outlet/order/station and order item/ticket.
- Missing kitchen routing does not block Sales orderability; use the routing exception action/report to find unrouted items.
- KDS events are notification only. Clients must call the snapshot endpoint after reconnect.
- Printer jobs are append-only. Retry and reprint create new jobs; printer failure does not mutate Sales order/payment state.

## Current Evidence

- `composer quality:static` passed.
- `composer test:unit` passed.
- Focused Phase 07 feature suites are authored but require MariaDB testing on `127.0.0.1:33067`.
