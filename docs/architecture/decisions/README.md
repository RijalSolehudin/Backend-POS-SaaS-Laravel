# Architecture Decision Records

## Status

- `Proposed`: diajukan, belum disetujui.
- `Accepted`: telah disetujui dan menjadi constraint perencanaan.
- `Superseded`: digantikan ADR lain.
- `Rejected`: dipertimbangkan tetapi tidak dipilih.

## Accepted ADRs

| ADR | Decision |
|---|---|
| [ADR-001](001-use-mariadb.md) | MariaDB sebagai database utama |
| [ADR-002](002-shared-database-tenancy.md) | Shared database multi-tenancy |
| [ADR-004](004-use-ulid.md) | ULID sebagai primary key domain |
| [ADR-005](005-modular-monolith-use-cases.md) | Modular monolith dengan application use cases |
| [ADR-006](006-module-physical-structure.md) | Struktur fisik standar setiap module |
| [ADR-007](007-sanctum-api-token-authentication.md) | Laravel Sanctum API token untuk Flutter |
| [ADR-008](008-sanctum-token-lifecycle.md) | Lifecycle Sanctum token untuk perangkat POS |
| [ADR-009](009-mariadb-version-ulid-storage.md) | MariaDB 11.4 LTS dan penyimpanan ULID `CHAR(26)` |
| [ADR-010](010-money-and-rounding.md) | Money minor units dan perhitungan presisi hybrid |
| [ADR-011](011-tenant-outlet-request-context.md) | Tenant/outlet context eksplisit pada URL API |
| [ADR-012](012-api-response-errors-versioning.md) | Response envelope, Problem Details, dan `/api/v1` |
| [ADR-013](013-pos-device-registration.md) | Device registry dan outlet binding terminal POS |
| [ADR-014](014-web-admin-and-flutter-presentations.md) | Web Admin untuk back-office dan Flutter untuk POS |
| [ADR-015](015-blade-first-vue-by-exception.md) | Blade + Alpine default, Vue untuk complex UI tertentu |
| [ADR-016](016-controlled-tenant-provisioning.md) | Controlled tenant provisioning tanpa public registration |
| [ADR-017](017-platform-admin-web-and-emergency-cli.md) | Platform Admin Web utama dan CLI emergency |
| [ADR-018](018-separate-platform-identity.md) | `platform_users` terpisah dari tenant users |
| [ADR-019](019-web-session-and-platform-mfa.md) | Web session policy dan mandatory Platform MFA |
| [ADR-020](020-single-tenant-user-membership.md) | Satu tenant per tenant user pada MVP |
| [ADR-021](021-predefined-mvp-roles.md) | Predefined roles tanpa custom role builder |
| [ADR-022](022-explicit-module-service-providers.md) | Registrasi eksplisit service provider per modul |
| [ADR-023](023-application-action-handle-convention.md) | Application action menggunakan public method `handle()` |
| [ADR-024](024-explicit-actor-context-in-actions.md) | Actor context eksplisit pada application action |
| [ADR-025](025-module-local-resources.md) | Route, migration, view, dan translation ditempatkan dalam modul pemilik |
| [ADR-026](026-deptrac-architecture-boundary-tests.md) | Deptrac untuk menjaga boundary arsitektur |
| [ADR-027](027-mariadb-container-test-strategy.md) | MariaDB container untuk database-backed test lokal dan CI |
| [ADR-028](028-typed-action-output-and-business-failures.md) | Typed action output dan typed business exception |
| [ADR-029](029-github-actions-ci.md) | GitHub Actions sebagai platform CI |
| [ADR-030](030-pint-and-larastan-quality-baseline.md) | Pint dan Larastan level 8 sebagai quality baseline |
| [ADR-031](031-shared-minimal-actor-context.md) | Shared minimal immutable `ActorContext` |
| [ADR-032](032-platform-identity-implementation-policy.md) | Platform identity credential, MFA, session, audit, dan recovery policy |
| [ADR-033](033-platform-admin-shell-composition.md) | Shared Platform Admin shell dengan halaman capability module-local |
| [ADR-034](034-tenant-provisioning-implementation-policy.md) | Atomic tenant provisioning, idempotency, initial credential, dan audit policy |
| [ADR-035](035-tenant-identity-implementation-policy.md) | Tenant session isolation, membership resolution, credential lifecycle, dan revocation |
| [ADR-036](036-tenancy-outlet-management-policy.md) | Immutable tenant context, outlet lifecycle, assignment integrity, dan owner administration |

## Superseded ADRs

| ADR | Superseded by |
|---|---|
| [ADR-003](003-api-first-flutter.md) | [ADR-014](014-web-admin-and-flutter-presentations.md) |

## Open Architecture Decisions

- Order/payment lifecycle.
- Recipe module ownership.
- Offline synchronization strategy.
- Stock deduction timing dan inventory costing.
- Real-time, queue, cache, dan deployment topology.
