# Tenancy Module

## Owns

- Tenant dan outlet.
- User membership pada tenant/outlet.
- Tenant/outlet context resolution.
- Tenant-wide dan outlet-specific configuration ownership.
- POS device tenant/outlet registration dan binding.

## MVP Use Cases

- Provision tenant, initial outlet, dan initial owner secara atomik melalui privileged platform path.
- Assign user membership.
- List authorized outlets.
- Select and validate active outlet context.
- Register/reassign/revoke device melalui actor yang berwenang.
- Issue/revoke POS device token melalui flow user-device-outlet yang tervalidasi.
- Resolve outlet context untuk Flutter API dari Sanctum token dan route `{outlet}`.

## API Context

- Outlet-scoped route menyertakan `{outlet}` pada URL.
- Tenant diturunkan dari outlet oleh backend.
- Tenant-wide route menyertakan `{tenant}` pada URL.
- Active outlet Flutter tidak menjadi sumber authorization.

## Invariants

- Outlet hanya dimiliki satu tenant.
- Tenant user dimiliki tepat satu tenant pada MVP dan dapat mempunyai banyak outlet assignment dalam tenant yang sama.
- Tenant provisioning tidak boleh menghasilkan tenant/outlet/owner setengah jadi ketika gagal.
- Public actor tidak dapat membuat tenant pada MVP.
- User tidak dapat memilih outlet di luar membership yang sah.
- Semua public module operations menerima tenant context yang tervalidasi.
- Device `pos_terminal` terikat tepat pada satu tenant/outlet dan request outlet harus cocok dengan binding tersebut.
- Token POS selalu dikaitkan ke device terdaftar dan user hanya dapat mengakses outlet yang cocok dengan assignment serta binding device.

## Open Decisions

- Configuration inheritance tenant ke outlet.

Provisioning atomicity, idempotency, duplicate boundary, credential handling, dan audit mengikuti [ADR-034](../architecture/decisions/034-tenant-provisioning-implementation-policy.md).
