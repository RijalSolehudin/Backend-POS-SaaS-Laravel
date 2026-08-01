# P03-03 — Refund and Payment Reversal

Status: **Ready**

## Outcome

Completed order dapat direversal secara finansial melalui full manual refund yang aman, idempotent, diaudit, dan membutuhkan supervisor approval.

## Scope

- Full refund manual untuk completed non-voided order.
- Refund record yang mereferensikan original order dan payment.
- Refund amount sama dengan remaining refundable amount.
- Supervisor approval wajib memakai capability P03-02.
- Idempotency untuk refund submission.
- Shift and daily sales summary mengecualikan atau menampilkan refund sesuai policy reconciliation.
- Audit event untuk refund recorded dan replay.

## Out of Scope

- Partial refund.
- Payment gateway settlement atau callback.
- Inventory return.
- Accounting export.
- Reprint receipt refund.

## Dependencies

- P03-02 selesai.
- [ADR-038 Operational Safety MVP Policy](../../architecture/decisions/038-operational-safety-mvp-policy.md) accepted.

## Acceptance Criteria

- Refund hanya bisa dilakukan pada completed order dengan recorded payment.
- Refund membutuhkan approval valid untuk action `payments.refund`.
- Refund tidak bisa melebihi refundable amount.
- Retry dengan idempotency key dan fingerprint sama mengembalikan refund pertama.
- Retry dengan key sama tetapi fingerprint berbeda menghasilkan `409`.
- Refund tidak mengubah receipt/order/payment original secara destructive.
- Summary shift dan daily sales dapat menjelaskan gross, refunds, net sales, dan discrepancy.
- Semua refund mutation menghasilkan sales audit event non-secret.

## Verification

- Feature tests untuk happy path full refund.
- Feature tests untuk missing approval, consumed approval, amount mismatch, already refunded, and idempotency conflict.
- Summary tests untuk gross/refund/net totals.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti dan tanyakan product owner jika refund membutuhkan partial refund, gateway settlement, multi-payment order, inventory return, atau accounting journal.
