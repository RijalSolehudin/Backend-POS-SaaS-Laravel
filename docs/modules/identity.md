# Identity Module

## Owns

- User identity dan credential.
- Authentication session/token.
- Role, permission, dan authorization primitives.
- PIN authentication policy bila disetujui.

## MVP Use Cases

- Membuat initial Tenant Owner dan predefined owner role melalui published application boundary saat provisioning.
- Verifikasi credential tenant user dan menerbitkan Laravel Sanctum API token untuk device POS yang tervalidasi.
- Revoke token aktif atau token perangkat yang dipilih.
- Resolve authenticated identity.
- Assign minimum operational roles.

## Role Policy

- Role MVP bersifat predefined: Tenant Owner, Outlet Manager, dan Cashier.
- Permission matrix MVP tercatat pada [Role Permission Matrix](../architecture/role-permission-matrix.md).
- Tenant actor berwenang hanya mengelola assignment role.
- Custom role/permission builder tidak tersedia pada MVP.
- Platform authority tidak menjadi bagian tenant role matrix.

## Does Not Own

- Platform Administrator identity dan credential; dimiliki Platform Identity.
- Tenant/outlet membership dan device outlet ownership rules; dimiliki Tenancy.
- Device registration, reassignment, dan revocation workflow; dimiliki Tenancy.
- Shift dan cashier activity; dimiliki Sales.

## Open Decisions

- PIN storage, retry limit, lockout, dan supervisor re-authentication.

Initial owner credential, normalized email, dan forced first password change mengikuti [ADR-034](../architecture/decisions/034-tenant-provisioning-implementation-policy.md).
