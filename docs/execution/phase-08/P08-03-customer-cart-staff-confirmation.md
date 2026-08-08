# P08-03 — Customer Cart and Staff Confirmation

Status: **Implemented — Pending MariaDB Verification**

## Outcome

Customer cart menjadi pending order request dan hanya masuk Sales setelah staff confirmation.

## Scope

- Buat cart, cart item, dan order request.
- Validate customer selection dari POS catalog.
- Staff confirm/reject order request.
- Confirm membuat/menambah Sales order.

## Implementation Contract

- Ikuti [Phase 08 Implementation Contract](implementation-contract.md).
- Order request confirmation wajib idempotent.

## Verification

- Cart validation tests.
- Confirm/reject tests.
- Staff authorization tests.
- Sales integration regression.
- `composer quality`.
