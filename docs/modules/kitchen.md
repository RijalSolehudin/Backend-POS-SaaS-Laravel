# Kitchen Module

## Owns

- Kitchen station dan routing.
- Kitchen ticket/order projection.
- Item preparation status.
- Kitchen display behavior.

## Planned Use Cases

- Dispatch confirmed sales item ke station.
- Advance kitchen item status.
- Bump, recall, dan prioritize ticket.
- Publish real-time kitchen updates.

## Invariants

- Dispatch dari sales event bersifat idempotent.
- Kitchen state tidak mengubah financial snapshot order.
- Authorization dan outlet scope diterapkan pada real-time channel.

## Open Decisions

- Printer ownership dan deployment model.
- KDS ticket lifecycle.
- Routing product category versus explicit station mapping.
- Reverb/real-time topology.

