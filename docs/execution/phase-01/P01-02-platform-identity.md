# P01-02 — Platform Identity

Status: **Planned**

## Outcome

Platform Administrator memiliki identity, authentication, MFA, session, bootstrap, dan recovery boundary yang terpisah dari tenant user.

## Scope

- Model/provider `platform_users`.
- Controlled bootstrap Platform Administrator pertama.
- Password dan mandatory TOTP MFA.
- Session idle/absolute timeout dan batas active session.
- Recent confirmation untuk sensitive action.
- Emergency recovery melalui CLI terkontrol.

## Out of Scope

- Tenant provisioning UI.
- Tenant membership untuk platform user.
- Impersonation.

## Dependencies

- P01-01 Modular Foundation.

## References

- Module: [Platform Identity](../../modules/platform-identity.md)
- ADR: [017](../../architecture/decisions/017-platform-admin-web-and-emergency-cli.md), [018](../../architecture/decisions/018-separate-platform-identity.md), [019](../../architecture/decisions/019-web-session-and-platform-mfa.md)
- Acceptance criteria: AC-07–AC-11, AC-35–AC-36, AC-38

## Use Cases and Invariants

- BootstrapPlatformAdministrator.
- AuthenticatePlatformUser dan ConfirmPlatformMfa.
- ConfirmSensitivePlatformAction.
- RevokePlatformSession dan RecoverPlatformAccess.
- Platform identity tidak memberikan tenant access.
- Remember-me tidak tersedia; secret tidak masuk log atau audit.

## Implementation Checklist

- [ ] Implementasikan platform identity persistence dan auth provider terpisah.
- [ ] Implementasikan idempotent first-user bootstrap.
- [ ] Implementasikan password + TOTP login dan rate limiting.
- [ ] Tegakkan idle 15 menit, absolute 4 jam, dan maksimal dua session.
- [ ] Implementasikan recent confirmation maksimal 10 menit.
- [ ] Implementasikan logout, session revocation, dan emergency recovery.
- [ ] Tambahkan security audit dan automated tests.

## Verification and Evidence

- Tenant credential gagal pada Platform Admin dan sebaliknya.
- Duplicate bootstrap ditolak tanpa membocorkan secret.
- Timeout, session cap, MFA failure, dan recovery diuji server-side.
- Evidence pengujian dan security review dicatat saat implementasi.

## Architecture Check

Berhenti dan tanyakan product owner jika muncul pilihan baru tentang MFA enrollment/recovery UX, credential policy, recovery authority, atau lifecycle platform account.

