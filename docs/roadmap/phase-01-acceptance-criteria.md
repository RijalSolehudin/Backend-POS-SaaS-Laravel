# Phase 01 Acceptance Criteria

Status: **Accepted**

Dokumen ini mendefinisikan bukti bahwa Phase 01 Foundation selesai. Keberadaan migration, model, controller, view, atau endpoint saja tidak memenuhi acceptance criteria.

## Target Outcome

> Platform Administrator dapat memprovisikan tenant secara aman; Tenant Owner dapat mengelola outlet, user, predefined role assignment, device, dan catalog sederhana melalui Web Admin; cashier dapat memperoleh token dari registered POS device; seluruh akses terisolasi berdasarkan tenant dan outlet.

Order, shift, payment, dan inventory transaction belum menjadi outcome Phase 01.

## 1. Environment and Data Conventions

### AC-01 — MariaDB Compatibility

- Automated tests berjalan pada MariaDB 11.4, bukan hanya SQLite.
- Migration berhasil dari database kosong dengan strict SQL mode.
- Schema tidak menggunakan fitur PostgreSQL/MySQL yang tidak kompatibel.
- Development, CI, staging, dan production menargetkan series MariaDB yang sama.

### AC-02 — ULID Consistency

- Domain primary key menggunakan lowercase ULID `CHAR(26)`.
- Foreign ULID memiliki tipe, panjang, character set, dan collation identik.
- ULID invalid ditolak dan canonical normalization konsisten.
- Mengetahui ULID tidak melewati authorization.

### AC-03 — Numeric Baseline

- Monetary amount menggunakan signed integer field berakhiran `_minor`.
- Currency dinyatakan eksplisit pada konteks terkait.
- `FLOAT`/`DOUBLE` tidak digunakan untuk business calculation.
- Decimal-presision value dikirim melalui API sebagai decimal string.

## 2. Modular Architecture

### AC-04 — Module Ownership

- Capability mempunyai owning module yang jelas.
- Platform Identity, Identity, Tenancy, dan Catalog minimum tidak saling mengubah internal model secara langsung.
- Tidak ada generic service/helper sebagai tempat business logic tanpa owner.

### AC-05 — Application Use Cases

- Web, API, dan CLI menjalankan workflow melalui application action/use case.
- Provisioning Web dan CLI menggunakan use case yang sama.
- Transaction boundary berada pada application layer.
- Presentation layer hanya menangani transport, validation, authorization boundary, dan response.

### AC-06 — Presentation Separation

- Platform Admin, Tenant Admin, dan Flutter API mempunyai adapter terpisah.
- Web/API controller tidak saling memanggil melalui HTTP.
- Business rules tidak diduplikasi antar-presentation.
- Architecture/static tests atau review gate mendeteksi dependency violation penting.

## 3. Platform Identity and Administration

### AC-07 — First Platform User Bootstrap

Given belum ada Platform Administrator  
When controlled bootstrap CLI dijalankan oleh operator berwenang  
Then Platform User pertama dibuat tanpa membuat tenant user.

- Credential rahasia tidak muncul di log/output yang tidak aman.
- Duplicate bootstrap ditolak.
- Operation mempunyai actor/correlation audit.

### AC-08 — Identity Isolation

- Platform account disimpan pada `platform_users`; tenant account pada `users`.
- Tenant role tidak dapat menghasilkan platform privilege.
- Tenant user management tidak membaca/mengubah platform account.
- Platform account tidak otomatis mempunyai tenant membership.
- Impersonation tidak tersedia.

### AC-09 — Platform Authentication

- Password dan confirmed TOTP MFA diperlukan untuk login.
- Remember-me tidak tersedia.
- Error tidak mengungkap keberadaan identifier.
- Login di-rate-limit dan diaudit.
- Session ID diregenerasi setelah authentication.

### AC-10 — Platform Session

- Idle timeout 15 menit dan absolute timeout 4 jam ditegakkan server-side.
- Maksimal dua active sessions.
- Passive polling tidak memperpanjang idle timeout.
- Logout menginvalidasi server session/cookie Platform Admin saja.

### AC-11 — Sensitive Platform Confirmation

Provision/disable tenant, platform-user management, credential reset, dan security recovery membutuhkan recent password/MFA confirmation.

- Confirmation berlaku maksimal 10 menit.
- Expired/other-session confirmation ditolak.
- Actor, target, waktu, dan outcome diaudit.

## 4. Tenant Provisioning

### AC-12 — No Public Registration

- Tidak ada public tenant-registration endpoint/form.
- Tenant user dan Flutter tidak dapat membuat tenant.
- Unauthorized attempt tidak menghasilkan mutation.

### AC-13 — Atomic and Idempotent Provisioning

Successful provisioning membuat secara atomik:

- Tenant.
- Initial outlet.
- Initial Tenant Owner.
- Ownership/membership.
- Initial predefined role assignment.
- Minimum currency/timezone configuration.

Jika satu langkah gagal, seluruh operation rollback. Retry tidak menghasilkan tenant/owner duplikat dan failure memiliki correlation ID.

### AC-14 — Platform Target Audit

- Target tenant dicatat sebagai operation target, bukan platform membership.
- Audit membedakan actor `platform_user` dan `tenant_user`.
- Tenant role tidak memengaruhi platform authorization.

## 5. Tenant Web Admin

### AC-15 — Tenant Authentication

- Tenant user login melalui Tenant Admin; platform credential tidak berlaku.
- Tenant dan platform menggunakan cookie/session terpisah.
- State-changing request memiliki CSRF protection.
- Remember-me tidak tersedia.

### AC-16 — Tenant Session

- Idle timeout 30 menit dan absolute timeout 8 jam ditegakkan server-side.
- Passive polling tidak memperpanjang idle timeout.
- Logout menginvalidasi server session/cookie Tenant Admin saja.

### AC-17 — Tenant Context

Given user Tenant A  
When mengakses route/resource Tenant B  
Then request ditolak tanpa mengungkap data Tenant B.

- Route tenant divalidasi terhadap ownership user.
- Session tenant terakhir hanya navigation preference.
- Child binding/query selalu scoped.

### AC-18 — Immediate Effective Revocation

Request berikutnya ditolak ketika user/tenant dinonaktifkan, ownership/outlet assignment dicabut, session dicabut, atau security reset dilakukan. Sistem tidak menunggu timeout.

## 6. User, Role, and Outlet Management

### AC-19 — Single-Tenant User

- Tenant user dimiliki tepat satu tenant.
- User dapat ditugaskan ke banyak outlet dalam owning tenant.
- Assignment ke outlet tenant lain ditolak.
- User tidak dipindahkan lintas tenant melalui update biasa.

### AC-20 — Predefined Roles

- MVP hanya menyediakan Tenant Owner, Outlet Manager, dan Cashier.
- Actor berwenang mengelola assignment, bukan role/permission definition.
- Custom role builder tidak tersedia.
- Cashier tidak mempunyai administrative capability.
- Role assignment/removal diaudit dan tetap diperiksa server-side.

### AC-21 — Outlet Management

- Actor berwenang dapat membuat, memperbarui, menonaktifkan, dan menetapkan user ke outlet dalam tenant.
- Cross-tenant outlet reference ditolak.

## 7. POS Device Registration

### AC-22 — Authorized Registration

- Owner/Manager berpermission dapat mendaftarkan installation ID baru.
- Device terikat tepat satu tenant dan outlet.
- Cashier tidak dapat mendaftarkan device.
- IMEI/MAC/hardware fingerprint tidak dibutuhkan.
- Registration actor/waktu diaudit.

### AC-23 — Unknown Device

Cashier dengan credential valid pada installation ID tidak terdaftar tidak memperoleh operational token dan menerima stable code `DEVICE_NOT_REGISTERED`.

### AC-24 — Outlet Binding

Device Outlet A tidak dapat mengoperasikan Outlet B walaupun user mempunyai assignment ke Outlet B.

```text
effective access =
user assignment ∩ device binding ∩ token ∩ policy
```

### AC-25 — Device Revocation

- Revocation mencabut seluruh linked tokens.
- Token lama ditolak dengan stable code `DEVICE_REVOKED`.
- Device record tidak di-hard-delete.
- Actor, reason, dan waktu diaudit.

### AC-26 — Device Reassignment

- Membutuhkan permission dan reason.
- Hanya lintas outlet dalam tenant yang sama.
- Mencabut linked tokens dan membutuhkan login ulang.
- Operation diaudit.

## 8. Flutter Authentication and API

### AC-27 — Registered Device Login

- Active user, ownership/outlet assignment, dan device tervalidasi sebelum token diterbitkan.
- Plain token hanya tersedia saat issuance dan tidak masuk log.
- Token terkait device dan mengganti token lama pada kombinasi user-device yang sama.
- Expiry maksimal 30 hari.

### AC-28 — Token Lifecycle

- Logout mencabut current token saja.
- Specific device/all-device revocation tersedia untuk actor berwenang.
- Disabled user/tenant/assignment/device menghentikan effective access.
- Tidak ada refresh token; expired token membutuhkan login ulang.
- Tenant password reset mencabut seluruh user sessions dan Sanctum tokens.

### AC-29 — Outlet API Context

- Outlet endpoint memakai `/api/v1/outlets/{outlet}/...`.
- Backend menurunkan tenant dari outlet.
- Body/header tenant/outlet tidak menjadi authorization source.
- Child resource di-scope pada immutable request context.

### AC-30 — API Contract

- Success menggunakan `data`, serta `links`/`meta` bila relevan.
- Error menggunakan RFC 9457 `application/problem+json`.
- Error mempunyai stable `code`, `trace_id`, dan `retryable`.
- Validation error terstruktur.
- Response mempunyai `X-Request-ID`.
- Production error tidak mengekspos stack trace, SQL, path, secret, atau raw integration response.

## 9. Minimum Catalog

### AC-31 — Simple Category

- Actor berwenang dapat membuat, mengubah, mengaktifkan, dan menonaktifkan category dalam tenant aktif.
- Cross-tenant category access/reference ditolak.

### AC-32 — Simple Product

Minimum product mempunyai:

- Name.
- Tenant-unique SKU.
- Category.
- Base/outlet price `_minor`.
- Currency.
- Active status.
- Outlet availability.

Product tidak dapat dikaitkan dengan category/outlet tenant lain. Variant, modifier, combo, dan recipe bukan syarat Phase 01.

### AC-33 — Flutter Catalog Read

- Flutter hanya menerima product aktif/tersedia pada outlet context.
- Cross-tenant/outlet product tidak muncul.
- Monetary output menggunakan `_minor` dan currency eksplisit.
- Response mengikuti API v1 contract.

## 10. Security, Audit, and Isolation

### AC-34 — Cross-Tenant Test Matrix

Automated tests mencoba dan menolak:

- Read/update/delete record tenant lain.
- Cross-tenant parent-child link.
- Route binding menggunakan ULID tenant lain.
- Device operation pada outlet lain.
- Cache/lock context tenant lain bila capability telah digunakan.
- Tenant role menjadi platform privilege.

### AC-35 — Minimum Audit

Audit tersedia untuk:

- Platform login/security event.
- Tenant provisioning/disable.
- User/role/outlet assignment.
- Device registration/reassignment/revocation.
- Credential/security reset.

Audit mencatat actor type/ID, target context, action, UTC timestamp, outcome, correlation ID, dan reason bila diwajibkan. Audit tidak menyimpan credential/token/MFA secret.

## 11. Quality and Operational Gate

### AC-36 — Automated Tests

Test suite mencakup domain/use-case logic, Web/API authorization, tenant isolation, session/MFA, provisioning rollback/idempotency, device lifecycle, dan API contract—termasuk failure paths.

### AC-37 — Database Lifecycle

- Fresh migration berhasil pada MariaDB 11.4.
- Constraint penting diuji.
- Test tidak bergantung pada execution order.
- Demo/seeder tidak membuat production credential.

### AC-38 — Operational Readiness

- HTTPS diwajibkan.
- Session dan expired Sanctum token cleanup terjadwal.
- Log/audit tidak mengandung secret.
- Error dapat ditelusuri melalui request ID.
- Tidak ada unresolved critical/high security finding.

### AC-39 — Documentation

- ADR sesuai perilaku aktual.
- OpenAPI tersedia untuk API Phase 01.
- Predefined role/permission matrix terdokumentasi.
- Provisioning/emergency recovery runbook tersedia.
- Device registration/revocation runbook tersedia.
- Setup/deployment guide diperbarui.

## Out of Scope

- Order, payment, dan shift.
- Inventory dan recipe.
- Variant, modifier, dan combo.
- Table, kitchen/KDS, dan printer.
- QR order dan payment gateway.
- Offline synchronization.
- Public tenant registration dan email invitation lifecycle.
- Impersonation.
- Custom role/permission builder.
- Full Vue/Inertia Web Admin.
- Advanced reporting.

## Phase 01 Definition of Done

Phase 01 berstatus `Done` hanya jika:

- Seluruh applicable acceptance criteria lulus.
- Critical security/isolation criteria diverifikasi otomatis.
- Test suite lulus pada MariaDB 11.4.
- Dokumentasi sesuai implementasi aktual.
- Product owner menerima demonstrasi:

```text
Bootstrap Platform Admin
  -> login + TOTP
  -> provision tenant
  -> login Tenant Owner
  -> manage outlet/user/predefined role
  -> register POS device
  -> create simple product
  -> login Flutter on registered device
  -> fetch outlet catalog
  -> revoke device
  -> verify token is rejected
```
