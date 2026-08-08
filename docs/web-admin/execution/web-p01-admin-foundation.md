# WEB-P01: Admin Foundation

Status: **Ready**
Layer: **Frontend**

## Outcome

Shared admin design system, primitive components, and shell foundation siap dipakai oleh Platform Admin dan Tenant Admin.

## Work Packages

| ID | Work package | Layer | Dependency | Status |
|---|---|---|---|---|
| W01-01 | Design Tokens and Tailwind Baseline | Frontend | Frontend decisions | Ready |
| W01-02 | Shared Button, Link, and Icon Action Primitives | Frontend | W01-01 | Ready |
| W01-03 | Shared Badge, Alert, Flash, and Empty State Primitives | Frontend | W01-01 | Ready |
| W01-04 | Shared Form, Validation, and Field Section Primitives | Frontend | W01-01 | Ready |
| W01-05 | Shared Table, Filter Bar, and Pagination Primitives | Frontend | W01-01 | Ready |
| W01-06 | Admin Page Header and Workspace Layout Primitives | Frontend | W01-01..W01-05 | Ready |
| W01-07 | Tenant Admin Shell Skeleton | Frontend | W01-06 | Ready |
| W01-08 | Responsive Sidebar, Topbar, and Drawer Behavior | Frontend | W01-07 | Ready |
| W01-09 | Admin Foundation Verification | Docs/QA + Frontend | W01-01..W01-08 | Ready |

## W01-01 Design Tokens and Tailwind Baseline

Acceptance criteria:

- Hybrid Professional + F&B Accent palette tersedia.
- Typography, spacing, radius, border, shadow, and focus tokens konsisten.
- Purple/amber tidak menjadi tema dominan.
- Tidak ada decorative gradient/orb background pada admin shell.

## W01-02 Shared Button, Link, and Icon Action Primitives

Acceptance criteria:

- Primary, secondary, subtle, danger, and icon button variants tersedia.
- Button state mencakup hover, focus, disabled, and loading-friendly styling.
- Icon action memiliki accessible label.
- Tidak ada business logic dalam component.

## W01-03 Shared Badge, Alert, Flash, and Empty State Primitives

Acceptance criteria:

- Status badge mencakup success, warning, danger, neutral, info.
- Flash success/error/warning/info konsisten.
- Empty state memberi next action yang valid bila tersedia.
- Error tidak mengandalkan warna saja.

## W01-04 Shared Form, Validation, and Field Section Primitives

Acceptance criteria:

- Field label, hint, error, required marker, and section layout konsisten.
- Destructive action memiliki confirmation pattern.
- Form besar mendukung sectioning.
- Server-side validation tetap menjadi sumber kebenaran.

## W01-05 Shared Table, Filter Bar, and Pagination Primitives

Acceptance criteria:

- Table mendukung server-side pagination/filter/search/sort.
- Filter state cocok dengan query string.
- Mobile behavior tidak memotong action penting.
- Empty table state tersedia.

## W01-06 Admin Page Header and Workspace Layout Primitives

Acceptance criteria:

- Page header mendukung title, subtitle, primary action, and contextual metadata.
- Workspace layout menyediakan area filter, metric strip, main content, and secondary action.
- Tidak membuat card di dalam card.
- Heading scale sesuai konteks admin.

## W01-07 Tenant Admin Shell Skeleton

Acceptance criteria:

- Tenant Admin shell memakai collapsible sidebar + topbar.
- Navigation group mengikuti D-FE-004.
- Tenant context terlihat jelas.
- Menu belum siap tidak tampil aktif.

## W01-08 Responsive Sidebar, Topbar, and Drawer Behavior

Acceptance criteria:

- Desktop sidebar usable.
- Tablet collapsed state usable.
- Mobile drawer usable.
- Focus dan escape behavior untuk drawer/modal tidak mengganggu keyboard user.

## W01-09 Admin Foundation Verification

Acceptance criteria:

- `npm run build` lulus.
- Focused web feature tests yang terdampak lulus.
- Desktop dan mobile screenshot smoke check dilakukan.
- Manual QA notes dicatat untuk shell, component, and responsive behavior.
