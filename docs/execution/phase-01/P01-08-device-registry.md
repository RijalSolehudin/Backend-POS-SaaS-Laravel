# P01-08 — POS Device Registry

Status: **Done**

## Outcome

Owner atau Manager berwenang dapat mendaftarkan, memindahkan, dan mencabut terminal POS yang terikat pada satu tenant dan satu outlet.

## Scope

- Device record berbasis installation ID.
- Registration oleh actor berwenang.
- Single tenant/outlet binding.
- Reassignment hanya antar-outlet dalam tenant yang sama.
- Revocation tanpa hard delete dan pencabutan seluruh linked token.
- Tenant Admin management UI dan audit.

## Out of Scope

- IMEI, MAC address, atau hardware fingerprint.
- Cashier self-registration.
- Binding satu device ke banyak outlet.

## Dependencies

- P01-06 Tenancy and Outlets.
- P01-07 Predefined RBAC.

## References

- ADR: [008](../../architecture/decisions/008-sanctum-token-lifecycle.md), [013](../../architecture/decisions/013-pos-device-registration.md)
- Acceptance criteria: AC-22–AC-26, AC-35

## Use Cases and Invariants

- RegisterPosDevice.
- ReassignPosDevice.
- RevokePosDevice.
- Device terikat tepat satu tenant dan outlet.
- Reassignment/revocation mencabut seluruh linked token.
- Cashier tidak dapat mendaftarkan device.

## Implementation Checklist

- [x] Implementasikan device persistence dan installation ID validation.
- [x] Implementasikan registration, reassignment, dan revocation use cases.
- [x] Hubungkan management UI ke use cases.
- [x] Terapkan authorization dan same-tenant constraint.
- [x] Terapkan atomic linked-token revocation.
- [x] Audit actor, device, outlet, reason, dan outcome.
- [x] Tambahkan lifecycle dan cross-tenant tests.

## Verification and Evidence

- `pos_devices` menyimpan installation ULID, tenant/outlet binding, metadata client, status, registration actor, last seen, dan revocation metadata.
- Sanctum `personal_access_tokens` mempunyai nullable `pos_device_id` untuk linked-token revocation P01-08 dan enforcement P01-09.
- `RegisterPosDevice`, `ReassignPosDevice`, `RevokePosDevice`, dan `ResolveRegisteredPosDevice` tersedia sebagai application actions.
- Outlet Manager dapat register device hanya pada outlet assignment yang sah; Cashier ditolak.
- Reassignment lintas tenant ditolak dan tidak mengubah state.
- Reassignment/revocation menghapus linked Sanctum tokens secara atomik.
- Device record tetap tersedia setelah revocation untuk audit.
- Unknown device menghasilkan stable code `DEVICE_NOT_REGISTERED`; revoked device menghasilkan `DEVICE_REVOKED`.
- Tenant Admin menyediakan `/admin/tenants/{tenant}/devices` untuk registration, reassignment, dan revocation.
- `composer quality` lulus: static quality gate, 11 unit tests/37 assertions, dan 46 feature tests/344 assertions pada MariaDB-backed test run.

## Architecture Check

Berhenti dan tanyakan product owner jika installation ID format/lifecycle belum memadai, dibutuhkan device approval flow, multi-outlet device, device credential, atau trusted hardware signal.
