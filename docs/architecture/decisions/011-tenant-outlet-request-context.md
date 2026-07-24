# ADR-011: Tenant and Outlet Request Context

- Status: Accepted
- Date: 2026-07-23

## Context

Shared-database tenancy mengharuskan setiap operasi mengetahui tenant dan outlet yang sah. Context yang tersembunyi pada header atau mutable token state meningkatkan risiko request diproses untuk outlet yang salah, khususnya saat retry, pergantian outlet, atau penggunaan beberapa perangkat.

## Decision

- Outlet-scoped API menggunakan `{outlet}` secara eksplisit pada URL.
- Backend memuat outlet, menurunkan tenant dari ownership outlet, lalu memverifikasi tenant aktif, membership user, dan outlet assignment.
- Tenant-wide API menggunakan `{tenant}` secara eksplisit pada URL.
- Login tidak otomatis menetapkan active outlet pada server.
- Flutter memperoleh daftar outlet yang diizinkan dan menyimpan active outlet hanya sebagai preferensi UI.
- `tenant_id` atau `outlet_id` dalam request body/custom header tidak menjadi sumber authorization.
- Token tidak menyimpan mutable active-outlet state sebagai sumber context.
- Context user, token, tenant, dan outlet bersifat immutable selama satu request.

## Proposed API Shape

```text
POST /api/v1/auth/tokens
GET  /api/v1/me/outlets

GET  /api/v1/outlets/{outlet}/catalog
POST /api/v1/outlets/{outlet}/shifts
POST /api/v1/outlets/{outlet}/orders

GET  /api/v1/tenants/{tenant}/users
POST /api/v1/tenants/{tenant}/outlets
```

Nama resource final tetap mengikuti API design phase; bentuk context URL dalam ADR ini bersifat wajib.

## Authorization Flow

```text
Authenticate token
  -> resolve route outlet/tenant
  -> validate active tenant
  -> validate user membership
  -> validate outlet assignment
  -> build immutable request context
  -> authorize operation/resource
  -> invoke application use case
```

## Rules

- Child resource lookup dan route model binding di-scope pada context yang sudah divalidasi.
- Mengetahui ULID milik tenant/outlet lain tidak memberikan akses.
- Cache, lock, rate limit, dan idempotency scope menyertakan tenant/outlet ketika relevan.
- Event dan queued job membawa tenant/outlet identifier eksplisit serta membentuk context baru saat diproses.
- Context tidak disimpan pada mutable static/global state yang dapat bocor ke request/job berikutnya.
- Use case tidak mempercayai ownership identifier bebas dari request body.

## Consequences

- URL lebih panjang tetapi target outlet terlihat pada log, audit, dan API documentation.
- Retry tetap menuju outlet yang sama tanpa bergantung pada server-side active state.
- Flutter dapat berpindah outlet tanpa mengubah token state.
- Middleware, scoped binding, dan cross-tenant tests menjadi bagian penting foundation.

## Open Decisions

- Apakah device registration membatasi perangkat ke satu atau beberapa outlet.
- Apakah `tenant_id` diduplikasi pada tabel outlet-owned untuk integrity dan query performance.

