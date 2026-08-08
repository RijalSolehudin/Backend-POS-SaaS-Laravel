# [rencana] Phase 10 Execution Plan

Status: **Proposed**

Dokumen ini memecah [[rencana] Phase 10 SaaS Lifecycle, Onboarding, and Billing](../../roadmap/phase-10-saas-lifecycle-billing.md) menjadi work package backend lanjutan. Implementasi belum boleh dimulai sebelum decision gate disetujui.

## Required Decision Gate

Phase 10 membutuhkan ADR baru untuk:

- SaaS onboarding model.
- Subscription lifecycle dan entitlement policy.
- Billing provider dan webhook policy.
- Tenant suspension/reactivation semantics.

## Urutan yang Direkomendasikan

| ID | Work package | Layer | Dependency utama | Status |
|---|---|---|---|---|
| P10-01 | [[rencana] SaaS Lifecycle Decision Gate](P10-01-saas-lifecycle-decision-gate.md) | Product/ADR + Backend | Phase 09 | Planned |
| P10-02 | [[rencana] Plan and Entitlement Catalog](P10-02-plan-entitlement-catalog.md) | Backend | P10-01 | Planned |
| P10-03 | [[rencana] Onboarding Intake and Tenant Creation Policy](P10-03-onboarding-intake-tenant-creation.md) | Backend | P10-01, Tenancy | Planned |
| P10-04 | [[rencana] Subscription Lifecycle Engine](P10-04-subscription-lifecycle-engine.md) | Backend | P10-02, P10-03 | Planned |
| P10-05 | [[rencana] Billing Invoice and Payment Collection](P10-05-billing-invoice-payment-collection.md) | Backend | P10-04 | Planned |
| P10-06 | [[rencana] Billing Provider Webhook and Reconciliation](P10-06-billing-provider-webhook-reconciliation.md) | Backend | P10-05 | Planned |
| P10-07 | [[rencana] Server-side Feature Gate and Quota Enforcement](P10-07-feature-gate-quota-enforcement.md) | Backend | P10-02, P10-04 | Planned |
| P10-08 | [[rencana] Platform Billing Operations and Readiness](P10-08-platform-billing-operations-readiness.md) | Backend + Docs/QA | P10-02..P10-07 | Planned |

## Boundary Rules

- Phase 10 billing adalah SaaS billing tenant kepada platform.
- Phase 08 payment gateway tetap untuk sales order/customer payment.
- Entitlement check tidak boleh hanya berada di frontend.
- Tenant suspension tidak boleh menghapus data.
- Public signup tidak boleh bypass controlled provisioning invariants.
- Semua mutation billing harus idempotent bila dapat diulang.

## Verification Target

- Feature tests untuk lifecycle status dan transition guard.
- Feature tests untuk onboarding success/failure/rollback.
- Feature tests untuk invoice/payment/webhook idempotency.
- Feature tests untuk tenant feature gate.
- Authorization tests untuk platform override dan tenant visibility.
- Runbook untuk billing recovery, webhook replay, dan suspension/reactivation.

## Stop Rule

Berhenti dan minta keputusan product owner jika implementasi membutuhkan:

- storing payment card data,
- accounting ledger penuh,
- multi-provider settlement reconciliation,
- multi-currency billing,
- usage-based metering kompleks,
- tenant data deletion otomatis setelah cancellation,
- public self-service signup tanpa abuse prevention dan email verification policy.
