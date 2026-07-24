# Identity Module

## Owns

- User identity dan credential.
- Authentication session/token.
- Role, permission, dan authorization primitives.
- PIN authentication policy bila disetujui.

## MVP Use Cases

- Authenticate user dan menerbitkan Laravel Sanctum API token.
- Revoke token aktif atau token perangkat yang dipilih.
- Resolve authenticated identity.
- Assign minimum operational roles.

## Role Policy

- Role MVP bersifat predefined: Tenant Owner, Outlet Manager, dan Cashier.
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
- Custom roles pada MVP.
