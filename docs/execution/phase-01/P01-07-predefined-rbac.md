# P01-07 — Predefined RBAC

Status: **Planned**

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

- [ ] Dokumentasikan permission matrix untuk tiga role.
- [ ] Implementasikan role assignment/removal use cases.
- [ ] Terapkan policy yang sama pada Web dan API.
- [ ] Cegah cross-tenant/outlet assignment.
- [ ] Audit actor, target, perubahan, dan outcome.
- [ ] Tambahkan positive/negative authorization tests.

## Verification and Evidence

- Setiap capability diuji untuk ketiga role.
- Cashier gagal melakukan seluruh administrative mutation.
- UI visibility bukan satu-satunya kontrol authorization.
- Evidence matrix dan hasil automated test dicatat.

## Architecture Check

Berhenti dan tanyakan product owner jika permission matrix belum menentukan suatu capability, muncul role keempat, custom permission, role inheritance, atau perubahan scope role.

