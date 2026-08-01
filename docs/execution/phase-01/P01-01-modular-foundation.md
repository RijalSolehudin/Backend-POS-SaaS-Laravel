# P01-01 — Modular Foundation

Status: **Done**

Readiness approved: **2026-07-24**
Implementation started: **2026-07-24**

Implementation authorized by product owner. Verification dan evidence P01-01 telah terpenuhi melalui MariaDB-backed full quality gate.

## Outcome

Fondasi Laravel modular mempunyai boundary, convention, dan quality gate yang konsisten sehingga Web, API, dan CLI dapat memakai application use case yang sama.

## Scope

- Struktur fisik standar untuk module Phase 01.
- Aturan dependency dan ownership capability.
- Convention application action/use case dan transaction boundary.
- Separation presentation adapter untuk Platform Web, Tenant Web, API, dan CLI.
- Baseline MariaDB 11.4, ULID, numeric, testing, linting, dan CI.

## Out of Scope

- Workflow bisnis lengkap.
- Generic framework atau abstraction yang belum memiliki kebutuhan nyata.
- Repository untuk setiap model secara otomatis.

## References

- ADR: [001](../../architecture/decisions/001-use-mariadb.md), [004](../../architecture/decisions/004-use-ulid.md), [005](../../architecture/decisions/005-modular-monolith-use-cases.md), [006](../../architecture/decisions/006-module-physical-structure.md), [009](../../architecture/decisions/009-mariadb-version-ulid-storage.md), [010](../../architecture/decisions/010-money-and-rounding.md), [014](../../architecture/decisions/014-web-admin-and-flutter-presentations.md), [022](../../architecture/decisions/022-explicit-module-service-providers.md), [023](../../architecture/decisions/023-application-action-handle-convention.md), [024](../../architecture/decisions/024-explicit-actor-context-in-actions.md), [025](../../architecture/decisions/025-module-local-resources.md), [026](../../architecture/decisions/026-deptrac-architecture-boundary-tests.md), [027](../../architecture/decisions/027-mariadb-container-test-strategy.md), [028](../../architecture/decisions/028-typed-action-output-and-business-failures.md), [029](../../architecture/decisions/029-github-actions-ci.md), [030](../../architecture/decisions/030-pint-and-larastan-quality-baseline.md), [031](../../architecture/decisions/031-shared-minimal-actor-context.md)
- Modules: [Module Map](../../modules/README.md)
- Acceptance criteria: AC-01–AC-06, AC-36–AC-37

## Use Cases and Invariants

- Setiap capability mempunyai owning module.
- Presentation layer tidak memuat business workflow.
- Web, API, dan CLI tidak menduplikasi business rule.
- Domain identifier dan numeric representation mengikuti ADR.
- Cross-module access hanya melalui contract yang memang dibutuhkan.
- Setiap modul dengan bootstrap responsibility menggunakan service provider eksplisit.
- Satu application action mempunyai satu public workflow entry point bernama `handle()`.
- Actor dan target context diberikan secara eksplisit kepada action; action tidak membaca global HTTP state.
- Resource milik domain ditempatkan dalam modul dan dimuat oleh service provider modul.
- Dependency direction dan boundary antar-modul diverifikasi dengan Deptrac.
- Database-backed test berjalan pada MariaDB 11.4 container di local dan CI.
- Action memakai typed success output dan typed business exception.
- GitHub Actions menjalankan quality gate yang juga tersedia melalui local scripts.
- Pint dan Larastan level 8 menjaga formatting dan type correctness tanpa permanent baseline.
- Minimal immutable `ActorContext` dimiliki Shared Application dan tidak menjadi generic context bag.

## Implementation Checklist

- [x] Tetapkan skeleton/convention module Phase 01 sesuai ADR-006.
- [x] Tetapkan explicit module provider convention sesuai ADR-022; provider dibuat ketika module mempunyai bootstrap responsibility nyata.
- [x] Tetapkan module-local resource convention sesuai ADR-025.
- [x] Tetapkan convention action/use case, DTO/input, output, dan failure.
- [x] Terapkan convention action `handle()` sesuai ADR-023.
- [x] Implementasikan shared minimal `ActorContext` sesuai ADR-024 dan ADR-031.
- [x] Dokumentasikan transaction boundary pada application layer.
- [x] Verifikasi composition/presentation entry points pada use case nyata pertama.
- [x] Siapkan architecture/static checks yang relevan.
- [x] Konfigurasikan Deptrac sebagai architecture quality gate sesuai ADR-026.
- [x] Konfigurasikan Pint check dan Larastan level 8 sesuai ADR-030.
- [x] Siapkan MariaDB test lifecycle dan GitHub Actions baseline.
- [x] Definisikan MariaDB 11.4 container untuk integration/feature test sesuai ADR-027.
- [x] Dokumentasikan dependency rule untuk contributor.

## Verification and Evidence

- [x] Fresh migration dan database-backed test lulus pada MariaDB 11.4 strict mode.
  - Container definition dan mandatory MariaDB compatibility test tersedia.
  - Local MariaDB 11.4 container tersedia pada `127.0.0.1:33067`.
  - `composer quality` lulus dengan MariaDB-backed feature suite.
  - GitHub Actions workflow siap menjalankan MariaDB service.
- [x] Use case nyata pertama dipakai minimal dua presentation adapter tanpa HTTP internal.
  - `ProvisionTenant` dipakai oleh Platform Admin Web dan controlled interactive CLI.
- [x] Architecture check mendeteksi dependency violation yang disengaja.
  - Temporary `Catalog Domain -> Catalog Presentation` probe ditolak Deptrac dengan exit code 1, lalu probe dihapus.
- [x] `composer quality:static` lulus:
  - Composer validation.
  - Pint formatting check.
  - Larastan/PHPStan level 8 tanpa baseline.
  - Deptrac tanpa skip violation.
- [x] Unit test lulus: 11 tests, 37 assertions.
- [x] Feature test lulus: 37 tests, 306 assertions.
- [x] Vite production build lulus tanpa remote font/network dependency.
- [x] GitHub Actions workflow dapat diparse sebagai valid YAML.
- [x] Laravel Boost memverifikasi Laravel 13.20.0, Boost 2.4.13, dan Larastan 3.10.0.

## Architecture Check

Berhenti dan tanyakan product owner jika diperlukan module baru, perubahan dependency direction, abstraction lintas-module baru, atau perubahan convention data yang belum tercakup ADR.
