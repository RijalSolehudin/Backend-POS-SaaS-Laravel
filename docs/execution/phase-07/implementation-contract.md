# Phase 07 Implementation Contract

Dokumen ini mengunci detail teknis Phase 07 agar implementer tidak membuat keputusan dining/kitchen baru.

## Modules

- `Dining`: floor, table, table session, transfer, merge.
- `Kitchen`: station, routing rule, ticket, KDS state, printer dispatch.

## Tables

| Table | Owner | Purpose |
|---|---|---|
| `dining_floors` | P07-02 | Floor per outlet |
| `dining_tables` | P07-02 | Table per floor/outlet |
| `dining_table_sessions` | P07-03 | Occupancy source of truth |
| `dining_table_session_orders` | P07-03 | Link table session to Sales order |
| `kitchen_stations` | P07-04 | Station per outlet |
| `kitchen_routing_rules` | P07-04 | Catalog to station rule |
| `kitchen_tickets` | P07-05 | Kitchen ticket header |
| `kitchen_ticket_items` | P07-05 | Kitchen item lines |
| `kitchen_ticket_events` | P07-05 | Append-only ticket history |
| `kitchen_print_jobs` | P07-07 | Printer dispatch/reprint log |

## Enums

| Enum | Values |
|---|---|
| `TableStatus` | `active`, `inactive` |
| `TableSessionStatus` | `open`, `merged`, `transferred`, `closed`, `cancelled` |
| `KitchenTicketStatus` | `queued`, `preparing`, `ready`, `served`, `cancelled` |
| `PrintJobStatus` | `queued`, `sent`, `failed`, `cancelled` |

## Table Rules

- One open table session per table/outlet.
- Transfer moves session to another table and records previous table.
- Merge links source sessions into target session and marks source as `merged`.
- Table session close requires all linked Sales orders completed/cancelled/voided.

## Kitchen Rules

- Routing is server-side.
- Ticket creation idempotency scope: tenant, outlet, order item, station.
- Cancelled order/item creates ticket event; do not delete original ticket.
- KDS state changes require actor and timestamp.

## Realtime Rules

- Broadcast channels include tenant/outlet scope.
- Event payload must not include secrets.
- Client reconnect must call API snapshot endpoint.
- Missing broadcast event must not corrupt state.

## Printer Rules

- Print jobs are append-only.
- Reprint creates new print job with reason.
- Failed printer job can be retried.
- Print failure does not alter Sales order/payment.

## Error Codes

- `DINING_TABLE_NOT_FOUND`
- `DINING_TABLE_OCCUPIED`
- `DINING_TABLE_SESSION_NOT_FOUND`
- `DINING_TABLE_SESSION_INVALID_STATE`
- `KITCHEN_STATION_NOT_FOUND`
- `KITCHEN_ROUTING_MISSING`
- `KITCHEN_TICKET_NOT_FOUND`
- `KITCHEN_TICKET_INVALID_STATE`
- `KITCHEN_PRINT_JOB_NOT_FOUND`
- `KITCHEN_PRINT_FAILED`

## Testing Baseline

- Table session lifecycle and isolation tests.
- Kitchen routing tests.
- Ticket idempotency and state transition tests.
- Broadcast authorization tests when realtime is added.
- Printer failure/retry tests.
- `composer quality`.
