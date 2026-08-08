# [rencana] P10-01: SaaS Lifecycle Decision Gate

Status: **Planned**
Layer: **Product/ADR + Backend**

## Outcome

Keputusan bisnis dan arsitektur Phase 10 dikunci sebelum schema, route, atau billing provider dibuat.

## Decisions Required

- Onboarding model: public self-service, assisted sales, atau hybrid.
- Tenant creation timing: before payment, after payment, or after manual approval.
- Plan model: free trial, paid-only, freemium, or pilot-only.
- Subscription statuses and allowed transitions.
- Trial length, grace period, suspension, cancellation, and reactivation semantics.
- Billing provider and currency.
- Invoice numbering and tax/VAT scope.
- Entitlement/feature gate shape.
- Upgrade/downgrade and proration policy.
- Data retention after cancellation.

## Acceptance Criteria

- ADR baru dibuat dan berstatus Accepted.
- Phase 10 implementation contract dibuat.
- Module ownership disepakati.
- Open decisions yang berdampak pada schema atau billing provider tidak tersisa.

## Stop Rule

Jangan mulai migration atau provider integration sebelum decision gate selesai.
