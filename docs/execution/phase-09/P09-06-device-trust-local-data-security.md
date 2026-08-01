# P09-06 — Device Trust and Local Data Security

Status: **Planned**

## Outcome

Device sync memakai trust boundary yang jelas dan local data policy tersedia untuk Flutter.

## Scope

- Perketat device sync authorization.
- Return local retention/encryption policy from bootstrap.
- Revoke device blocks new sync mutation.

## Implementation Contract

- Ikuti [Phase 09 Implementation Contract](implementation-contract.md).
- Server tidak menerima local encryption key.

## Verification

- Revocation sync tests.
- Bootstrap policy tests.
- Secret redaction tests.
- `composer quality`.
