# WEB-P03: Tenant Dashboard

Status: **Planned**
Layer: **Frontend + Backend**

## Outcome

Tenant home menjadi operational overview dashboard dengan tenant-wide + outlet filter.

## Work Packages

| ID | Work package | Layer | Dependency | Status |
|---|---|---|---|---|
| W03-01 | Dashboard Read Model Contract | Backend + Frontend | WEB-P01 | Planned |
| W03-02 | Tenant and Outlet Filter Query Support | Backend | W03-01 | Planned |
| W03-03 | Sales, Device, Outlet, and Low Stock Metrics | Backend | W03-01 | Planned |
| W03-04 | Dashboard Page Composition | Frontend | W03-01..W03-03 | Planned |
| W03-05 | Quick Actions and Attention List | Frontend + Backend | W03-03 | Planned |
| W03-06 | Dashboard Empty and Partial Data States | Frontend | W03-04 | Planned |
| W03-07 | Tenant Dashboard Verification | Docs/QA + Frontend + Backend | W03-01..W03-06 | Planned |

## Thin Backend Scope

- Add dashboard query/read model.
- Aggregate data tenant-scoped and optional outlet-scoped.
- Avoid direct heavy query logic in Blade.
- Use existing module data and invariants.

## Acceptance Criteria

- Dashboard tidak menampilkan fitur belum aktif.
- Query aman untuk tenant multi-outlet.
- User hanya melihat outlet yang diizinkan.
- Data kosong memberi next action valid.
- Focused tests mencakup tenant isolation dan outlet filter.
