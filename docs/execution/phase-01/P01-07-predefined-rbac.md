# P01-07 — Predefined RBAC

Status: **Done**

## Outcome

Tenant Owner dapat mengelola assignment tiga predefined role dengan permission server-side yang konsisten pada Tenant Admin dan API.

## Scope

- Tenant Owner, Outlet Manager, dan Cashier.
- Role assignment/removal dalam tenant dan outlet context yang sah.
- Shared authorization policy/capability mapping.
- Audit perubahan role.
- Dokumentasi role-permission matrix.

## Out of Scope

- Custom role builder.
- Editing permission definitions melalui UI.
- Platform authorization.

## Dependencies

- P01-05 Tenant Identity.
- P01-06 Tenancy and Outlets.

## References

- Module: [Identity](../../modules/identity.md)
- ADR: [020](../../architecture/decisions/020-single-tenant-user-membership.md), [021](../../architecture/decisions/021-predefined-mvp-roles.md)
- Acceptance criteria: AC-19–AC-21, AC-35, AC-39

## Use Cases and Invariants

- AssignPredefinedRole dan RemovePredefinedRole.
- Actor hanya mengubah assignment, bukan definisi role/permission.
- Cashier tidak memiliki administrative capability.
- Outlet-scoped authority tidak berlaku di luar outlet yang diizinkan.
- Semua authorization tetap ditegakkan server-side.

## Implementation Checklist

- [x] Dokumentasikan permission matrix untuk tiga role.
- [x] Implementasikan role assignment/removal use cases.
- [x] Terapkan policy yang sama pada Web dan API.
- [x] Cegah cross-tenant/outlet assignment.
- [x] Audit actor, target, perubahan, dan outcome.
- [x] Tambahkan positive/negative authorization tests.

## Verification and Evidence

- Permission matrix terdokumentasi di [Role Permission Matrix](../../architecture/role-permission-matrix.md).
- Identity menyediakan predefined permission enum, policy, dan role assignment repository contract.
- Tenancy mengorkestrasi `AssignPredefinedRole` dan `RemovePredefinedRole` dengan tenant membership validation, transaction, idempotent replay, dan tenancy audit.
- Tenant Admin menyediakan halaman `/admin/tenants/{tenant}/users` untuk assignment Tenant Owner, Outlet Manager, dan Cashier.
- Outlet mutation memakai server-side permission guard, bukan hanya UI/middleware visibility.
- Cross-tenant role assignment ditolak tanpa mutation.
- Cashier gagal melakukan administrative mutation pada use case boundary.
- `composer quality:static` lulus: Composer validate, Pint, Larastan/PHPStan, dan Deptrac tanpa violation.
- Feature suite lulus: 40 tests, 321 assertions pada MariaDB-backed test run.

## Architecture Check

Berhenti dan tanyakan product owner jika permission matrix belum menentukan suatu capability, muncul role keempat, custom permission, role inheritance, atau perubahan scope role.
