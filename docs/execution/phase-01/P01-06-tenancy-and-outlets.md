# P01-06 — Tenancy and Outlets

Status: **Planned**

## Outcome

Tenant Owner dapat mengelola outlet dan user assignment melalui Tenant Admin dengan request context dan isolation yang konsisten.

## Scope

- Tenant Admin shell pada `/admin/tenants/{tenant}/...`.
- Explicit tenant route context dan scoped child binding.
- Create, update, disable outlet.
- Assign/unassign tenant user ke banyak outlet dalam tenant yang sama.
- Tenant/outlet navigation context sebagai preference, bukan authorization source.

## Out of Scope

- Cross-tenant user assignment.
- Outlet transfer antar-tenant.
- Operational sales atau inventory configuration.

## Dependencies

- P01-04 Tenant Provisioning.
- P01-05 Tenant Identity.

## References

- Module: [Tenancy](../../modules/tenancy.md)
- ADR: [002](../../architecture/decisions/002-shared-database-tenancy.md), [011](../../architecture/decisions/011-tenant-outlet-request-context.md), [014](../../architecture/decisions/014-web-admin-and-flutter-presentations.md), [020](../../architecture/decisions/020-single-tenant-user-membership.md)
- Acceptance criteria: AC-17–AC-21, AC-34

## Use Cases and Invariants

- CreateOutlet, UpdateOutlet, DisableOutlet.
- AssignUserToOutlet dan RemoveUserFromOutlet.
- Tenant berasal dari authorized route context.
- Child entity selalu di-scope terhadap parent context.
- Outlet dan user assignment tidak pernah melintasi tenant.

## Implementation Checklist

- [ ] Buat Tenant Admin route/middleware/layout boundary.
- [ ] Implementasikan immutable tenant request context.
- [ ] Implementasikan outlet lifecycle use cases.
- [ ] Implementasikan user-outlet assignment use cases.
- [ ] Terapkan scoped binding/query dan authorization.
- [ ] Tambahkan cross-tenant matrix tests.

## Verification and Evidence

- Manipulasi ULID route tidak menghasilkan akses tenant lain.
- Cross-tenant parent-child link ditolak tanpa mutation.
- Preference tenant/outlet terakhir tidak dapat meningkatkan privilege.
- Evidence isolation dan demo Tenant Admin dicatat.

## Architecture Check

Berhenti dan tanyakan product owner jika dibutuhkan hierarchy outlet, user transfer, tenant switching, soft-delete policy baru, atau perubahan source of truth request context.

