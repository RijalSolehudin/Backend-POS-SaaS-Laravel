# P01-08 — POS Device Registry

Status: **Planned**

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

- [ ] Implementasikan device persistence dan installation ID validation.
- [ ] Implementasikan registration, reassignment, dan revocation use cases.
- [ ] Hubungkan management UI ke use cases.
- [ ] Terapkan authorization dan same-tenant constraint.
- [ ] Terapkan atomic linked-token revocation.
- [ ] Audit actor, device, outlet, reason, dan outcome.
- [ ] Tambahkan lifecycle dan cross-tenant tests.

## Verification and Evidence

- Unknown device menghasilkan `DEVICE_NOT_REGISTERED`.
- Revoked device/token menghasilkan `DEVICE_REVOKED`.
- Reassignment lintas tenant ditolak dan tidak mengubah state.
- Device record tetap tersedia setelah revocation untuk audit.

## Architecture Check

Berhenti dan tanyakan product owner jika installation ID format/lifecycle belum memadai, dibutuhkan device approval flow, multi-outlet device, device credential, atau trusted hardware signal.

