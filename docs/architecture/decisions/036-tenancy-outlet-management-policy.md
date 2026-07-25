# ADR-036: Tenancy and Outlet Management Policy

- Status: Accepted
- Date: 2026-07-26

## Context

P01-06 menyediakan Tenant Admin shell, outlet lifecycle, dan user-outlet assignment sebelum permission matrix lengkap P01-07 tersedia. Implementasi harus menolak manipulated tenant/outlet/user ULID tanpa bergantung pada navigation session atau input tenant dari form.

Product owner telah mendelegasikan keputusan arsitektur work package lanjutan kepada implementer tanpa konfirmasi tambahan.

## Decision

### Immutable Request Context

- `ResolveTenantRequestContext` menyelesaikan single membership user, active tenant, dan membership type.
- Middleware membandingkan tenant milik user dengan `{tenant}` route. Ketidaksesuaian route menghasilkan `404`; membership/tenant yang sudah tidak efektif mengakhiri session.
- Context request adalah readonly value object berisi `tenantId`, `userId`, dan `membershipType`.
- Context disimpan hanya sebagai request attribute. Tidak ada mutable static/global tenant state.
- `tenant.navigation.last_tenant_id` dan `last_outlet_id` merupakan UI preference yang baru ditulis setelah scope tervalidasi dan tidak pernah menjadi authorization input.

### Administration Authority

- Selama P01-06, outlet administration hanya tersedia bagi membership type `owner`.
- Application action tetap melakukan owner check walaupun route juga dilindungi middleware.
- P01-07 dapat memperluas capability ke predefined role lain, tetapi tidak boleh melemahkan tenant/outlet ownership checks.
- Membership ownership milik Tenancy; predefined permission matrix tetap milik Identity/RBAC.

### Outlet Lifecycle

- Outlet code dinormalisasi uppercase, maksimal 32 karakter, dan unik per tenant.
- Create menghasilkan outlet active.
- Update hanya mengubah name/code dan tidak memindahkan tenant ownership.
- Disable bersifat idempotent, membutuhkan reason 10–500 karakter, tidak hard-delete outlet, dan diaudit.
- Reactivation, outlet hierarchy, serta cross-tenant transfer tidak tersedia pada MVP.

### User-Outlet Assignment

- Satu tenant user dapat mempunyai banyak outlet assignment dalam owning tenant.
- Pivot `outlet_user_assignments` menyimpan `tenant_id`, `outlet_id`, dan `user_id`.
- Composite foreign key `(tenant_id, outlet_id)` dan `(tenant_id, user_id)` menjadi database backstop terhadap cross-tenant link.
- Assign dan remove idempotent. Pivot dapat di-hard-delete karena audit event mempertahankan history assignment.
- Tenant user display data dibaca melalui `TenantUserDirectory`, sebuah consumer-owned port Tenancy yang diimplementasikan adapter Identity; Tenancy tidak mengubah Identity model secara langsung.

### Query and Audit Boundary

- Semua child outlet lookup menyertakan validated `tenant_id`; mengetahui outlet ULID tenant lain menghasilkan `404`.
- Application action menerima immutable tenant context dan explicit `ActorContext`.
- Create/update/disable outlet serta assign/remove user mencatat actor, target tenant, correlation ID, outlet/user metadata, outcome, dan reason bila diperlukan.

## Consequences

- Tenant isolation ditegakkan pada middleware, application action, query scope, dan composite database constraints.
- Initial owner tetap dapat mengelola semua outlet berdasarkan owner membership; outlet assignment merepresentasikan operational scope user dan tidak dibutuhkan untuk owner administration.
- Adapter lintas Identity/Tenancy memakai application contracts dan tercatat eksplisit pada Deptrac.
- Full role/permission behavior, user creation, dan assignment policy per role diselesaikan pada P01-07.
