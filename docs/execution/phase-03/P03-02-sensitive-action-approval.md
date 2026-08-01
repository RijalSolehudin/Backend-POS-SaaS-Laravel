# P03-02 — Sensitive Action Approval

Status: **Done**

## Outcome

Sensitive financial and operational actions memiliki approval flow minimum yang mengikat performer, approver, reason, waktu, target resource, tenant, outlet, dan correlation ID.

## Scope

- Approval request model untuk sensitive actions.
- Supervisor approval boundary untuk:
  - void completed order;
  - full refund manual;
  - close shift dengan discrepancy di atas threshold;
  - cash out di atas threshold.
- Authorization policy untuk Tenant Owner dan Outlet Manager sebagai approver minimum.
- Idempotency untuk approval submission.
- Audit event untuk approval created, approved, rejected, expired, dan consumed.
- Problem Details response untuk approval missing, forbidden, expired, already consumed, dan idempotency conflict.

## Out of Scope

- Refund/reversal execution.
- Cash movement execution.
- Custom approval workflow builder.
- Push notification atau real-time approval.
- Payment gateway settlement.

## Dependencies

- P03-01 selesai.
- [ADR-038 Operational Safety MVP Policy](../../architecture/decisions/038-operational-safety-mvp-policy.md) accepted.

## Acceptance Criteria

- Cashier tidak bisa menjalankan sensitive action yang membutuhkan supervisor tanpa approval valid.
- Approver tidak boleh sama dengan performer pada financial sensitive action.
- Approver harus aktif pada tenant/outlet target dan memiliki role minimum Tenant Owner atau Outlet Manager.
- Approval hanya bisa dipakai untuk action, target, tenant, outlet, performer, dan request fingerprint yang sama.
- Approval yang sudah consumed tidak bisa dipakai ulang.
- Retry approval submission dengan idempotency key dan fingerprint sama mengembalikan hasil pertama.
- Retry dengan idempotency key sama tetapi fingerprint berbeda menghasilkan `409`.
- Semua lifecycle approval menghasilkan audit event non-secret.

## Verification

- Feature tests untuk happy path approval.
- Feature tests untuk forbidden approver, same performer/approver, consumed approval, dan fingerprint mismatch.
- Feature tests untuk idempotent replay.
- `composer quality` lulus.

## Evidence

- `tests/Feature/Sales/SensitiveActionApprovalTest.php` verifies idempotent approval creation, supervisor approval, approval consumption, same-actor rejection, cashier approver rejection, and fingerprint mismatch.
- `tests/Feature/Sales/CancelVoidFlowTest.php` verifies completed order void now requires approved supervisor approval.
- POS API exposes `POST /api/v1/pos/outlets/{outlet}/orders/{order}/void` with Problem Details responses for approval failures.
- Sales audit events capture approval lifecycle and consumed sensitive action evidence.

## Architecture Stop Rule

Berhenti dan tanyakan product owner jika approval membutuhkan custom rule per tenant, approval bertingkat, remote approval, notification real-time, atau override tanpa approver.
