# ADR-017: Platform Admin Web and Emergency CLI

- Status: Accepted
- Date: 2026-07-24

## Context

Controlled provisioning membutuhkan kanal operasional yang dapat digunakan berkelanjutan, diaudit, dan tidak mengekspos kemampuan platform kepada tenant user. CLI berguna untuk bootstrap dan recovery tetapi kurang sesuai sebagai kanal administrasi harian.

## Decision

- Platform Admin Web menjadi kanal utama tenant provisioning dan platform-level tenant administration.
- Platform Admin dan Tenant Admin merupakan dua area dalam satu Laravel modular monolith, bukan aplikasi/deployment terpisah.
- Keduanya berbagi repository, runtime/deployment, database, Blade + Alpine.js stack, Vite build, application use cases, domain modules, dan shared UI primitives.
- Platform Admin menggunakan route/authentication area yang terpisah dari Tenant Web Admin.
- Platform authority tidak berasal dari tenant role/permission dan tidak dapat diberikan melalui tenant role management.
- Tenant user tidak dapat mengakses Platform Admin area.
- Controlled CLI dipertahankan hanya untuk initial bootstrap dan emergency operation.
- Platform Admin Web dan CLI memanggil application provisioning use case yang sama.
- Business invariant, transaction boundary, idempotency, dan audit tidak diduplikasi pada presentation/console layer.

## Route and Context Direction

```text
/platform/...                         Platform Admin Web
/admin/tenants/{tenant}/...          Tenant Web Admin
```

- Route prefix digunakan pada MVP; subdomain terpisah tidak diperlukan.
- Platform route tidak menggunakan selected tenant sebagai sumber platform authorization.
- Ketika platform actor mengoperasikan tenant tertentu, target tenant dicatat sebagai operation target dan bukan active tenant membership biasa.
- Tenant Web Admin tetap menggunakan tenant/outlet route context serta membership/policy sesuai ADR-011 dan ADR-014.

## CLI Constraints

- CLI bukan kanal administrasi normal.
- CLI memerlukan privileged execution environment dan actor attribution yang dapat diaudit.
- CLI tidak mengimplementasikan provisioning logic sendiri.
- Emergency action harus menghasilkan audit outcome dan correlation identifier setara dengan Platform Admin Web.
- Detail confirmation, environment restriction, dan credential handling ditetapkan sebelum command diimplementasikan.

## Security Boundary

- Platform authentication/session dipisahkan secara logis dari tenant session.
- Platform endpoint memiliki authorization policy dan rate limit khusus.
- Tenant role mutation tidak dapat menciptakan platform privilege.
- Sensitive platform operation membutuhkan audit trail.

## Open Decisions

- Platform identity separation telah diputuskan melalui ADR-018.
- Session, recent confirmation, MFA, dan recovery policy telah diputuskan melalui ADR-019.
- Credential bootstrap dan recovery process untuk platform actor pertama.
- Detail emergency CLI actor attribution.

## Consequences

- Ada dua area Web Admin dengan navigation dan authorization boundary berbeda.
- Tidak ada backend, frontend project, atau deployment kedua untuk Platform Admin.
- Initial foundation scope bertambah dengan Platform Admin shell minimum.
- Operasi harian tidak bergantung pada akses shell server.
- CLI tetap tersedia untuk recovery tanpa menjadi sumber logic kedua.
- Platform identity boundary mengikuti ADR-018.
- Platform session/security policy mengikuti ADR-019.
