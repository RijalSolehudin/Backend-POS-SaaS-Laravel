# P01-04 — Tenant Provisioning

Status: **In Review**

## Outcome

Platform Administrator dapat memprovisikan tenant, outlet awal, dan Tenant Owner secara atomik, idempotent, aman, dan dapat diaudit.

## Scope

- Provisioning use case yang dipakai Platform Admin Web dan emergency/controlled CLI.
- Tenant, initial outlet, Tenant Owner, ownership, role awal, currency, dan timezone minimum.
- Validation, authorization, transaction, rollback, idempotency, dan audit.
- Disable tenant sebagai sensitive platform action minimum.

## Out of Scope

- Public registration.
- Self-service onboarding.
- Email invitation lifecycle.
- Platform user menjadi tenant member.

## Dependencies

- P01-02 Platform Identity.
- P01-03 Platform Admin Shell.

## References

- Modules: [Platform Identity](../../modules/platform-identity.md), [Tenancy](../../modules/tenancy.md), [Identity](../../modules/identity.md)
- ADR: [016](../../architecture/decisions/016-controlled-tenant-provisioning.md), [017](../../architecture/decisions/017-platform-admin-web-and-emergency-cli.md), [018](../../architecture/decisions/018-separate-platform-identity.md), [020](../../architecture/decisions/020-single-tenant-user-membership.md), [021](../../architecture/decisions/021-predefined-mvp-roles.md), [034](../../architecture/decisions/034-tenant-provisioning-implementation-policy.md)
- Acceptance criteria: AC-12–AC-14, AC-18

## Use Cases and Invariants

- ProvisionTenant.
- DisableTenant.
- Provisioning berhasil seluruhnya atau rollback seluruhnya.
- Retry tidak membuat tenant atau owner duplikat.
- Platform actor dicatat sebagai actor; tenant sebagai target.
- Tidak ada public entry point.

## Implementation Checklist

- [x] Definisikan typed input/output dan stable failures provisioning.
- [x] Implementasikan orchestration Tenancy melalui published Identity application contract.
- [x] Terapkan transaction, unique constraints, lowercase ULID idempotency key, dan HMAC input fingerprint.
- [x] Hubungkan Platform Admin Web dan controlled interactive CLI ke use case yang sama.
- [x] Terapkan recent confirmation untuk create form, provisioning mutation, dan disable mutation.
- [x] Catat audit actor, target, outcome, reason, correlation ID, dan metadata non-secret.
- [x] Tambahkan rollback, replay/mismatch, duplicate, Web authorization, CLI, disable, dan MariaDB schema tests.

## Verification and Evidence

- ADR-034 menetapkan Tenancy sebagai orchestration owner dan Identity sebagai pemilik initial owner credential/role.
- Schema module-local tersedia untuk `users`, role assignment, tenant, outlet, membership, provisioning request, dan append-only tenancy audit.
- Initial owner password hanya diterima melalui Web password field atau hidden interactive CLI prompt; owner dibuat dengan `must_change_password`.
- Platform routes tersedia pada `/platform/tenants`; tidak ada public registration route/API.
- Static quality gate lulus: Pint, Larastan level 8, dan Deptrac tanpa violation.
- Unit suite lulus: 11 tests dan 37 assertions, termasuk HMAC provisioning fingerprint.
- Feature subset tanpa database lulus: 3 tests dan 18 assertions untuk safe platform boundary, middleware composition, serta non-interactive CLI rejection.
- Blade templates berhasil diprecompile dan Vite production build berhasil.
- MariaDB feature tests tersedia untuk complete state, retry, mismatch, duplicate email rollback, final-stage rollback, disable, Web recent confirmation, tenant-user rejection, CLI, dan ULID schema compatibility.
- MariaDB-backed suite belum dapat dijalankan karena test service pada `127.0.0.1:33067` tidak tersedia. P01-04 tetap `In Review` sampai fresh migration dan seluruh feature suite lulus pada MariaDB 11.4.

## Architecture Check

Keputusan implementasi P01-04 dikunci dalam ADR-034 berdasarkan delegasi product owner. Berhenti dan tanyakan product owner jika work package berikutnya memerlukan lifecycle selain active/disabled, invitation, billing, perubahan role cardinality, atau perubahan data awal tenant.
