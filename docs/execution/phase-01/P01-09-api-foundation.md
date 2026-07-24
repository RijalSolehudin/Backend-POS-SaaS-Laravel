# P01-09 — Flutter API Foundation

Status: **Planned**

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

- [ ] Implementasikan login/logout adapter untuk use case token.
- [ ] Kaitkan token ke device dan enforce expiry/revocation.
- [ ] Implementasikan immutable outlet request context.
- [ ] Standardisasi success, validation, auth, dan domain failures.
- [ ] Tambahkan request ID dan safe logging/error handling.
- [ ] Dokumentasikan endpoint dalam OpenAPI.
- [ ] Tambahkan contract, auth, lifecycle, dan isolation tests.

## Verification and Evidence

- Plain token hanya terlihat saat issuance dan tidak masuk log.
- Expired, replaced, revoked, disabled, dan wrong-outlet token ditolak.
- Response sesuai content type, envelope, stable code, dan request ID.
- Evidence contract tests dan OpenAPI validation dicatat.

## Architecture Check

Berhenti dan tanyakan product owner jika muncul kebutuhan refresh token, token abilities baru, idempotency convention API, pagination/filter contract baru, atau re-authentication POS.

