# WEB-P08: UI Production Readiness

Status: **Planned**
Layer: **Docs/QA + Frontend + Backend**

## Outcome

Web Admin implementation is verified with frontend build, focused feature tests, manual responsive review, and accessibility smoke review before being called production-ready.

## Work Packages

| ID | Work package | Layer | Dependency | Status |
|---|---|---|---|---|
| W08-01 | Manual QA Test Case Update | Docs/QA | WEB-P02..WEB-P07 | Planned |
| W08-02 | Accessibility Smoke Review | Docs/QA + Frontend | WEB-P02..WEB-P07 | Planned |
| W08-03 | Responsive Viewport Review | Docs/QA + Frontend | WEB-P02..WEB-P07 | Planned |
| W08-04 | Frontend Build and Asset Review | Frontend | WEB-P02..WEB-P07 | Planned |
| W08-05 | Focused Web Feature Test Pass | Backend + Frontend | WEB-P02..WEB-P07 | Planned |
| W08-06 | Full Quality Gate Pass | Docs/QA + Backend + Frontend | W08-01..W08-05 | Planned |
| W08-07 | UI Readiness Runbook | Docs/QA | W08-06 | Planned |

## Acceptance Criteria

- Manual QA covers Platform Admin, Tenant Admin, QR Customer, and Landing/Pricing when those surfaces are in scope.
- `npm run build` passes.
- Focused feature tests pass.
- Full `php artisan test` and static quality gates are run before production claim.
- Accessibility issues with critical severity are resolved.
- Responsive screenshots/checks cover desktop and mobile viewports.
- Full E2E visual automation is optional for high-risk screens and is not the initial required gate.
