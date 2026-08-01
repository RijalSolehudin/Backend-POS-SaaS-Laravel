# P08-05 — Payment Gateway Abstraction

Status: **Planned**

## Outcome

Sales order dapat dibayar melalui provider gateway dengan webhook aman dan idempotent.

## Scope

- Buat payment provider contract.
- Buat payment intent dan webhook inbox.
- Verify signature dan process paid/failed events.

## Implementation Contract

- Ikuti [Phase 08 Implementation Contract](implementation-contract.md).
- Jangan menyimpan card data.
- Webhook replay no-op bila event sama.

## Verification

- Intent lifecycle tests.
- Webhook signature/idempotency tests.
- Sales completion tests.
- `composer quality`.
