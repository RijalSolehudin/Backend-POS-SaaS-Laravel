# ADR-021: Predefined Roles for MVP

- Status: Accepted
- Date: 2026-07-24

## Context

Custom role builder memperbesar permukaan authorization, privilege escalation, UI configuration, permission dependency, dan testing matrix. MVP hanya membutuhkan role operasional minimum yang perilakunya dapat diverifikasi.

## Decision

- MVP menggunakan predefined roles:
  - Tenant Owner.
  - Outlet Manager.
  - Cashier.
- Permission matrix role ditetapkan oleh sistem dan tidak dapat dibuat, dihapus, atau diedit tenant user pada MVP.
- Tenant Owner/actor berwenang dapat menetapkan predefined role kepada user.
- Platform privilege tidak termasuk tenant permission matrix dan tidak dapat diberikan melalui tenant role assignment.
- Custom role dan permission builder ditunda ke post-MVP.

## Invariants

- Tenant Owner tidak dapat membuat role yang memberikan platform access.
- Cashier tidak memperoleh administrative capability.
- Outlet Manager hanya beroperasi dalam tenant/outlet assignment yang sah.
- UI visibility tidak menggantikan server-side policy/permission checks.
- Role assignment dan removal diaudit.

## Consequences

- Authorization matrix lebih kecil dan dapat diuji lengkap.
- Tenant dengan kebutuhan role khusus harus menunggu post-MVP atau menggunakan kombinasi yang secara eksplisit disetujui kemudian.
- Penambahan role/permission baru memerlukan perubahan product/authorization contract dan tests.

