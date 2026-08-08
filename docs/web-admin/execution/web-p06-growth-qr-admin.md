# WEB-P06: Growth and QR Admin

Status: **Planned**
Layer: **Frontend + Backend**

## Outcome

Tenant Admin dapat mengelola growth capabilities yang sudah tersedia atau membutuhkan thin adapter: QR session, staff confirmation, waiter workflow, reservation, promotion, and payment gateway review.

## Work Packages

| ID | Work package | Layer | Dependency | Status |
|---|---|---|---|---|
| W06-01 | QR Session Management UX | Frontend + Backend | WEB-P03 | Planned |
| W06-02 | Customer Order Request and Staff Confirmation UX | Frontend + Backend | W06-01 | Planned |
| W06-03 | Waiter Workflow Test Surface UX | Frontend + Backend | W06-02 | Planned |
| W06-04 | Reservation Minimum UX | Frontend + Backend | WEB-P03 | Planned |
| W06-05 | Promotion MVP UX | Frontend + Backend | WEB-P03 | Planned |
| W06-06 | Payment Gateway Intent and Webhook Review UX | Frontend + Backend | WEB-P03 | Planned |
| W06-07 | Growth Admin Verification | Docs/QA + Frontend + Backend | W06-01..W06-06 | Planned |

## Scope Rules

- QR admin uses OrderingChannel module and existing QR token model.
- Do not introduce `/api/v2/qr-orders` or new QR schema from external prototype without ADR.
- Real payment provider settings remain hidden until provider decision.
- Promotion UI exposes only MVP single discount behavior.
- Waiter web entry is a lightweight test surface only; primary operational waiter workflow remains Flutter/mobile.

## Acceptance Criteria

- Staff confirmation remains required before customer cart becomes operational order.
- Payment gateway UI clearly indicates local/fake provider if still not production provider.
- Reservation and promotion states are tenant/outlet scoped.
- Focused tests and manual QA cover growth flows.
