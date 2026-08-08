# [rencana] P10-04: Subscription Lifecycle Engine

Status: **Planned**
Layer: **Backend**

## Outcome

Tenant memiliki subscription lifecycle yang eksplisit dan dapat diaudit.

## Scope

- Tenant subscription aggregate.
- Status transition guard.
- Trial, active, past due, grace, suspended, cancelled, expired.
- Scheduled expiration/check command.
- Reactivation and controlled override.
- Audit trail.

## Acceptance Criteria

- Transition invalid ditolak.
- Scheduled lifecycle processing idempotent.
- Suspension tidak menghapus data.
- Tenant active/disabled semantics lama tidak dirusak tanpa ADR.
- Platform override membutuhkan reason dan actor.

## Out of Scope

- Invoice/payment.
- Feature gate enforcement.
- Data deletion automation.
