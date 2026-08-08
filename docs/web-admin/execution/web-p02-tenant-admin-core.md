# WEB-P02: Tenant Admin Core

Status: **Planned**
Layer: **Frontend**

## Outcome

Tenant Admin ready screens yang sudah memiliki backend route/action dipoles memakai foundation dari WEB-P01.

## Work Packages

| ID | Work package | Layer | Dependency | Status |
|---|---|---|---|---|
| W02-01 | Tenant Home Shell Migration | Frontend | WEB-P01 | Planned |
| W02-02 | Outlets UX Hardening | Frontend | W02-01 | Planned |
| W02-03 | Users and Predefined Roles UX Hardening | Frontend | W02-01 | Planned |
| W02-04 | POS Devices UX Hardening | Frontend | W02-01 | Planned |
| W02-05 | Catalog Categories and Products UX Hardening | Frontend | W02-01 | Planned |
| W02-06 | Availability and Price Override UX Hardening | Frontend | W02-05 | Planned |
| W02-07 | Sales Daily and Void UX Hardening | Frontend | W02-01 | Planned |
| W02-08 | Dining Floor and Table UX Hardening | Frontend | W02-01 | Planned |
| W02-09 | Inventory Baseline UX Hardening | Frontend | W02-01 | Planned |
| W02-10 | Recipe and Procurement Baseline UX Hardening | Frontend | W02-01 | Planned |
| W02-11 | Tenant Admin Core Verification | Docs/QA + Frontend | W02-02..W02-10 | Planned |

## Scope Rules

- Hanya fitur `Ready for UI` dari readiness matrix yang tampil aktif.
- Custom role builder, subscription, loyalty, split payment, combo, manager PIN, and real provider settings tetap disembunyikan.
- Existing route/action digunakan; tidak membuat domain behavior baru.

## Acceptance Criteria

- Semua ready screen memakai shared page, table, form, badge, and flash primitives.
- Validation dan destructive confirmation konsisten.
- Tenant/outlet context terlihat pada halaman yang relevan.
- No N+1 baru yang jelas pada list utama.
- Focused web tests dan `npm run build` lulus.
