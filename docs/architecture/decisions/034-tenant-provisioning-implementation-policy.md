# ADR-034: Tenant Provisioning Implementation Policy

- Status: Accepted
- Date: 2026-07-26

## Context

P01-04 harus membuat tenant, outlet awal, Tenant Owner, membership, dan predefined role assignment secara atomik melalui Platform Admin Web dan controlled CLI. ADR-016 belum menetapkan idempotency key, duplicate detection, credential delivery, ownership orchestration lintas modul, dan failure audit secara final.

Product owner mendelegasikan keputusan arsitektur work package berikutnya kepada implementer pada 2026-07-26 dengan arahan menggunakan rekomendasi terbaik tanpa konfirmasi tambahan.

## Decision

### Module Ownership

- `Tenancy` memiliki `ProvisionTenant` dan `DisableTenant` sebagai orchestration use case.
- `Tenancy` memiliki tenant, outlet, membership, provisioning request, dan tenancy audit.
- `Identity` memiliki tenant user, credential, dan predefined role assignment.
- `Tenancy` memanggil published `InitialTenantOwnerCreator` milik Application layer `Identity`.
- Adapter Web dan CLI memanggil `ProvisionTenant` yang sama; keduanya tidak membuat entity secara langsung.

### Atomicity and Idempotency

- Satu transaction membuat provisioning request, tenant, outlet awal, owner identity, role `tenant_owner`, membership, dan success audit.
- Idempotency key adalah lowercase ULID yang dibuat delivery channel dan disimpan dengan unique constraint.
- Fingerprint input menggunakan HMAC-SHA256 dengan application key atas canonical normalized input, termasuk password, tanpa menyimpan plaintext credential.
- Retry dengan key dan fingerprint sama mengembalikan output awal dengan penanda replay.
- Key sama dengan fingerprint berbeda ditolak menggunakan stable failure.
- Tenant code global dan normalized owner email merupakan duplicate boundary. Nama bisnis tidak wajib unik.
- Database unique/foreign-key constraints tetap menjadi concurrency backstop.

### Initial Credential

- Operator memasukkan initial owner password melalui password field Web atau hidden interactive CLI prompt.
- Password/confirmation tidak tersedia sebagai CLI option, tidak ditulis ke log, audit, output, atau provisioning request.
- Initial owner dibuat aktif dengan `must_change_password = true`.
- Tidak ada email invitation atau aplikasi yang menampilkan ulang password.
- Penyampaian initial credential kepada owner menggunakan kanal aman yang dikendalikan operator sampai credential lifecycle Tenant Admin tersedia.

### Tenant Baseline

- Tenant lifecycle minimum adalah `active` dan `disabled`.
- Tenant code memakai lowercase kebab-case, maksimal 64 karakter.
- Outlet awal mempunyai code yang unik dalam tenant.
- Currency dan timezone merupakan input eksplisit dari allowlist konfigurasi.
- Baseline MVP mengizinkan `IDR` serta timezone Indonesia `Asia/Jakarta`, `Asia/Makassar`, dan `Asia/Jayapura`.

### Authorization, Confirmation, and Audit

- Semua active `PlatformUser` mempunyai platform provisioning authority pada MVP; tenant role tidak pernah diperiksa.
- Web route memakai `platform.web`, `platform.authenticated`, dan `platform.confirmed` untuk provisioning/disable mutation.
- Halaman create juga meminta recent confirmation agar operator mengonfirmasi sebelum mengisi credential.
- CLI hanya berjalan secara interaktif, meminta operator identity/reason/reference, dan meminta final confirmation.
- Audit mencatat actor type/ID, target tenant, action, outcome, UTC time, correlation ID, reason, dan metadata non-secret.
- Failure audit ditulis setelah business transaction rollback. Failure audit tidak mengubah hasil rollback.
- Disable membutuhkan reason dan menjadi idempotent bila tenant sudah disabled.

## Schema Direction

```text
Identity
  users
  user_role_assignments

Tenancy
  tenants
  outlets
  tenant_memberships
  tenant_provisioning_requests
  tenancy_audit_events
```

- Domain key dan foreign key menggunakan lowercase ULID `CHAR(26)` ASCII `ascii_bin`.
- `tenant_memberships.user_id` unik untuk menegakkan single-tenant user pada MVP.
- Role assignment tidak menetapkan keputusan final mengenai jumlah role per user; P01-04 hanya membuat assignment `tenant_owner`.

## Stable Failures

- `TENANT_IDEMPOTENCY_MISMATCH`
- `TENANT_PROVISIONING_IN_PROGRESS`
- `TENANT_CODE_UNAVAILABLE`
- `TENANT_OWNER_EMAIL_UNAVAILABLE`
- `TENANT_PROVISIONING_CONFLICT`
- `TENANT_PROVISIONING_FAILED`
- `TENANT_NOT_FOUND`

Unexpected failure diekspos sebagai generic provisioning failure dengan correlation ID; raw SQL/exception tidak ditampilkan kepada user.

## Consequences

- Web dan CLI mempunyai behavior atomik/idempotent yang sama.
- Initial credential aman dari command history dan audit, tetapi operator tetap bertanggung jawab atas secure delivery.
- Cross-module dependency terbatas dari `Tenancy Application` ke published `Identity Application`.
- Full owner login, forced password change, tenant session revocation, dan role matrix diselesaikan oleh work package Identity/RBAC berikutnya.
