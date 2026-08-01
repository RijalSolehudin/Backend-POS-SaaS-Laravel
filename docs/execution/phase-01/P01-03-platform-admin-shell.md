# P01-03 — Platform Admin Shell

Status: **Done**

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

- ADR: [014](../../architecture/decisions/014-web-admin-and-flutter-presentations.md), [015](../../architecture/decisions/015-blade-first-vue-by-exception.md), [017](../../architecture/decisions/017-platform-admin-web-and-emergency-cli.md), [018](../../architecture/decisions/018-separate-platform-identity.md), [019](../../architecture/decisions/019-web-session-and-platform-mfa.md), [033](../../architecture/decisions/033-platform-admin-shell-composition.md)
- Acceptance criteria: AC-06, AC-09–AC-11
- [Web Admin Conventions](../../architecture/web-admin-conventions.md)

## Use Cases and Invariants

- Controller hanya mengadaptasi request/response ke use case.
- Platform cookie/session terpisah dari Tenant Admin.
- CSRF berlaku untuk mutation.
- Passive polling tidak memperpanjang idle timeout.
- Sensitive action membutuhkan recent confirmation yang valid untuk session tersebut.

## Implementation Checklist

- [x] Buat route group dan middleware boundary `/platform`.
- [x] Buat shared guest/authenticated layout dan navigation shell.
- [x] Integrasikan authentication, MFA, logout, session replacement, dan confirmation flow Platform Identity.
- [x] Tampilkan identity, MFA, timeout, dan active-session state yang diperlukan.
- [x] Terapkan accessible validation, skip navigation, responsive navigation, dan safe platform error boundary.
- [x] Tambahkan feature tests untuk shell, auth boundary, sensitive confirmation middleware, dan safe error rendering.

## Verification and Evidence

- ADR-033 menetapkan shared shell global tanpa membuat module domain semu; halaman dan route capability tetap module-local.
- `platform.authenticated` menggabungkan guard `platform` dan server-side session policy; sensitive mutation tetap menambahkan `platform.confirmed`.
- `/platform` merupakan GET-only authenticated entrypoint menuju security overview.
- Shared Vite entry menggunakan Tailwind CSS dan Alpine.js; Alpine hanya mengelola mobile navigation dan copy-to-clipboard recovery code.
- Blade templates berhasil diprecompile dan Vite production build berhasil.
- Static quality gate lulus: Pint, Larastan level 8, dan Deptrac tanpa violation.
- Unit suite lulus: 9 tests dan 33 assertions.
- Feature subset tanpa database untuk route middleware dan safe error boundary lulus: 2 tests dan 9 assertions.
- `npm install` audit tidak menemukan vulnerability pada 67 packages.
- Feature tests tersedia untuk guest/authenticated shell, platform guard redirect, middleware boundary, session visibility, dan safe 404 response.
- MariaDB-backed feature suite lulus pada MariaDB 11.4 test container `127.0.0.1:33067`.
- `composer quality` lulus: static quality gate, 11 unit tests/37 assertions, dan 37 feature tests/306 assertions.

## Architecture Check

Keputusan shell P01-03 dikunci dalam ADR-033 berdasarkan delegasi product owner. Berhenti dan tanyakan product owner sebelum menambahkan Vue, live-update mechanism, dynamic navigation registry, atau perubahan route/auth boundary di luar ADR tersebut.
