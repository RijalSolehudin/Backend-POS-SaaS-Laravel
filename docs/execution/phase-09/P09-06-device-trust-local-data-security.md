# P09-06 — Device Trust and Local Data Security

Status: **Done**

## Outcome

Device sync memakai trust boundary yang jelas dan local data policy tersedia untuk Flutter.

## Scope

- Perketat device sync authorization.
- Return local retention/encryption policy from bootstrap.
- Revoke device blocks new sync mutation.

## Delivered

- Endpoint Sync memakai POS device token yang diselesaikan oleh `ResolvePosOutletApiContext`.
- Bootstrap sync mengembalikan retention policy dan `requires_local_encryption`.
- Server menolak local encryption key melalui policy `server_accepts_local_encryption_keys=false`.
- Revoked POS device atau revoked sync state tidak dapat push mutation baru.

## Implementation Contract

- Ikuti [Phase 09 Implementation Contract](implementation-contract.md).
- Server tidak menerima local encryption key.

## Verification

- Revocation sync tests.
- Bootstrap policy tests.
- Secret redaction tests.
- `composer quality`.
