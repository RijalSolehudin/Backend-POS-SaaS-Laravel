# P01-09 — Flutter API Foundation

Status: **Done**

## Outcome

Flutter POS pada registered device dapat memperoleh Sanctum token dan mengakses resource outlet melalui API v1 yang konsisten dan terisolasi.

## Scope

- Registered-device login dan logout.
- Sanctum personal access token per user-device, maksimal 30 hari, tanpa refresh token.
- Explicit outlet URL context.
- API success envelope dan RFC 9457 error format.
- Stable error code, trace/request ID, validation shape, dan safe production errors.
- OpenAPI baseline untuk endpoint Phase 01.

## Out of Scope

- Idle app lock.
- Re-authentication untuk sensitive POS action.
- Offline synchronization.
- Order, shift, atau payment API.

## Dependencies

- P01-05 Tenant Identity.
- P01-06 Tenancy and Outlets.
- P01-08 POS Device Registry.

## References

- ADR: [007](../../architecture/decisions/007-sanctum-api-token-authentication.md), [008](../../architecture/decisions/008-sanctum-token-lifecycle.md), [011](../../architecture/decisions/011-tenant-outlet-request-context.md), [012](../../architecture/decisions/012-api-response-errors-versioning.md), [013](../../architecture/decisions/013-pos-device-registration.md)
- Acceptance criteria: AC-23–AC-30, AC-39

## Use Cases and Invariants

- IssuePosToken dan RevokeCurrentPosToken.
- Token hanya diterbitkan setelah user, assignment, tenant, outlet, dan device valid.
- Token dikaitkan ke device dan mengganti token lama user-device.
- Backend menurunkan tenant dari outlet route.
- Body/header context tidak menjadi authorization source.

## Implementation Checklist

- [x] Implementasikan login/logout adapter untuk use case token.
- [x] Kaitkan token ke device dan enforce expiry/revocation.
- [x] Implementasikan immutable outlet request context.
- [x] Standardisasi success, validation, auth, dan domain failures.
- [x] Tambahkan request ID dan safe logging/error handling.
- [x] Dokumentasikan endpoint dalam OpenAPI.
- [x] Tambahkan contract, auth, lifecycle, dan isolation tests.

## Verification and Evidence

- Plain token hanya dikembalikan pada `POST /api/v1/pos/auth/login`; persistent token tetap hashed pada Sanctum.
- Login ulang user-device mengganti token lama; logout menghapus current token; device revoked/reassigned tetap mencabut token lewat P01-08 lifecycle.
- Wrong-outlet token menghasilkan `OUTLET_NOT_FOUND`; unassigned cashier menghasilkan `TENANCY_FORBIDDEN`.
- Response sukses memakai envelope `data`; error memakai `application/problem+json`, stable `code`, dan `X-Request-ID`/`trace_id`.
- Automated evidence: `php artisan test tests/Feature/Tenancy/FlutterApiFoundationTest.php` lulus 7 test / 44 assertion.
- Quality evidence: `composer quality` lulus composer validate, Pint, PHPStan, Deptrac 0 violation, unit 11 test / 37 assertion, feature 53 test / 388 assertion.
- OpenAPI baseline: [docs/api/openapi.yaml](../../api/openapi.yaml).

## Architecture Check

Berhenti dan tanyakan product owner jika muncul kebutuhan refresh token, token abilities baru, idempotency convention API, pagination/filter contract baru, atau re-authentication POS.
