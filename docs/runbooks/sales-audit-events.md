# Sales Audit Events

Sales audit event bersifat append-only untuk business flow. Penghapusan hanya boleh melalui retention command operasional sesuai policy pilot.

## Event Matrix

| Event | Actor | Target | Outcome | Trigger |
|---|---|---|---|---|
| `approval.created` | Performer | `sales_sensitive_action_approval` | `pending` | Sensitive action approval requested |
| `approval.approved` | Approver | `sales_sensitive_action_approval` | `approved` | Supervisor approves request |
| `approval.rejected` | Approver | `sales_sensitive_action_approval` | `rejected` | Supervisor rejects request |
| `approval.expired` | System | `sales_sensitive_action_approval` | `expired` | Approval is used/checked after expiry |
| `approval.consumed` | Performer | `sales_sensitive_action_approval` | `consumed` | Sensitive mutation consumes approval |
| `order.voided` | Performer | `sales_order` | `voided` | Completed order void succeeds |
| `payment.refunded` | Performer | `sales_refund` | `recorded` | Full manual refund succeeds |
| `cash_movement.recorded` | Performer | `sales_cash_movement` | `recorded` | Cash in/out succeeds |
| `shift.discrepancy.recorded` | Cashier | `sales_shift` | `recorded` | Closed shift has non-zero cash variance |

## Required Fields

- `tenant_id`
- `outlet_id` when the event is outlet-scoped
- `actor_user_id` when a user initiated the event
- `event_type`
- `target_type` and `target_id`
- `outcome`
- `reason` for sensitive/manual financial operations
- `correlation_id`
- `occurred_at`

## Redaction

Sales audit metadata must redact keys containing:

- `password`
- `token`
- `secret`
- `recovery`
- `card`

Nested metadata follows the same redaction rules.

## Retention

Default pilot retention is 2 years, configured through `SALES_AUDIT_RETENTION_YEARS`.

Commands:

```bash
php artisan sales:prune-audit-events --pretend
php artisan sales:prune-audit-events
```

The command is scheduled daily with `withoutOverlapping()`.
