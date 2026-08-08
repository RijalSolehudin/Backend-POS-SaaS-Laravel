# WEB-P07: Public Surfaces

Status: **Planned**
Layer: **Frontend + Backend**

## Outcome

QR Customer and Landing/Pricing surfaces are built as separate UX surfaces within the same Laravel codebase when product priority allows.

## Work Packages

| ID | Work package | Layer | Dependency | Status |
|---|---|---|---|---|
| W07-01 | Public Surface Layout Baseline | Frontend | WEB-P01 | Planned |
| W07-02 | QR Customer Menu and Item Detail UX | Frontend + Backend | WEB-P06 | Planned |
| W07-03 | QR Customer Cart and Checkout UX | Frontend + Backend | W07-02 | Planned |
| W07-04 | QR Customer Confirmation and Tracking UX | Frontend + Backend | W07-03 | Planned |
| W07-05 | Landing Page Direction Refresh | Frontend + Product | WEB-P01 | Planned |
| W07-06 | Pricing Page Marketing Refresh | Frontend + Product | W07-05 | Planned |
| W07-07 | Public Surfaces Verification | Docs/QA + Frontend + Backend | W07-01..W07-06 | Planned |

## Scope Rules

- QR Customer is mobile-first and separate from Admin layout.
- Landing/Pricing CTA defaults to Request Demo, Hubungi Sales, or Daftar Pilot until Phase 10 exists.
- Pricing tiers are marketing concepts only before backend entitlement.
- Public QR uses existing token contract.

## Acceptance Criteria

- QR public pages do not expose unnecessary tenant data.
- Customer flow handles expired/invalid token gracefully.
- Landing/Pricing does not imply self-service tenant provisioning.
- Mobile viewport is manually verified.
