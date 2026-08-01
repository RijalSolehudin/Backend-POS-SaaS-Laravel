# P02-05 — Idempotent Payment and Completion

Status: **Planned**

## Outcome

Cashier dapat menyelesaikan order dengan full cash/manual payment secara idempotent.

## Scope

- Payment record foundation.
- Complete order with payment API.
- Exact-payment-only MVP policy.
- Order/payment total consistency.
- Retry and conflict behavior.

## Verification

- Retry payment tidak menggandakan payment.
- Amount/currency mismatch ditolak.
- Completed order immutable.
