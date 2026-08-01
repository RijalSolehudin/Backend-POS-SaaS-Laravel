# P02-07 — Close Shift and Summary

Status: **Done**

## Outcome

Cashier dapat menutup shift dan Tenant Admin dapat melihat summary shift/daily minimum.

## Scope

- Close shift snapshot.
- Shift summary API.
- Web Admin daily sales summary.

## Verification

- Summary cocok dengan completed non-voided orders.
- Closed shift tidak menerima transaksi baru.

## Implementation Notes

- Close shift menyimpan snapshot `expected_cash_minor` dan `gross_sales_minor` yang dihitung ulang dari completed orders dan recorded payments.
- Shift summary API tersedia di `GET /api/v1/pos/outlets/{outlet}/shifts/{shift}/summary`.
- Tenant Admin daily sales summary tersedia di `GET /admin/tenants/{tenant}/sales/daily?date=YYYY-MM-DD`.
- Summary minimum memuat completed order count, gross sales, recorded payment total, cash/manual non-cash split, expected cash, closing cash, dan cash variance.

## Evidence

- `php artisan test tests/Feature/Sales/ShiftSummaryTest.php` — 1 passed, 30 assertions.
