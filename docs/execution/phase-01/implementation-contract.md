# Phase 01 Implementation Contract

Dokumen ini mengunci detail teknis Phase 01 Foundation agar work package P01-01 sampai P01-11 dapat dipahami tanpa membuat ulang keputusan arsitektur.

## Architecture Baseline

- Aplikasi adalah Laravel modular monolith.
- Workflow bisnis wajib dibuat sebagai application action/use case dengan public method `handle()`.
- Controller hanya menangani transport concern, request validation, session/token context, redirect/JSON response, dan mapping exception.
- Eloquent boleh dipakai langsung secara pragmatis.
- Repository hanya dibuat bila ada kompleksitas nyata.
- Module wajib memakai struktur fisik standar: `Application`, `Domain`, `Infrastructure`, dan `Presentation`.
- Migration, route, view, translation, provider, dan command ditempatkan di module pemilik.
- Semua ID domain memakai lowercase ULID `CHAR(26)` ASCII binary.

## Module Ownership

| Module | Owns |
|---|---|
| `PlatformIdentity` | platform admin credential, MFA, recovery code, session, security audit |
| `Identity` | tenant user credential, role assignment, credential reset |
| `Tenancy` | tenant, membership, outlet, outlet assignment, POS device, POS token context |
| `Catalog` | minimum category, product, product outlet availability |
| `Shared` | actor context, business exception, ULID concern, metadata redaction |

## Tables

Gunakan table berikut sebagai baseline Phase 01:

- `platform_users`
- `platform_sessions`
- `platform_recovery_codes`
- `platform_security_audit_events`
- `users`
- `user_role_assignments`
- `identity_password_reset_tokens`
- `identity_security_events`
- `tenants`
- `tenant_memberships`
- `outlets`
- `outlet_user_assignments`
- `pos_devices`
- `catalog_categories`
- `catalog_products`
- `catalog_product_outlet_availabilities`

Jangan membuat table lintas module untuk data yang sudah dimiliki module lain.

## Identity And Access

- Platform admin terpisah dari tenant user.
- Tenant user MVP hanya memiliki satu tenant membership.
- Web tenant session wajib menyimpan tenant authentication timestamp dan last activity timestamp.
- Platform MFA wajib untuk platform admin.
- Recovery code disimpan hashed dan single-use.
- Predefined role MVP: `tenant_owner`, `outlet_manager`, `cashier`.
- Custom role builder tidak masuk Phase 01.
- Semua admin action tenant harus memvalidasi tenant membership dan role.

## Tenancy And Outlet Context

- Tenant context eksplisit pada web route tenant.
- Outlet context eksplisit pada POS API route.
- Cross-tenant reference wajib ditolak.
- Inactive tenant/outlet/user/device tidak boleh mendapat akses baru.
- Device POS terikat ke tenant dan outlet.
- POS token diterbitkan melalui device registration/login dan dapat dicabut.

## API Baseline

- API memakai prefix `/api/v1`.
- Response sukses memakai envelope `data`.
- Error bisnis memakai Problem Details style dengan code stabil.
- POS API memakai Laravel Sanctum token.
- Jangan expose password, token, recovery code, atau secret pada response/audit/log.

## Catalog Minimum

- Minimum Catalog hanya memiliki category, product, dan product outlet availability.
- Product SKU unik per tenant.
- POS catalog hanya menampilkan product aktif dan available pada outlet tersebut.
- Pricing memakai money minor unit integer dan currency tenant.
- Variant, modifier, dan outlet override lanjutannya milik Phase 04.

## Testing Baseline

Setiap work package Phase 01 wajib memiliki:

- happy path feature test;
- validation/failure path test;
- tenant isolation atau authorization test bila menyentuh tenant resource;
- `composer quality`.

Gunakan test database MariaDB sesuai ADR testing. Jalankan `npm run build` bila mengubah frontend asset.

## Stop Rule

Berhenti jika implementasi membutuhkan multi-tenant membership, custom RBAC, public registration, external SSO, payment, Sales transaction, inventory, atau offline sync.
