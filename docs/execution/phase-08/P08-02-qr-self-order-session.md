# P08-02 — QR Self-Order Session

Status: **Done**

## Outcome

Customer dapat membuka QR session public yang aman dan scoped ke outlet/table/pickup context.

## Scope

- Buat `ordering_qr_sessions`.
- Generate signed opaque token dengan expiry.
- Public endpoint membaca session dan catalog resolved.

## Implementation Contract

- Ikuti [Phase 08 Implementation Contract](implementation-contract.md).
- Jangan membuat Sales order pada P08-02.

## Verification

- Token expiry/revocation tests.
- Public catalog scope tests.
- Cross-outlet leakage tests.
- `composer quality`.
