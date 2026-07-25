# ADR-033: Platform Admin Shell Composition

- Status: Accepted
- Date: 2026-07-26

## Context

Platform Admin membutuhkan shell Blade yang dapat dipakai oleh Platform Identity dan capability platform lintas modul berikutnya. Shell tersebut harus menjaga ownership module-local tanpa menciptakan modul domain semu atau memindahkan business workflow ke presentation bersama.

Product owner mendelegasikan keputusan arsitektur P01-03 kepada implementer pada 2026-07-26 dengan arahan menggunakan rekomendasi yang paling sesuai dengan baseline arsitektur.

## Decision

- Layout dan primitive presentasi yang benar-benar lintas modul ditempatkan pada global `resources/views/components/platform`.
- Halaman, controller, request, ViewModel, dan route capability tetap berada dalam owning module.
- Tidak dibuat module `PlatformAdmin`; Platform Admin adalah presentation area, bukan domain capability.
- Shell mempunyai layout `guest` untuk authentication flow dan layout `authenticated` untuk area operasional.
- Platform Identity tetap memiliki login, MFA, session replacement, recovery code, recent confirmation, security, dan logout flow.
- `/platform` menjadi authenticated entrypoint dan sementara mengarahkan ke security overview sampai capability platform lain menyediakan landing page yang lebih tepat.
- Navigation MVP ditulis eksplisit pada shared shell. Tidak dibuat dynamic registry, plugin discovery, database-driven menu, atau permission model baru.
- Shared frontend memakai satu Vite build dengan Tailwind CSS dan Alpine.js. Alpine hanya mengelola state lokal seperti mobile navigation.
- HTML exception pada route `/platform` menggunakan safe platform error boundary untuk status yang relevan. Detail exception tidak dirender kepada user.
- JSON/API response tidak menggunakan Platform Admin HTML error boundary.

## Route and Middleware Boundary

```text
platform.web
  session cookie/platform CSRF/cache boundary

platform.authenticated
  auth:platform
  platform.session-policy

platform.confirmed
  recent password + second-factor confirmation
```

Module yang menerbitkan Platform Admin route wajib menggunakan prefix/name `/platform`/`platform.*`, `platform.web`, dan `platform.authenticated` untuk protected page. Sensitive mutation menambahkan `platform.confirmed`; authorization dan audit bisnis tetap dilakukan oleh owning use case/policy.

## Ownership Examples

```text
resources/views/components/platform/
  document.blade.php
  guest-layout.blade.php
  app-layout.blade.php

app/Modules/PlatformIdentity/Presentation/Resources/views/
  login.blade.php
  security.blade.php

app/Modules/Tenancy/Presentation/Resources/views/platform/
  tenants/...
```

## Consequences

- Capability module dapat memakai shell yang konsisten tanpa bergantung pada internal module lain.
- Global resources hanya memiliki chrome dan primitive lintas-domain, bukan workflow bisnis.
- Navigation sederhana harus diperbarui secara eksplisit ketika capability platform baru ditambahkan.
- Perubahan ke dynamic navigation registry, Vue/Inertia, subdomain, atau deployment terpisah membutuhkan review arsitektur baru.

## Alternatives Considered

### Presentation-only `PlatformAdmin` module

Tidak dipilih karena tidak memiliki domain capability dan cenderung menjadi aggregator yang bergantung pada internal banyak modul.

### Seluruh Platform Admin berada pada global Laravel directories

Tidak dipilih karena menghilangkan ownership route/controller/view milik Platform Identity, Tenancy, dan capability lain.

### Dynamic module navigation registry sejak MVP

Tidak dipilih karena jumlah menu awal kecil dan registry menambah extension contract serta authorization surface sebelum dibutuhkan.
