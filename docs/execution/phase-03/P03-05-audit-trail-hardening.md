# P03-05 — Audit Trail Hardening

Status: **Ready**

## Outcome

Sales operational audit trail memiliki event coverage, redaction, retention boundary, dan verification matrix yang cukup untuk pilot outlet.

## Scope

- Audit event coverage matrix untuk approval, void, refund, cash movement, and shift discrepancy.
- Redaction test untuk sensitive sales metadata.
- Query helpers atau report minimum untuk tracing actor, target, reason, outcome, and correlation ID.
- Retention command atau runbook untuk financial audit event pilot.
- Documentation of audit event names and required metadata.

## Out of Scope

- External SIEM integration.
- Tamper-proof storage.
- Long-term archive automation.
- Compliance certification.

## Dependencies

- P03-02 selesai.
- P03-03 selesai.
- P03-04 selesai.

## Acceptance Criteria

- Setiap sensitive action Phase 03 menghasilkan sales audit event.
- Metadata sensitive seperti password, token, secret, recovery code, and card data selalu diredaksi.
- Audit event menyimpan tenant, outlet, actor, target, outcome, reason, correlation ID, and occurred time bila relevan.
- Audit events tidak di-hard-delete oleh business flow.
- Retention policy pilot terdokumentasi.

## Verification

- Feature/unit tests untuk redaction.
- Feature tests atau readiness test untuk event coverage.
- Documentation checklist for event names and metadata.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti dan tanyakan product owner jika audit membutuhkan external storage, immutable ledger cryptographic signing, atau regulatory retention lebih spesifik.
