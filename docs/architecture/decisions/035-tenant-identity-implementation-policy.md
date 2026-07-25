# ADR-035: Tenant Identity Implementation Policy

- Status: Accepted
- Date: 2026-07-26

## Context

P01-05 harus mengaktifkan Tenant Admin tanpa mencampur credential atau session Platform Admin. Keputusan sebelumnya menetapkan single-tenant membership, database session, idle 30 menit, absolute 8 jam, forced initial password change, serta revocation session dan token setelah credential reset.

Product owner mendelegasikan keputusan arsitektur work package lanjutan kepada implementer tanpa konfirmasi tambahan pada 2026-07-26.

## Decision

### Authentication and Tenant Resolution

- Guard `web` dan provider `users` khusus tenant user; guard `platform` tetap independen.
- Login Tenant Admin berada di `/admin/login` dan hanya menerima email serta password. Tenant tidak menjadi input login.
- Email dinormalisasi lowercase dan tetap unik global untuk MVP.
- `Identity Application` mendefinisikan consumer-owned `TenantAccessResolver`; adapter `Tenancy Infrastructure` mengambil single membership dan status tenant.
- URL resource selalu eksplisit `/admin/tenants/{tenant}/...`.
- Tenant route yang tidak sama dengan membership menghasilkan `404`; tenant atau membership yang tidak aktif mencabut session efektif pada request berikutnya.

### Session Boundary and Lifecycle

- Tenant Admin menggunakan database session Laravel dengan cookie default `pos_tenant_session`, path `/admin`, `HttpOnly`, `SameSite=Lax`, dan secure cookie pada HTTPS deployment.
- Platform Admin tetap menggunakan `pos_platform_session` dan custom session store. Login/logout salah satu area tidak mengautentikasi area lain.
- Session ID diregenerasi setelah login dan perubahan password.
- Idle timeout adalah 30 menit dan absolute timeout 8 jam, diperiksa server-side.
- Route yang secara eksplisit diberi action `tenant_passive=true` tidak memperbarui idle activity.
- Remember-me tidak tersedia.

### Credential Lifecycle

- Owner awal dengan `must_change_password=true` hanya dapat membuka halaman perubahan password dan logout.
- Password recovery memakai Laravel password broker, generic request response, single-use token, dan expiry 30 menit.
- Password change/reset menghapus seluruh database session user dan seluruh Sanctum personal access token.
- Current session hanya dibuat kembali setelah forced password change berhasil; password recovery mewajibkan login ulang di semua perangkat.
- User disable memakai application action yang mengubah status dan mencabut semua akses.

### Module Boundary

- `Identity` memiliki credential, authentication action, session policy, recovery, dan access-revocation contract.
- `Tenancy` memiliki tenant dan membership serta mengimplementasikan resolver yang dipublikasikan oleh `Identity`.
- Presentation tidak membaca tenant ID dari form atau session sebagai sumber otorisasi.
- Security audit consolidation dan operator-facing user management diselesaikan pada P01-07/P01-11; lifecycle action P01-05 sudah menyediakan enforcement boundary.

### Sanctum Readiness

- Sanctum dipasang pada P01-05 agar credential reset sudah dapat menjamin token revocation sebelum token issuance P01-09 diperkenalkan.
- Tabel `personal_access_tokens` memakai `CHAR(26)` ASCII untuk `tokenable_id`, konsisten dengan ULID tenant user.
- P01-09 tetap memiliki ownership atas issuance, ability, expiry, device binding, dan API middleware.

## Consequences

- Menambahkan Web Admin capability tidak mengubah autentikasi Flutter/POS atau Platform Admin.
- Cross-tenant request tidak membocorkan keberadaan tenant.
- Identity dan Tenancy tetap modular melalui application port; dependency lintas modul dibatasi dan dijaga Deptrac.
- Database-backed feature verification membutuhkan MariaDB test service sesuai ADR-027.
