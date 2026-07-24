# Multi-Tenancy

Status: **Accepted at strategy level**

## Strategy

Sistem menggunakan satu MariaDB database dan shared tables. Data bisnis dipisahkan dengan `tenant_id` dan/atau ownership melalui outlet.

## Tenant Context

- Tenant context ditentukan oleh identitas dan membership pengguna, bukan dipercaya dari request body.
- Outlet-scoped API menyertakan `{outlet}` secara eksplisit pada URL.
- Backend memuat outlet, menurunkan tenant, lalu memverifikasi status tenant, membership user, dan outlet assignment.
- Tenant-wide API menyertakan `{tenant}` secara eksplisit pada URL.
- Active outlet pada Flutter hanya preferensi client dan bukan sumber authorization.
- Custom tenant/outlet header serta mutable active-outlet state pada token tidak menjadi sumber utama context.
- Context user, token, tenant, dan outlet bersifat immutable selama satu request.
- HTTP request, console command, queue job, event, broadcast channel, dan scheduled task harus memiliki tenant context eksplisit.
- Untuk client `pos_terminal`, outlet context juga harus cocok dengan outlet binding pada device registry.

## Defense in Depth

- Query scope membantu mencegah akses tidak sengaja.
- Policy/authorization tetap wajib untuk operasi sensitif.
- Database constraints mencegah relasi lintas tenant sejauh dapat diekspresikan.
- Child resource dan route model binding harus di-scope pada tenant/outlet context yang sudah divalidasi.
- API resource tidak boleh mengekspos identifier data tenant lain.
- Cache key, lock key, file path, broadcast channel, dan idempotency scope menyertakan tenant/outlet.

## Testing Requirements

Setiap modul tenant-owned memiliki automated test yang membuktikan bahwa user tenant A tidak dapat:

- Membaca record tenant B.
- Mengubah atau menghapus record tenant B.
- Menautkan record tenant A dengan child milik tenant B.
- Menebak ULID untuk melewati authorization.
- Menerima event atau cache data tenant B.

## Open Decisions

- Membership satu user pada lebih dari satu tenant.
- Apakah `tenant_id` diduplikasi pada seluruh tabel outlet-owned untuk integrity dan query performance.
- Mekanisme akses platform administrator.
