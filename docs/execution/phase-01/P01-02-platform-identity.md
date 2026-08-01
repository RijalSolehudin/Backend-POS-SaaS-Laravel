# P01-02 — Platform Identity

Status: **Done**

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
- Detailed policy: [ADR-032](../../architecture/decisions/032-platform-identity-implementation-policy.md)
- Acceptance criteria: AC-07–AC-11, AC-35–AC-36, AC-38

## Use Cases and Invariants

- BootstrapPlatformAdministrator.
- AuthenticatePlatformUser dan ConfirmPlatformMfa.
- ConfirmSensitivePlatformAction.
- RevokePlatformSession dan RecoverPlatformAccess.
- Platform identity tidak memberikan tenant access.
- Remember-me tidak tersedia; secret tidak masuk log atau audit.

## Implementation Checklist

- [x] Implementasikan platform identity persistence dan auth provider terpisah.
- [x] Implementasikan controlled first-user bootstrap dengan duplicate rejection.
- [x] Implementasikan password + TOTP/recovery-code login dan progressive rate limiting.
- [x] Tegakkan idle 15 menit, absolute 4 jam, dan maksimal dua session.
- [x] Implementasikan password + second-factor recent confirmation maksimal 10 menit.
- [x] Implementasikan logout, session revocation, recovery-code regeneration, dan emergency recovery.
- [x] Tambahkan append-only security audit, queued alert, prune schedule, dan automated tests.

## Verification and Evidence

- Static quality gate lulus: Pint, Larastan level 8, dan Deptrac tanpa baseline/ignore.
- Unit suite lulus, termasuk deterministic TOTP, QR SVG, recovery-code format, dan lowercase ULID.
- Feature tests tersedia untuk identity isolation, password + TOTP, first bootstrap, recovery code, session cap/timeout/suspension, dan emergency recovery.
- Composer audit tidak menemukan active advisory setelah Guzzle diperbarui ke 7.15.1.
- Laravel Boost digunakan untuk memverifikasi Laravel 13 guard/provider, password rule, rate limiter, session, prompt, queue, dan notification behavior.
- MariaDB-backed feature suite lulus pada MariaDB 11.4 test container `127.0.0.1:33067`.
- `composer quality` lulus: static quality gate, 11 unit tests/37 assertions, dan 37 feature tests/306 assertions.

## Architecture Check

Keputusan P01-02 telah dikunci dalam ADR-032. Berhenti dan tanyakan product owner jika implementasi berikutnya perlu mengubah identity lifecycle, credential/MFA policy, session isolation, recovery authority, atau audit retention.
