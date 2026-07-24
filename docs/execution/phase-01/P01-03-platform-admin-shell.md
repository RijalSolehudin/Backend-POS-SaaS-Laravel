# P01-03 — Platform Admin Shell

Status: **Planned**

## Outcome

Platform Administrator dapat masuk ke area `/platform/...` yang terisolasi dan siap menjadi presentation utama untuk operasi platform.

## Scope

- Route, middleware, layout, navigation, dan error boundary Platform Admin.
- Login, MFA challenge, logout, session visibility, dan recent confirmation UI.
- Blade-first dengan Alpine.js untuk interaksi ringan.
- Authorization dan audit hook untuk sensitive platform action.

## Out of Scope

- Aplikasi/deployment frontend terpisah.
- Vue SPA atau Inertia.
- Tenant operational screens.
- Impersonation.

## Dependencies

- P01-02 Platform Identity.

## References

- ADR: [014](../../architecture/decisions/014-web-admin-and-flutter-presentations.md), [015](../../architecture/decisions/015-blade-first-vue-by-exception.md), [017](../../architecture/decisions/017-platform-admin-web-and-emergency-cli.md), [018](../../architecture/decisions/018-separate-platform-identity.md), [019](../../architecture/decisions/019-web-session-and-platform-mfa.md)
- Acceptance criteria: AC-06, AC-09–AC-11
- [Web Admin Conventions](../../architecture/web-admin-conventions.md)

## Use Cases and Invariants

- Controller hanya mengadaptasi request/response ke use case.
- Platform cookie/session terpisah dari Tenant Admin.
- CSRF berlaku untuk mutation.
- Passive polling tidak memperpanjang idle timeout.
- Sensitive action membutuhkan recent confirmation yang valid untuk session tersebut.

## Implementation Checklist

- [ ] Buat route group dan middleware boundary `/platform`.
- [ ] Buat shared platform layout dan navigation shell.
- [ ] Buat authentication, MFA, logout, dan confirmation flow.
- [ ] Tampilkan session/security state yang diperlukan.
- [ ] Terapkan accessible validation dan safe error handling.
- [ ] Tambahkan feature tests untuk auth dan boundary.

## Verification and Evidence

- Platform dan Tenant Admin dapat memiliki session terpisah tanpa collision.
- Unauthorized, expired, dan unconfirmed requests ditolak konsisten.
- Blade/Alpine UI dapat digunakan tanpa business rule di view/controller.

## Architecture Check

Berhenti dan tanyakan product owner sebelum menambahkan Vue, live-update mechanism, navigation model baru, atau perubahan route/auth boundary.

