# Phase 08 Execution Plan

Status: **Planned**

Dokumen ini memecah [Phase 08 Growth Features](../../roadmap/phase-08-growth.md) menjadi work package berbasis outcome.

## Required Decision Gate

Implementasi Phase 08 mengikuti [ADR-043 Growth Channels MVP Policy](../../architecture/decisions/043-growth-channels-mvp-policy.md).
Detail implementasi wajib mengikuti [Phase 08 Implementation Contract](implementation-contract.md).

## Urutan yang Direkomendasikan

| ID | Work package | Dependency utama | Status |
|---|---|---|---|
| P08-01 | [Growth Channels Decision Gate](P08-01-growth-channels-decision-gate.md) | Phase 07 | Done |
| P08-02 | [QR Self-Order Session](P08-02-qr-self-order-session.md) | P08-01, Dining | Planned |
| P08-03 | [Customer Cart and Staff Confirmation](P08-03-customer-cart-staff-confirmation.md) | P08-02, Sales | Planned |
| P08-04 | [Waiter Workflow](P08-04-waiter-workflow.md) | P08-03, Dining | Planned |
| P08-05 | [Payment Gateway Abstraction](P08-05-payment-gateway-abstraction.md) | P08-03, Sales | Planned |
| P08-06 | [Reservation Minimum](P08-06-reservation-minimum.md) | P08-02, Dining | Planned |
| P08-07 | [Promotion Discount MVP](P08-07-promotion-discount-mvp.md) | P08-03, Sales | Planned |
| P08-08 | [Analytics Export and Growth Readiness](P08-08-analytics-export-growth-readiness.md) | P08-02..P08-07 | Planned |

## Fixed Implementation Decisions

- QR session public memakai signed opaque token dan expiry.
- Customer cart tidak langsung menjadi completed order; staff confirmation wajib.
- Payment gateway memakai provider abstraction dan webhook idempotency/signature.
- Promotion MVP single discount only, fixed amount atau percentage.
- Customer identity optional dan data minimum.

## Stop Rule

Berhenti jika implementasi membutuhkan loyalty, complex campaign stacking, marketplace delivery, full CRM, atau multi-provider settlement reconciliation.
