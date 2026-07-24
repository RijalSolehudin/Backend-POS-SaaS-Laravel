# ADR-018: Separate Platform Identity

- Status: Accepted
- Date: 2026-07-24

## Context

Platform Administrator memiliki privilege lintas tenant, sedangkan tenant user hanya beroperasi dalam membership dan outlet assignment. Menyimpan kedua identity pada model/table yang sama meningkatkan risiko privilege escalation, pengecualian tenant scope, dan kebingungan audit.

## Decision

- Platform Administrator menggunakan model `PlatformUser` dan tabel `platform_users`.
- Tenant identity tetap menggunakan `User` dan tabel `users`.
- Platform authentication menggunakan guard/provider, login route, session policy, password reset/recovery, dan security events yang terpisah.
- Platform authority tidak berasal dari tenant role/permission.
- Platform user tidak memiliki tenant membership secara otomatis.
- Jika orang yang sama membutuhkan Platform Admin dan Tenant Admin access, ia menggunakan dua akun terpisah.
- Platform user pertama dibuat melalui controlled CLI.
- Impersonation/login-as-tenant tidak tersedia pada MVP.

## Authentication Boundaries

```text
/platform/login
  -> platform guard/provider
  -> platform_users

/admin/login
  -> tenant web guard/provider
  -> users

/api/v1/auth/tokens
  -> tenant user provider
  -> Sanctum token for Flutter POS
```

Route naming final dapat disesuaikan, tetapi identity boundary tidak boleh digabung tanpa ADR baru.

## Authorization and Audit Rules

- Tenant user management tidak membaca/mengubah `platform_users`.
- Tenant role mutation tidak dapat menciptakan platform access.
- Platform action mencatat actor type `platform_user` dan actor identifier.
- Tenant action mencatat tenant user actor dan tenant/outlet context.
- Platform target tenant dicatat sebagai operation target, bukan platform membership.

## Consequences

- Dua model authenticatable dan auth flow perlu dipelihara.
- Platform security policy dapat dibuat lebih ketat tanpa memengaruhi tenant UX.
- Bug tenant user management tidak langsung menyentuh identity berkewenangan platform.
- Orang yang sama mungkin memiliki dua credential, tetapi context dan audit tetap jelas.

## Open Decisions

- Platform MFA/session/recovery policy telah diputuskan melalui ADR-019.
- Detail secure credential input untuk first-account bootstrap CLI.
