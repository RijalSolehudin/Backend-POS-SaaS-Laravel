# P02-03 — Shift Lifecycle

Status: **Done**

## Outcome

Cashier dapat membuka dan menutup shift pada outlet yang valid, dan order hanya dapat dibuat pada shift aktif.

## Scope

- Open shift API.
- Current shift API.
- Close shift API with basic cash count input.
- One open shift per user-outlet invariant.
- Tenant/outlet/device authorization.

## Verification

- Duplicate open shift ditolak dengan `SHIFT_ALREADY_OPEN`.
- Closed shift tidak dapat ditutup lagi dan menghasilkan `SHIFT_NOT_OPEN`.
- Cross-outlet/token mismatch ditolak dengan `OUTLET_NOT_FOUND`.
- Current shift mengembalikan shift open atau `data: null` ketika tidak ada shift aktif.
- Automated evidence: `php artisan test tests/Feature/Sales/ShiftLifecycleTest.php` lulus 4 test / 33 assertion.
- Quality evidence: `composer quality` lulus composer validate, Pint, PHPStan, Deptrac 0 violation, unit 11 test / 37 assertion, feature 69 test / 663 assertion.
- Build evidence: `npm run build` lulus.
