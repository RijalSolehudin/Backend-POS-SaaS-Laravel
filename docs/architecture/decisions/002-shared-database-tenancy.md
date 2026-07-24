# ADR-002: Shared Database Multi-Tenancy

- Status: Accepted
- Date: 2026-07-21

## Decision

Tenant berbagi satu database dan schema. Isolasi data menggunakan tenant/outlet ownership, scopes, policies, constraints, serta tests.

## Consequences

- Seluruh execution context harus tenant-aware.
- Unique constraints, cache, locks, events, jobs, dan broadcast channels harus scoped.
- Automated cross-tenant isolation tests menjadi bagian Definition of Done.

