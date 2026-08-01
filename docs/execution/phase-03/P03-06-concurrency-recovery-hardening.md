# P03-06 — Concurrency and Recovery Hardening

Status: **Ready**

## Outcome

Critical Sales mutations memiliki retry, locking, idempotency, dan recovery checks yang cukup untuk mencegah duplicate financial mutation pada pilot.

## Scope

- Review lock boundary untuk approval, void, refund, cash movement, payment completion, and close shift.
- Recovery query atau command minimum untuk menemukan incomplete/ambiguous financial states.
- Tests untuk replay setelah partial persistence scenario yang masih bisa disimulasikan.
- Documentation for retry and recovery procedure.

## Out of Scope

- Distributed locks across services.
- Offline sync conflict resolution.
- Payment gateway reconciliation.
- External incident management tooling.

## Dependencies

- P03-02 selesai.
- P03-03 selesai.
- P03-04 selesai.
- P03-05 selesai.

## Acceptance Criteria

- Critical mutation menggunakan database transaction dan row lock pada aggregate terkait.
- Retry idempotent tidak membuat duplicate order, payment, refund, cash movement, atau approval.
- Recovery check dapat menemukan failed/ambiguous state yang perlu operator review.
- Runbook menjelaskan cara retry aman dan cara membaca audit/correlation ID.

## Verification

- Feature tests untuk critical replay matrix.
- Console tests bila recovery check dibuat sebagai command.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti dan tanyakan product owner jika recovery membutuhkan queue/outbox, distributed transaction, external payment gateway, atau offline sync strategy.
