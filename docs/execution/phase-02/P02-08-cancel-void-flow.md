# P02-08 — Cancel/Void Minimum Flow

Status: **Planned**

## Outcome

Draft order dapat dibatalkan dan completed order dapat divoid dengan authorization, reason, dan audit.

## Scope

- Cancel draft order.
- Void completed order.
- Void payment record linkage.
- Audit actor/reason/timestamp.

## Verification

- Finalized transaction tidak di-hard-delete.
- Void membutuhkan reason.
- Unauthorized cashier/admin boundary ditolak.
