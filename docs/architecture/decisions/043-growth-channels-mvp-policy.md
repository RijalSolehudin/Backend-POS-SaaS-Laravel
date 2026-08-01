# ADR-043: Growth Channels MVP Policy

- Status: Accepted
- Date: 2026-08-01

## Context

Phase 08 menambah channel pertumbuhan seperti QR self-order, waiter workflow, payment gateway, reservation, promotion, analytics, dan export. Scope perlu dipaku agar growth feature tidak mencampur customer privacy, payment settlement, dan promotion engine tanpa boundary.

## Decision

### Priority Order

Phase 08 dikerjakan berurutan:

1. QR customer self-order.
2. Waiter workflow.
3. Payment gateway abstraction and webhook.
4. Reservation minimum.
5. Promotion/discount MVP.
6. Analytics and export.

### QR Self-Order

- Public QR session tidak membutuhkan customer account.
- QR session scoped tenant/outlet/table or pickup context.
- QR session memiliki expiry dan signed opaque token.
- Customer cart menjadi pending order request, bukan langsung completed Sales order.
- Staff confirmation wajib sebelum item masuk Sales/Kitchen.

### Waiter Workflow

- Waiter adalah tenant user dengan role/permission.
- Waiter dapat membuat/menambah order untuk table session/outlet.
- Payment tetap mengikuti Sales rules.

### Payment Gateway

- Gateway integration memakai abstraction `PaymentProvider`.
- Webhook wajib signature verification dan idempotency.
- Payment intent status: `pending`, `requires_action`, `paid`, `failed`, `expired`, `cancelled`.
- Sales order completed hanya setelah paid confirmation valid.
- Manual cash/non-cash dari Phase 02 tetap tersedia.

### Promotion

- Promotion MVP hanya fixed amount atau percentage discount.
- Promotion dihitung server-side.
- Discount snapshot disimpan pada order.
- Stacking promotion ditolak pada MVP.
- Tax/service calculation tetap ditunda bila belum ada ADR terpisah.

### Privacy

- Customer identity optional.
- Simpan data customer minimum yang dibutuhkan untuk reservation/order.
- Jangan menyimpan payment card data.

## Deferred Scope

- Loyalty points.
- Complex campaign/segmentation.
- Multi-provider payment settlement reconciliation.
- Marketplace delivery integration.
- Advanced CRM.

## Approval

Product owner menyetujui keputusan ini sebagai pre-accepted Phase 08 implementation policy pada 2026-08-01.
