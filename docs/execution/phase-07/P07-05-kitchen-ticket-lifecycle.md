# P07-05 — Kitchen Ticket Lifecycle

Status: **Planned**

## Outcome

Confirmed Sales order item menghasilkan kitchen ticket tepat satu kali dan dapat diproses di KDS.

## Scope

- Buat ticket, ticket item, dan ticket event.
- Generate ticket dari confirmed order item.
- State transition queued/preparing/ready/served/cancelled.

## Implementation Contract

- Ikuti [Phase 07 Implementation Contract](implementation-contract.md).
- Ticket creation idempotent per order item and station.
- Cancel item/order membuat event cancellation.

## Verification

- Ticket creation idempotency tests.
- State transition tests.
- Sales cancellation regression tests.
- `composer quality`.
