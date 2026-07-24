# P01-04 — Tenant Provisioning

Status: **Planned**

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
- ADR: [016](../../architecture/decisions/016-controlled-tenant-provisioning.md), [017](../../architecture/decisions/017-platform-admin-web-and-emergency-cli.md), [018](../../architecture/decisions/018-separate-platform-identity.md), [020](../../architecture/decisions/020-single-tenant-user-membership.md), [021](../../architecture/decisions/021-predefined-mvp-roles.md)
- Acceptance criteria: AC-12–AC-14, AC-18

## Use Cases and Invariants

- ProvisionTenant.
- DisableTenant.
- Provisioning berhasil seluruhnya atau rollback seluruhnya.
- Retry tidak membuat tenant atau owner duplikat.
- Platform actor dicatat sebagai actor; tenant sebagai target.
- Tidak ada public entry point.

## Implementation Checklist

- [ ] Definisikan input/output dan stable failures provisioning.
- [ ] Implementasikan orchestration melalui application use case.
- [ ] Terapkan transaction dan idempotency.
- [ ] Hubungkan Web dan CLI ke use case yang sama.
- [ ] Terapkan recent confirmation untuk mutation sensitif.
- [ ] Catat audit actor, target, outcome, reason, dan correlation ID.
- [ ] Tambahkan rollback, retry, dan authorization tests.

## Verification and Evidence

- Simulasi failure setiap tahap membuktikan tidak ada partial tenant.
- Retry input yang sama tidak menghasilkan duplikasi.
- Tenant user dan unauthenticated request tidak dapat memprovisikan tenant.
- Evidence transaction, audit, dan demo provisioning dicatat.

## Architecture Check

Berhenti dan tanyakan product owner jika diperlukan definisi idempotency key baru, lifecycle tenant selain active/disabled, invitation, billing, atau perubahan data awal tenant.

