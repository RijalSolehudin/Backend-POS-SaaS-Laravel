# P01-05 — Tenant Identity

Status: **Planned**

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

- [ ] Implementasikan tenant identity persistence dan provider.
- [ ] Implementasikan login/logout Tenant Admin dan CSRF protection.
- [ ] Tegakkan idle 30 menit dan absolute 8 jam server-side.
- [ ] Implementasikan credential reset serta session/token revocation.
- [ ] Terapkan active user/tenant check pada setiap request.
- [ ] Tambahkan auth isolation dan lifecycle tests.

## Verification and Evidence

- Platform credential tidak berlaku pada Tenant Admin.
- Disabled/reset user ditolak pada request berikutnya.
- Passive polling tidak memperpanjang idle timeout.
- Cross-tenant login/resource attempt tidak membocorkan data.

## Architecture Check

Berhenti dan tanyakan product owner jika muncul kebutuhan email verification, invitation, tenant MFA, account transfer, atau user lintas-tenant.

