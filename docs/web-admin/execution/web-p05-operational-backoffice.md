# WEB-P05: Operational Back-office Screens

Status: **Planned**
Layer: **Frontend + Backend**

## Outcome

Operational screens yang domain/action-nya sudah ada tetapi belum punya Web Admin lengkap mendapat thin backend adapter dan UI.

## Work Packages

| ID | Work package | Layer | Dependency | Status |
|---|---|---|---|---|
| W05-01 | Sales Order Detail, Refund, and Shift Review | Frontend + Backend | WEB-P03 | Planned |
| W05-02 | Table Session Monitor and Controls | Frontend + Backend | WEB-P03 | Planned |
| W05-03 | Kitchen Stations and Routing Admin | Frontend + Backend | WEB-P03 | Planned |
| W05-04 | KDS Monitor and Ticket State UX | Frontend + Backend | W05-03 | Planned |
| W05-05 | Print Job Monitoring and Reprint UX | Frontend + Backend | W05-04 | Planned |
| W05-06 | Inventory Transfer Lifecycle UX | Frontend + Backend | WEB-P02 | Planned |
| W05-07 | Purchase Order Lifecycle UX | Frontend + Backend | WEB-P02 | Planned |
| W05-08 | Goods Receipt and Purchase Return UX | Frontend + Backend | W05-07 | Planned |
| W05-09 | Operational Back-office Verification | Docs/QA + Frontend + Backend | W05-01..W05-08 | Planned |

## Scope Rules

- Setiap screen tetap dimiliki owning module.
- Mutation sensitif memakai approval/idempotency sesuai backend.
- KDS realtime vs polling harus diputuskan sebelum implementasi W05-04.
- Web UI tidak menjadi POS cashier utama.

## Acceptance Criteria

- Thin backend adapters tidak menduplikasi business logic.
- Tenant/outlet authorization diuji.
- Empty/error/loading state tersedia.
- `npm run build` dan focused feature tests lulus.
