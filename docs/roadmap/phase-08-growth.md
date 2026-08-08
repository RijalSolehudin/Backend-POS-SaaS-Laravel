# Phase 08: Growth Features

Status: **Implemented — Pending MariaDB Verification**

## Outcome

Menambah channel penjualan dan capability pertumbuhan setelah operasi inti stabil.

## Candidate Scope

- QR customer self-order.
- Waiter workflow.
- Payment gateway.
- Reservation.
- Promotion/discount lanjutan.
- Expanded analytics dan exports.

Setiap candidate dapat dipecah menjadi phase tersendiri setelah prioritas bisnis diputuskan.

## Architecture Decisions Required

- Accepted decision: [ADR-043 Growth Channels MVP Policy](../architecture/decisions/043-growth-channels-mvp-policy.md).
- Priority order: QR self-order, waiter workflow, payment gateway, reservation, promotion, analytics/export.
- QR customer cart menjadi pending order request dan wajib staff confirmation.
- Payment gateway memakai provider abstraction dan webhook signature/idempotency.
- Customer identity optional dan privacy scope minimum.

## Acceptance Criteria

Acceptance criteria ditetapkan per candidate setelah scope dan keputusan arsitekturnya disetujui. Tidak ada candidate yang dianggap otomatis termasuk hanya karena tercantum di daftar ini.

Phase 08 execution plan memecah candidate menjadi work package dengan acceptance criteria masing-masing.
