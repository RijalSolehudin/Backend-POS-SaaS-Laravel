# P01-05 — Tenant Identity

Status: **In Review**

## Outcome

Tenant user dapat mengakses Tenant Admin melalui session terpisah, dengan lifecycle credential dan revocation yang konsisten terhadap akses web dan token POS.

## Scope

- Tenant user authentication untuk `/admin/tenants/{tenant}/...`.
- Session idle/absolute timeout, logout, dan security reset.
- Single-tenant ownership invariant.
- Immediate effective revocation saat user atau tenant tidak aktif.
- Pemisahan total dari Platform Identity.

## Out of Scope

- Multi-tenant membership untuk satu user.
- Public signup dan invitation lifecycle.
- Mandatory tenant MFA.
- POS token issuance; ditangani P01-09.

## Dependencies

- P01-01 Modular Foundation.
- P01-04 Tenant Provisioning.

## References

- Module: [Identity](../../modules/identity.md)
- ADR: [018](../../architecture/decisions/018-separate-platform-identity.md), [019](../../architecture/decisions/019-web-session-and-platform-mfa.md), [020](../../architecture/decisions/020-single-tenant-user-membership.md)
- Acceptance criteria: AC-15–AC-20, AC-28

## Use Cases and Invariants

- AuthenticateTenantUser.
- LogoutTenantSession.
- ResetTenantCredential.
- DisableTenantUser dan RevokeUserAccess.
- User dimiliki tepat satu tenant.
- Reset password mencabut seluruh session dan Sanctum token user.
- Remember-me tidak tersedia.

## Implementation Checklist

- [x] Implementasikan tenant identity persistence dan provider.
- [x] Implementasikan login/logout Tenant Admin dan CSRF protection.
- [x] Tegakkan idle 30 menit dan absolute 8 jam server-side.
- [x] Implementasikan credential reset serta session/token revocation.
- [x] Terapkan active user/tenant check pada setiap request.
- [x] Tambahkan auth isolation dan lifecycle tests.

## Verification and Evidence

- Platform credential tidak berlaku pada Tenant Admin.
- Disabled/reset user ditolak pada request berikutnya.
- Passive polling tidak memperpanjang idle timeout.
- Cross-tenant login/resource attempt tidak membocorkan data.

## Implementation Evidence

- ADR-035 mencatat session, credential, module-boundary, dan Sanctum readiness policy.
- Tenant login menyelesaikan tenant dari single membership; tenant ID tidak diterima sebagai login input.
- Forced initial password change, password broker 30 menit, user/tenant active check, logout, dan server-side timeout tersedia.
- Sanctum `v4.3.3` terpasang dan personal access token memakai ULID-compatible `tokenable_id`.
- `TenantIdentityTest` mencakup login, forced password change, Platform/Tenant isolation, cross-tenant hiding, disabled tenant, timeout termasuk passive polling, dan session/token revocation.
- Composer validation, 11 unit tests/37 assertions, Pint, Larastan level 8, dan Deptrac lulus pada 2026-07-26.
- Delapan test Tenant Identity tersedia; eksekusi database-backed menunggu MariaDB test service `127.0.0.1:33067` yang masih menolak koneksi sesuai environment ADR-027.

## Architecture Check

Berhenti dan tanyakan product owner jika muncul kebutuhan email verification, invitation, tenant MFA, account transfer, atau user lintas-tenant.
