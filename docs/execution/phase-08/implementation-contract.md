# Phase 08 Implementation Contract

Dokumen ini mengunci detail teknis Phase 08 agar implementer tidak membuat keputusan growth/payment baru.

## Modules

- `OrderingChannel`: QR session, customer cart, pending order request, waiter order source.
- `PaymentsGateway`: provider abstraction, payment intent, webhook inbox.
- `Promotion`: discount rule and order discount snapshot.
- `Reservation`: reservation minimum.
- Analytics/export boleh module-local report actions sampai perlu module khusus.

## Tables

| Table | Owner | Purpose |
|---|---|---|
| `ordering_qr_sessions` | P08-02 | Public QR session |
| `ordering_customer_carts` | P08-03 | Customer cart header |
| `ordering_customer_cart_items` | P08-03 | Customer selected items |
| `ordering_order_requests` | P08-03 | Pending staff confirmation |
| `payment_gateway_intents` | P08-05 | Provider payment intent |
| `payment_gateway_webhook_events` | P08-05 | Idempotent webhook inbox |
| `reservations` | P08-06 | Reservation minimum |
| `promotion_rules` | P08-07 | Discount rule |
| `sales_order_discounts` | P08-07 | Discount snapshot on order |
| `analytics_exports` | P08-08 | Export request/result |

## Enums

| Enum | Values |
|---|---|
| `QrSessionStatus` | `active`, `expired`, `revoked` |
| `OrderRequestStatus` | `pending`, `confirmed`, `rejected`, `expired` |
| `PaymentIntentStatus` | `pending`, `requires_action`, `paid`, `failed`, `expired`, `cancelled` |
| `ReservationStatus` | `pending`, `confirmed`, `seated`, `cancelled`, `no_show` |
| `PromotionStatus` | `active`, `inactive` |

## QR And Customer Order Rules

- QR token is signed opaque random token, not raw table/outlet id.
- QR session expires by config.
- Public customer cart validates against resolved POS catalog.
- Staff confirmation creates/updates Sales order.
- Rejected/expired request never creates Sales order.

## Payment Gateway Rules

- Do not store card data.
- Provider webhook must verify signature before processing.
- Webhook event unique key: provider + event id.
- Payment intent links to Sales order.
- Sales completion via gateway waits for `paid`.
- Webhook replay with same event id is no-op.

## Promotion Rules

- Single promotion per order.
- Fixed amount discount cannot exceed subtotal.
- Percentage discount uses server-side calculation and snapshot.
- Discount snapshot includes promotion id, name, type, value, amount minor, and reason/source.

## Privacy Rules

- Customer name/phone optional.
- Store only fields needed for reservation/order communication.
- Export must exclude secrets and payment provider raw sensitive payload.

## Error Codes

- `QR_SESSION_NOT_FOUND`
- `QR_SESSION_EXPIRED`
- `ORDER_REQUEST_NOT_FOUND`
- `ORDER_REQUEST_INVALID_STATE`
- `PAYMENT_PROVIDER_SIGNATURE_INVALID`
- `PAYMENT_INTENT_NOT_FOUND`
- `PAYMENT_INTENT_INVALID_STATE`
- `RESERVATION_NOT_FOUND`
- `RESERVATION_INVALID_STATE`
- `PROMOTION_NOT_FOUND`
- `PROMOTION_INVALID`
- `ANALYTICS_EXPORT_FAILED`

## Testing Baseline

- QR session expiry/security tests.
- Staff confirmation idempotency tests.
- Webhook signature/idempotency tests.
- Promotion calculation snapshot tests.
- Reservation lifecycle tests.
- Export authorization/redaction tests.
- `composer quality`.
