# P01-11 — Security, Audit, and Operational Readiness

Status: **Done**

## Outcome

Seluruh capability Phase 01 mempunyai bukti tenant isolation, authorization, audit, security, operability, dan dokumentasi yang cukup untuk dinyatakan selesai.

## Scope

- Cross-tenant and privilege-escalation test matrix.
- Minimum audit events dan redaction.
- Session/token cleanup dan HTTPS assumptions.
- Request correlation dan safe production error/log behavior.
- MariaDB lifecycle, full automated suite, OpenAPI, role matrix, runbook, dan setup/deployment guide.
- Final Phase 01 demonstration dan evidence review.

## Out of Scope

- Centralized SIEM atau enterprise observability platform.
- Advanced compliance certification.
- Performance/scale target Phase 09.

## Dependencies

- Seluruh work package Phase 01 yang menghasilkan capability terkait.

## References

- [Phase 01 Acceptance Criteria](../../roadmap/phase-01-acceptance-criteria.md)
- [Architecture Decisions](../../architecture/decisions/README.md)
- Acceptance criteria: AC-34–AC-39 dan seluruh AC yang dipetakan dari work package sebelumnya

## Use Cases and Invariants

- Audit membedakan `platform_user` dan `tenant_user`.
- Audit mencatat actor, target, action, UTC time, outcome, correlation ID, dan reason bila wajib.
- Credential, token, TOTP secret, raw integration response, SQL, dan stack trace tidak bocor.
- Isolation dan authorization diuji sebagai security property, bukan hanya UI behavior.

## Implementation Checklist

- [x] Lengkapi cross-tenant/privilege test matrix.
- [x] Lengkapi audit coverage dan redaction tests.
- [x] Verifikasi timeout, revocation, cleanup, HTTPS, dan request ID.
- [x] Jalankan full suite pada MariaDB 11.4 strict mode.
- [x] Validasi fresh migration dan independent test order.
- [x] Finalisasi OpenAPI, role matrix, runbook, dan deployment guide.
- [x] Lakukan security review dan selesaikan critical/high finding.
- [x] Jalankan Definition of Done demo dan catat evidence.

## Verification and Evidence

Evidence minimum:

- MariaDB evidence: `composer quality` berjalan pada MariaDB testing container `mariadb:11.4`; `MariaDbCompatibilityTest` memverifikasi MariaDB strict mode, migration table, dan ULID `CHAR(26)` ASCII binary collation.
- Automated security/isolation matrix: `tests/Feature/Security/PhaseOneReadinessTest.php` lulus 5 test / 21 assertion.
- Audit redaction: Platform dan Tenancy audit recorder meredaksi key metadata sensitif seperti `token`, `password`, `secret`, `totp`, dan `sql`.
- Cleanup: `schedule:list` memverifikasi `sanctum:prune-expired --hours=24` dijadwalkan harian dan `platform:prune-security-state` berjalan hourly.
- OpenAPI baseline: [docs/api/openapi.yaml](../../api/openapi.yaml).
- Role matrix: [Role Permission Matrix](../../architecture/role-permission-matrix.md).
- Runbook: [Platform Bootstrap and Emergency Recovery](../../runbooks/platform-bootstrap-and-recovery.md), [Tenant Provisioning](../../runbooks/tenant-provisioning.md), [POS Device and API Operations](../../runbooks/pos-device-and-api-operations.md), [Deployment Readiness](../../runbooks/deployment-readiness.md).
- Full quality evidence: `composer quality` lulus composer validate, Pint, PHPStan, Deptrac 0 violation, unit 11 test / 37 assertion, feature 62 test / 465 assertion.
- Build evidence: `npm run build` lulus.
- Security review: tidak ada critical/high finding terbuka pada automated matrix dan static architecture gate.

## Phase 01 Final Demo

```text
Bootstrap Platform Admin
  -> login + TOTP
  -> provision tenant
  -> login Tenant Owner
  -> manage outlet/user/predefined role
  -> register POS device
  -> create simple product
  -> login Flutter on registered device
  -> fetch outlet catalog
  -> revoke device
  -> verify token is rejected
```

Demo path didukung oleh feature tests untuk bootstrap/auth/session, provisioning, tenant outlet/user/RBAC/device/catalog, Flutter token issuance, outlet catalog API, dan device revocation token invalidation.

## Architecture Check

Berhenti dan tanyakan product owner jika remediation membutuhkan perubahan arsitektur, security policy, data retention, audit retention/storage, operational topology, atau external service baru.
