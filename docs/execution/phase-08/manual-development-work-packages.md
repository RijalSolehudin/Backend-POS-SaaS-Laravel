# Phase 08 Manual Development Work Packages

Status: **Draft for manual implementation**

Dokumen ini dibuat untuk membantu developer junior mengerjakan Phase 08 secara manual, bertahap, dan tetap mengikuti keputusan yang sudah dikunci.

## Dokumen Acuan Wajib

Baca dokumen ini sebelum mulai coding:

- `docs/product/vision-and-scope.md`
- `docs/product/business-rules.md`
- `docs/roadmap/phase-08-growth.md`
- `docs/execution/phase-08/README.md`
- `docs/execution/phase-08/implementation-contract.md`
- `docs/architecture/decisions/043-growth-channels-mvp-policy.md`
- `docs/architecture/development-conventions.md`
- `docs/architecture/web-admin-conventions.md`
- `docs/architecture/api-conventions.md`

Jika ada kebutuhan yang bertentangan dengan dokumen di atas, jangan langsung ubah desain. Berhenti dulu dan lakukan architecture review.

## Tujuan Phase 08

Phase 08 menambahkan channel growth setelah operasi inti stabil:

- Customer dapat membuka QR self-order session tanpa membuat akun.
- Customer cart menjadi pending order request dan wajib dikonfirmasi staff.
- Waiter dapat membuat atau menambah order sesuai permission.
- Payment gateway masuk lewat provider abstraction dan webhook idempotent.
- Reservation minimum dapat dicatat dan dihubungkan ke table session.
- Order dapat memakai satu promotion discount fixed atau percentage.
- Analytics/export minimum tersedia dengan redaction data sensitif.

## Aturan Yang Tidak Boleh Dilanggar

- Customer cart tidak boleh langsung membuat completed Sales order.
- Staff confirmation wajib sebelum item customer masuk Sales/Kitchen.
- QR token harus opaque dan signed; jangan expose tenant/outlet/table raw id sebagai token public.
- Public endpoint hanya boleh membaca data yang aman untuk customer.
- Payment gateway tidak boleh menyimpan card data.
- Webhook wajib signature verification sebelum memproses event.
- Webhook replay dengan provider event id yang sama harus no-op.
- Sales order gateway hanya boleh completed setelah payment intent `paid`.
- Manual cash/non-cash dari Phase 02 tetap tersedia.
- Promotion MVP hanya satu discount per order; stacking ditolak.
- Discount harus dihitung server-side dan disimpan sebagai snapshot.
- Customer identity optional; simpan data minimum.
- Export tidak boleh membawa secret, token, card data, atau raw sensitive payment payload.
- POS/API tetap memakai Sanctum token; jangan campur dengan public QR session.

## Pola Implementasi Repo

Ikuti struktur module yang sudah dipakai module lain:

```text
app/Modules/<Module>/
├── Application/
│   ├── Actions/
│   ├── Data/
│   ├── Contracts/
│   └── Exceptions/
├── Domain/
│   ├── Enums/
│   └── Models/
├── Infrastructure/
│   ├── Persistence/Migrations/
│   └── Providers/
└── Presentation/
    ├── Http/Routes/
    └── Resources/views/
```

Action application harus berbentuk:

```php
final class CreateSomething
{
    public function handle(SomeInput $input): SomeModel
    {
        // business workflow
    }
}
```

Controller hanya boleh:

- menerima request,
- validasi input,
- resolve tenant/outlet/user context,
- memanggil action,
- return JSON, view, atau redirect.

Controller tidak boleh menyimpan workflow bisnis.

## Module Yang Dibuat

Phase 08 memakai module baru berikut:

```text
OrderingChannel
PaymentsGateway
Promotion
Reservation
```

Analytics/export boleh dibuat module-local dulu. Jika export mulai lintas banyak modul dan kompleks, berhenti untuk architecture review sebelum membuat module `Analytics`.

Setiap provider baru wajib didaftarkan di:

```text
bootstrap/providers.php
```

## Urutan Kerja Yang Direkomendasikan

Kerjakan berurutan:

1. `P08-02` QR Self-Order Session
2. `P08-03` Customer Cart and Staff Confirmation
3. `P08-04` Waiter Workflow
4. `P08-05` Payment Gateway Abstraction
5. `P08-06` Reservation Minimum
6. `P08-07` Promotion Discount MVP
7. `P08-08` Analytics Export and Growth Readiness

Jangan mulai payment gateway sebelum flow pending order request dan staff confirmation stabil.

## P08-02 - QR Self-Order Session

Referensi: `docs/execution/phase-08/P08-02-qr-self-order-session.md`

### Outcome

Customer dapat membuka QR session public yang aman dan scoped ke tenant, outlet, dan table atau pickup context.

### Scope

- Buat module `OrderingChannel`.
- Buat table `ordering_qr_sessions`.
- Generate signed opaque token dengan expiry.
- Public endpoint membaca session dan catalog resolved.
- Admin endpoint membuat/revoke QR session.

### Tidak Termasuk

- Jangan membuat cart.
- Jangan membuat order request.
- Jangan membuat Sales order.
- Jangan membuat payment intent.

### Enum

```text
QrSessionStatus:
- active
- expired
- revoked
```

### File Yang Perlu Dibuat

```text
app/Modules/OrderingChannel/Domain/Enums/QrSessionStatus.php
app/Modules/OrderingChannel/Domain/Models/OrderingQrSession.php
app/Modules/OrderingChannel/Application/Actions/CreateQrSession.php
app/Modules/OrderingChannel/Application/Actions/RevokeQrSession.php
app/Modules/OrderingChannel/Application/Actions/ResolveQrSession.php
app/Modules/OrderingChannel/Application/Data/QrSessionInput.php
app/Modules/OrderingChannel/Application/Data/ResolvedQrSession.php
app/Modules/OrderingChannel/Application/Exceptions/OrderingChannelException.php
app/Modules/OrderingChannel/Infrastructure/Providers/OrderingChannelServiceProvider.php
app/Modules/OrderingChannel/Infrastructure/Persistence/Migrations/<timestamp>_create_ordering_qr_sessions_table.php
app/Modules/OrderingChannel/Presentation/Http/Routes/api.php
app/Modules/OrderingChannel/Presentation/Http/Routes/web.php
```

### Migration Draft

`ordering_qr_sessions`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
dining_table_id ULID nullable
table_session_id ULID nullable
context_type string
token_hash string unique
signature string
status string
expires_at timestamp
revoked_at timestamp nullable
created_by_actor_type string
created_by_actor_id string
created_at
updated_at
```

Tambahkan index:

```text
index tenant_id + outlet_id + status
index expires_at
index dining_table_id
index table_session_id
```

`context_type` minimum:

```text
table
pickup
```

### Token Rule

Generate token seperti ini secara konsep:

```text
plain token = random high entropy string
token_hash = hash plain token
signature = HMAC(token, APP_KEY atau signing key config)
public token = token.signature
```

Simpan hanya hash/signature yang dibutuhkan untuk verifikasi. Jangan simpan plain token.

### Endpoint Draft

Admin:

```text
POST /admin/tenants/{tenant}/outlets/{outlet}/qr-sessions
DELETE /admin/tenants/{tenant}/qr-sessions/{session}
```

Public:

```text
GET /qr/{token}
GET /api/public/qr/{token}/catalog
```

Jika memilih satu gaya route public saja, prioritaskan API JSON agar mudah dipakai frontend/mobile web.

### Langkah Coding

1. Buat enum `QrSessionStatus`.
2. Buat migration `ordering_qr_sessions`.
3. Buat model `OrderingQrSession`.
4. Buat exception dengan error code `QR_SESSION_NOT_FOUND` dan `QR_SESSION_EXPIRED`.
5. Buat `CreateQrSession` yang menerima tenant/outlet/table context eksplisit.
6. Validasi tenant/outlet/table aktif dan tidak membaca outlet lain.
7. Generate token opaque dan simpan hash.
8. Buat `ResolveQrSession` untuk public token.
9. `ResolveQrSession` harus cek signature, hash, status, dan expiry.
10. Buat public catalog endpoint yang memanggil action Catalog yang sudah ada.
11. Pastikan catalog ter-scope ke outlet dari QR session.
12. Buat admin revoke endpoint.

### Test Minimal

```text
tests/Feature/OrderingChannel/QrSelfOrderSessionTest.php
```

Test yang wajib ada:

- Admin berwenang bisa membuat QR session.
- Public token valid bisa resolve outlet/catalog.
- Token expired ditolak dengan `QR_SESSION_EXPIRED`.
- Token revoked ditolak.
- Token yang diubah signature-nya ditolak.
- QR outlet A tidak bisa membaca catalog outlet B.
- Plain token tidak disimpan di database.

### Done Jika

- QR session public aman dan scoped.
- Public catalog bisa dibaca lewat token.
- Tidak ada Sales order/cart yang dibuat.
- `composer quality` lulus.

## P08-03 - Customer Cart And Staff Confirmation

Referensi: `docs/execution/phase-08/P08-03-customer-cart-staff-confirmation.md`

### Outcome

Customer cart menjadi pending order request dan hanya masuk Sales setelah staff confirmation.

### Scope

- Buat `ordering_customer_carts`.
- Buat `ordering_customer_cart_items`.
- Buat `ordering_order_requests`.
- Validate customer selection dari POS catalog.
- Staff confirm/reject order request.
- Confirm membuat atau menambah Sales order.

### Tidak Termasuk

- Jangan complete payment.
- Jangan auto-send kitchen sebelum Sales confirmation berhasil.
- Jangan simpan customer account.

### Enum

```text
OrderRequestStatus:
- pending
- confirmed
- rejected
- expired
```

### File Yang Perlu Dibuat

```text
app/Modules/OrderingChannel/Domain/Enums/OrderRequestStatus.php
app/Modules/OrderingChannel/Domain/Models/OrderingCustomerCart.php
app/Modules/OrderingChannel/Domain/Models/OrderingCustomerCartItem.php
app/Modules/OrderingChannel/Domain/Models/OrderingOrderRequest.php
app/Modules/OrderingChannel/Application/Actions/CreateCustomerCart.php
app/Modules/OrderingChannel/Application/Actions/AddCustomerCartItem.php
app/Modules/OrderingChannel/Application/Actions/UpdateCustomerCartItem.php
app/Modules/OrderingChannel/Application/Actions/SubmitCustomerCart.php
app/Modules/OrderingChannel/Application/Actions/ConfirmOrderRequest.php
app/Modules/OrderingChannel/Application/Actions/RejectOrderRequest.php
app/Modules/OrderingChannel/Application/Data/CustomerCartInput.php
app/Modules/OrderingChannel/Application/Data/CustomerCartItemInput.php
app/Modules/OrderingChannel/Application/Data/OrderRequestConfirmationInput.php
app/Modules/OrderingChannel/Infrastructure/Persistence/Migrations/<timestamp>_create_ordering_customer_cart_tables.php
```

### Migration Draft

`ordering_customer_carts`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
qr_session_id ULID
status string
customer_name string nullable
customer_phone string nullable
notes text nullable
expires_at timestamp
submitted_at timestamp nullable
created_at
updated_at
```

`ordering_customer_cart_items`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
customer_cart_id ULID
product_id ULID
variant_id ULID nullable
quantity decimal/string sesuai pola Sales
notes text nullable
catalog_snapshot json
created_at
updated_at
```

`ordering_order_requests`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
qr_session_id ULID
customer_cart_id ULID
sales_order_id ULID nullable
status string
idempotency_key string
confirmed_by_actor_type string nullable
confirmed_by_actor_id string nullable
confirmed_at timestamp nullable
rejected_by_actor_type string nullable
rejected_by_actor_id string nullable
rejected_at timestamp nullable
rejection_reason text nullable
expires_at timestamp
created_at
updated_at
```

Tambahkan unique/index:

```text
unique tenant_id + outlet_id + idempotency_key
unique customer_cart_id
index tenant_id + outlet_id + status
index qr_session_id
index sales_order_id
```

### Staff Confirmation Rule

`ConfirmOrderRequest` harus:

1. Lock order request.
2. Pastikan status `pending`.
3. Validasi cart belum expired.
4. Validasi item masih available di POS catalog.
5. Buat atau update Sales order lewat action Sales yang sudah ada.
6. Link `sales_order_id`.
7. Ubah status menjadi `confirmed`.
8. Retry dengan idempotency key yang sama tidak boleh menggandakan order/item.

### Endpoint Draft

Public:

```text
POST /api/public/qr/{token}/cart
POST /api/public/qr/{token}/cart/items
PUT /api/public/qr/{token}/cart/items/{item}
POST /api/public/qr/{token}/cart/submit
```

Staff:

```text
GET /admin/tenants/{tenant}/order-requests
POST /admin/tenants/{tenant}/order-requests/{request}/confirm
POST /admin/tenants/{tenant}/order-requests/{request}/reject
```

### Langkah Coding

1. Buat enum `OrderRequestStatus`.
2. Buat migration cart/item/order request.
3. Buat model cart/item/order request.
4. Buat public action cart yang selalu resolve QR session lebih dulu.
5. Validasi item dari resolved POS catalog, bukan input bebas.
6. Simpan catalog snapshot minimum untuk audit tampilan customer.
7. Buat submit cart menjadi order request `pending`.
8. Buat staff listing pending request per tenant/outlet.
9. Buat confirm/reject action dengan authorization staff.
10. Integrasikan confirm ke Sales action yang paling dekat dengan flow existing.
11. Pastikan rejected/expired request tidak membuat Sales order.

### Error Code Yang Dipakai

```text
ORDER_REQUEST_NOT_FOUND
ORDER_REQUEST_INVALID_STATE
QR_SESSION_NOT_FOUND
QR_SESSION_EXPIRED
```

### Test Minimal

```text
tests/Feature/OrderingChannel/CustomerCartStaffConfirmationTest.php
```

Test yang wajib ada:

- Customer bisa membuat cart dari QR valid.
- Customer tidak bisa submit item unavailable.
- Submit cart membuat order request `pending`.
- Confirm membuat Sales order tepat satu kali.
- Retry confirm tidak menggandakan Sales order/item.
- Reject mengubah status dan tidak membuat Sales order.
- Staff tenant A tidak bisa confirm request tenant B.
- Expired QR/cart ditolak.

### Done Jika

- Customer order masuk sebagai pending request.
- Staff confirmation idempotent.
- Sales integration regression lulus.
- `composer quality` lulus.

## P08-04 - Waiter Workflow

Referensi: `docs/execution/phase-08/P08-04-waiter-workflow.md`

### Outcome

Waiter dapat membuat dan menambah order untuk table/outlet sesuai permission.

### Scope

- Tambahkan waiter order source.
- Tambahkan role/permission check waiter.
- Integrasikan ke table session bila dine-in.
- Buat UI/API minimum untuk waiter flow.

### Tidak Termasuk

- Jangan ubah payment lifecycle.
- Jangan bypass Sales validation.
- Jangan membuat role platform untuk waiter.

### File Yang Mungkin Diubah

```text
app/Modules/Identity/Application/Enums/TenantPermission.php
app/Modules/Identity/Application/Services/PredefinedTenantRolePolicy.php
app/Modules/Sales/Application/Data/OrderItemSelection.php
app/Modules/Sales/Application/Actions/CreateDraftOrder.php
app/Modules/Sales/Application/Actions/AddOrderItem.php
app/Modules/Tenancy/Application/Services/TenantPermissionGuard.php
```

Jika perlu action wrapper baru:

```text
app/Modules/OrderingChannel/Application/Actions/CreateWaiterOrder.php
app/Modules/OrderingChannel/Application/Actions/AddWaiterOrderItem.php
app/Modules/OrderingChannel/Application/Data/WaiterOrderInput.php
```

### Permission Draft

Tambahkan permission bila belum ada:

```text
operate_waiter_orders
```

Tenant Owner dan Outlet Manager boleh mendapat permission ini. Waiter role hanya ditambahkan jika product owner memang meminta role eksplisit. Jika tidak ada role waiter di policy saat ini, gunakan role paling dekat yang sudah ada dan catat follow-up.

### Order Source Draft

Tambahkan source pada order/order audit bila model Sales mendukung:

```text
pos
qr_customer
waiter
```

Jika Sales belum punya kolom/source, berhenti sejenak dan buat perubahan additive yang tidak merusak flow POS existing.

### Langkah Coding

1. Identifikasi action Sales untuk create draft order dan add item.
2. Tambahkan permission guard waiter.
3. Buat action wrapper waiter agar controller tidak langsung mengorkestrasi Sales/Dining.
4. Untuk dine-in, resolve table session dari Dining.
5. Pastikan waiter hanya bisa akses tenant/outlet assignment miliknya.
6. Create order tetap memakai Sales transaction/rules.
7. Add item tetap memakai catalog dan Sales calculation existing.
8. Payment tetap dilakukan lewat flow Sales yang sudah ada.

### Test Minimal

```text
tests/Feature/OrderingChannel/WaiterWorkflowTest.php
```

Test yang wajib ada:

- Waiter berwenang bisa membuat order outlet assigned.
- Waiter tidak bisa membuat order outlet lain.
- Waiter bisa add item ke table session open.
- Add item ke closed/cancelled table session ditolak.
- Payment state tidak berubah oleh waiter add item.
- Tenant/outlet isolation lulus.

### Done Jika

- Waiter order memakai Sales rules.
- Table session integration tidak merusak Dining.
- Permission test lulus.
- `composer quality` lulus.

## P08-05 - Payment Gateway Abstraction

Referensi: `docs/execution/phase-08/P08-05-payment-gateway-abstraction.md`

### Outcome

Sales order dapat dibayar melalui provider gateway dengan webhook aman dan idempotent.

### Scope

- Buat module `PaymentsGateway`.
- Buat payment provider contract.
- Buat payment intent.
- Buat webhook inbox.
- Verify signature dan process `paid`/`failed` events.

### Tidak Termasuk

- Jangan menyimpan card data.
- Jangan membuat multi-provider settlement reconciliation.
- Jangan mengganti manual payment existing.
- Jangan menerima webhook tanpa signature verification.

### Enum

```text
PaymentIntentStatus:
- pending
- requires_action
- paid
- failed
- expired
- cancelled
```

### File Yang Perlu Dibuat

```text
app/Modules/PaymentsGateway/Application/Contracts/PaymentProvider.php
app/Modules/PaymentsGateway/Application/Actions/CreatePaymentIntent.php
app/Modules/PaymentsGateway/Application/Actions/HandlePaymentWebhook.php
app/Modules/PaymentsGateway/Application/Actions/MarkPaymentIntentPaid.php
app/Modules/PaymentsGateway/Application/Data/PaymentIntentInput.php
app/Modules/PaymentsGateway/Application/Data/PaymentProviderIntent.php
app/Modules/PaymentsGateway/Application/Data/PaymentWebhookPayload.php
app/Modules/PaymentsGateway/Application/Exceptions/PaymentGatewayException.php
app/Modules/PaymentsGateway/Domain/Enums/PaymentIntentStatus.php
app/Modules/PaymentsGateway/Domain/Models/PaymentGatewayIntent.php
app/Modules/PaymentsGateway/Domain/Models/PaymentGatewayWebhookEvent.php
app/Modules/PaymentsGateway/Infrastructure/Providers/PaymentsGatewayServiceProvider.php
app/Modules/PaymentsGateway/Infrastructure/Providers/FakePaymentProvider.php
app/Modules/PaymentsGateway/Infrastructure/Persistence/Migrations/<timestamp>_create_payment_gateway_tables.php
app/Modules/PaymentsGateway/Presentation/Http/Routes/api.php
```

### Migration Draft

`payment_gateway_intents`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
sales_order_id ULID
provider string
provider_intent_id string nullable
status string
amount_minor unsigned big integer
currency string default IDR
checkout_url text nullable
expires_at timestamp nullable
paid_at timestamp nullable
failed_at timestamp nullable
failure_reason text nullable
metadata json nullable
created_at
updated_at
```

`payment_gateway_webhook_events`:

```text
id ULID primary
provider string
provider_event_id string
event_type string
signature_hash string nullable
payload_redacted json
processed_at timestamp nullable
created_at
updated_at
```

Tambahkan unique/index:

```text
unique provider + provider_event_id
unique provider + provider_intent_id
index tenant_id + outlet_id + sales_order_id
index status
```

### Provider Contract Draft

```php
interface PaymentProvider
{
    public function createIntent(PaymentIntentInput $input): PaymentProviderIntent;

    public function verifyWebhookSignature(string $payload, ?string $signature): bool;

    public function parseWebhook(string $payload): PaymentWebhookPayload;
}
```

Mulai dengan fake/manual provider untuk test. Provider real hanya boleh ditambahkan setelah config secret, signature, retry, dan runbook jelas.

### Endpoint Draft

```text
POST /api/v1/payment-gateway/intents
POST /api/payment-gateway/{provider}/webhook
```

Webhook route boleh public, tetapi harus signature verification dan idempotency.

### Langkah Coding

1. Buat enum `PaymentIntentStatus`.
2. Buat migration intent dan webhook inbox.
3. Buat model intent dan webhook event.
4. Buat `PaymentProvider` contract.
5. Buat fake provider untuk test/local.
6. Buat `CreatePaymentIntent` yang lock Sales order dan validasi amount.
7. Jangan buat intent untuk order yang sudah paid/cancelled/voided.
8. Buat `HandlePaymentWebhook`.
9. Verify signature sebelum menyimpan efek bisnis.
10. Simpan webhook event dengan unique provider/event id.
11. Jika event id sudah pernah processed, return success no-op.
12. Saat event `paid`, update intent menjadi `paid`.
13. Panggil Sales action untuk complete order/payment sesuai boundary existing.
14. Saat event `failed`, update intent menjadi `failed` tanpa complete Sales order.

### Error Code Yang Dipakai

```text
PAYMENT_PROVIDER_SIGNATURE_INVALID
PAYMENT_INTENT_NOT_FOUND
PAYMENT_INTENT_INVALID_STATE
```

### Test Minimal

```text
tests/Feature/PaymentsGateway/PaymentGatewayAbstractionTest.php
```

Test yang wajib ada:

- Bisa create payment intent untuk order valid.
- Tidak bisa create intent amount mismatch.
- Webhook signature invalid ditolak.
- Webhook paid mengubah intent menjadi paid.
- Webhook paid complete Sales order sesuai rule.
- Webhook replay event id sama no-op.
- Failed webhook tidak complete Sales order.
- Payload redacted tidak menyimpan secret/card data.

### Done Jika

- Gateway abstraction tersedia.
- Webhook idempotent dan signature-verified.
- Sales completion hanya setelah `paid`.
- `composer quality` lulus.

## P08-06 - Reservation Minimum

Referensi: `docs/execution/phase-08/P08-06-reservation-minimum.md`

### Outcome

Outlet dapat mencatat reservation minimum dan menghubungkannya ke table session saat customer datang.

### Scope

- Buat module `Reservation`.
- Buat table `reservations`.
- Lifecycle `pending`, `confirmed`, `seated`, `cancelled`, `no_show`.
- Link reservation ke table session.

### Tidak Termasuk

- Jangan buat deposit/payment reservation.
- Jangan buat reminder SMS/WhatsApp.
- Jangan buat customer CRM.

### Enum

```text
ReservationStatus:
- pending
- confirmed
- seated
- cancelled
- no_show
```

### File Yang Perlu Dibuat

```text
app/Modules/Reservation/Domain/Enums/ReservationStatus.php
app/Modules/Reservation/Domain/Models/Reservation.php
app/Modules/Reservation/Application/Actions/CreateReservation.php
app/Modules/Reservation/Application/Actions/ConfirmReservation.php
app/Modules/Reservation/Application/Actions/SeatReservation.php
app/Modules/Reservation/Application/Actions/CancelReservation.php
app/Modules/Reservation/Application/Actions/MarkReservationNoShow.php
app/Modules/Reservation/Application/Data/ReservationInput.php
app/Modules/Reservation/Application/Exceptions/ReservationException.php
app/Modules/Reservation/Infrastructure/Providers/ReservationServiceProvider.php
app/Modules/Reservation/Infrastructure/Persistence/Migrations/<timestamp>_create_reservations_table.php
app/Modules/Reservation/Presentation/Http/Routes/web.php
```

### Migration Draft

`reservations`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
dining_table_id ULID nullable
table_session_id ULID nullable
customer_name string nullable
customer_phone string nullable
party_size unsigned small integer
reserved_at timestamp
status string
notes text nullable
created_by_actor_type string
created_by_actor_id string
confirmed_at timestamp nullable
seated_at timestamp nullable
cancelled_at timestamp nullable
no_show_at timestamp nullable
created_at
updated_at
```

Tambahkan index:

```text
index tenant_id + outlet_id + reserved_at
index tenant_id + outlet_id + status
index dining_table_id
index table_session_id
```

### Lifecycle Rule

Minimum transition:

```text
pending -> confirmed
pending -> cancelled
confirmed -> seated
confirmed -> cancelled
confirmed -> no_show
```

Jangan izinkan:

```text
seated -> cancelled
cancelled -> seated
no_show -> seated
```

### Langkah Coding

1. Buat enum `ReservationStatus`.
2. Buat migration dan model.
3. Buat action create/confirm/seat/cancel/no-show.
4. Validasi tenant/outlet/table aktif.
5. Customer name/phone optional; jangan wajibkan customer account.
6. Saat seat, buka atau link ke table session lewat action Dining.
7. Pastikan table session tenant/outlet sama.
8. Buat Web Admin minimum untuk daftar reservation dan action lifecycle.
9. Jangan catat data payment/card di reservation.

### Error Code Yang Dipakai

```text
RESERVATION_NOT_FOUND
RESERVATION_INVALID_STATE
```

### Test Minimal

```text
tests/Feature/Reservation/ReservationMinimumTest.php
```

Test yang wajib ada:

- Bisa membuat reservation dengan data customer minimum.
- Customer name/phone boleh null sesuai privacy rule.
- Confirm reservation mengubah status.
- Seat reservation link ke table session.
- Cancel/no-show mengikuti valid transition.
- Invalid transition ditolak.
- Tenant/outlet isolation lulus.

### Done Jika

- Reservation lifecycle jalan.
- Link table session aman.
- Privacy minimum dipatuhi.
- `composer quality` lulus.

## P08-07 - Promotion Discount MVP

Referensi: `docs/execution/phase-08/P08-07-promotion-discount-mvp.md`

### Outcome

Order dapat memakai satu discount fixed/percentage yang dihitung server-side dan tersnapshot.

### Scope

- Buat module `Promotion`.
- Buat `promotion_rules`.
- Buat `sales_order_discounts`.
- Apply one promotion per order.
- Update total calculation sesuai ADR dan Sales boundary.

### Tidak Termasuk

- Jangan buat stacking promotion.
- Jangan buat loyalty.
- Jangan buat tax/service calculation baru.
- Jangan simpan calculation hanya dari client.

### Enum

```text
PromotionStatus:
- active
- inactive

PromotionType:
- fixed_amount
- percentage
```

### File Yang Perlu Dibuat

```text
app/Modules/Promotion/Domain/Enums/PromotionStatus.php
app/Modules/Promotion/Domain/Enums/PromotionType.php
app/Modules/Promotion/Domain/Models/PromotionRule.php
app/Modules/Promotion/Domain/Models/SalesOrderDiscount.php
app/Modules/Promotion/Application/Actions/CreatePromotionRule.php
app/Modules/Promotion/Application/Actions/UpdatePromotionRule.php
app/Modules/Promotion/Application/Actions/ChangePromotionStatus.php
app/Modules/Promotion/Application/Actions/ApplyPromotionToOrder.php
app/Modules/Promotion/Application/Actions/RemovePromotionFromOrder.php
app/Modules/Promotion/Application/Data/PromotionRuleInput.php
app/Modules/Promotion/Application/Data/PromotionCalculation.php
app/Modules/Promotion/Application/Exceptions/PromotionException.php
app/Modules/Promotion/Infrastructure/Providers/PromotionServiceProvider.php
app/Modules/Promotion/Infrastructure/Persistence/Migrations/<timestamp>_create_promotion_tables.php
app/Modules/Promotion/Presentation/Http/Routes/web.php
```

### Migration Draft

`promotion_rules`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID nullable
name string
type string
value_minor unsigned big integer nullable
percentage decimal/string nullable
status string
starts_at timestamp nullable
ends_at timestamp nullable
created_at
updated_at
```

`sales_order_discounts`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
sales_order_id ULID
promotion_rule_id ULID nullable
promotion_name_snapshot string
type string
value_snapshot string
amount_minor unsigned big integer
reason string nullable
source string
created_by_actor_type string
created_by_actor_id string
created_at
updated_at
```

Tambahkan unique/index:

```text
unique sales_order_id
index tenant_id + outlet_id
index promotion_rule_id
```

Unique `sales_order_id` adalah enforcement single promotion per order.

### Calculation Rule

Fixed amount:

```text
discount = min(configured_amount, order_subtotal)
```

Percentage:

```text
discount = floor(order_subtotal * percentage / 100)
```

Gunakan minor unit integer untuk amount uang. Jangan memakai float untuk amount final.

### Langkah Coding

1. Buat enum status dan type.
2. Buat migration rules dan snapshot.
3. Buat model.
4. Buat CRUD promotion rule minimum.
5. Buat calculator server-side.
6. `ApplyPromotionToOrder` harus lock order.
7. Tolak jika order sudah completed/voided/cancelled.
8. Tolak jika order sudah punya discount snapshot.
9. Simpan snapshot lengkap: promotion id, name, type, value, amount minor, reason/source.
10. Integrasikan total order lewat Sales action/boundary yang benar.
11. Pastikan perubahan promotion rule setelah apply tidak mengubah snapshot lama.

### Error Code Yang Dipakai

```text
PROMOTION_NOT_FOUND
PROMOTION_INVALID
```

### Test Minimal

```text
tests/Feature/Promotion/PromotionDiscountMvpTest.php
```

Test yang wajib ada:

- Fixed amount dihitung benar.
- Fixed amount tidak boleh melebihi subtotal.
- Percentage dihitung server-side.
- Tidak bisa apply dua promotion ke satu order.
- Snapshot tidak berubah saat promotion rule diedit.
- Inactive/expired promotion ditolak.
- Tenant/outlet isolation lulus.

### Done Jika

- Single discount berjalan.
- Snapshot immutable.
- Sales total regression lulus.
- `composer quality` lulus.

## P08-08 - Analytics Export And Growth Readiness

Referensi: `docs/execution/phase-08/P08-08-analytics-export-growth-readiness.md`

### Outcome

Growth features memiliki analytics/export minimum dan readiness evidence.

### Scope

- Buat `analytics_exports`.
- Buat export request/result untuk sales/growth data minimum.
- Redact sensitive payment/customer data.
- Buat readiness runbook.
- Update roadmap/execution status.

### Tidak Termasuk

- Jangan buat BI dashboard kompleks.
- Jangan buat data warehouse.
- Jangan export secret, token, raw webhook payload sensitif, atau card data.

### File Yang Perlu Dibuat

```text
app/Modules/OrderingChannel/Application/Actions/SummarizeOrderingChannel.php
app/Modules/PaymentsGateway/Application/Actions/SummarizePaymentGateway.php
app/Modules/Promotion/Application/Actions/SummarizePromotionUsage.php
app/Modules/Reservation/Application/Actions/SummarizeReservations.php
app/Modules/OrderingChannel/Application/Actions/CreateAnalyticsExport.php
app/Modules/OrderingChannel/Application/Actions/DownloadAnalyticsExport.php
app/Modules/OrderingChannel/Domain/Models/AnalyticsExport.php
app/Modules/OrderingChannel/Infrastructure/Persistence/Migrations/<timestamp>_create_analytics_exports_table.php
docs/runbooks/phase-08-growth-readiness.md
```

Jika export sudah terlalu lintas modul, buat module `Analytics` setelah architecture review.

### Migration Draft

`analytics_exports`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID nullable
type string
status string
requested_by_actor_type string
requested_by_actor_id string
parameters json
file_path string nullable
failure_reason text nullable
completed_at timestamp nullable
created_at
updated_at
```

Status minimum:

```text
pending
completed
failed
```

### Export Type Minimum

```text
sales_summary
qr_order_requests
payment_intents
promotion_usage
reservations
```

### Redaction Rule

Export boleh berisi:

```text
order id
date/time
tenant/outlet id
status
amount minor
discount amount minor
payment intent status
reservation status
customer name/phone jika dibutuhkan operasional
```

Export tidak boleh berisi:

```text
session token
plain QR token
Sanctum token
payment provider secret
webhook signature
raw card/payment data
password hash
TOTP secret
recovery code
full raw webhook payload
```

### Langkah Coding

1. Buat migration `analytics_exports`.
2. Buat model export.
3. Buat action summary per feature.
4. Buat export CSV/JSON sederhana.
5. Simpan file ke disk Laravel configured storage.
6. Terapkan authorization tenant/outlet.
7. Terapkan redaction sebelum file ditulis.
8. Catat status `completed` atau `failed`.
9. Buat runbook readiness Phase 08.
10. Update `docs/execution/phase-08/README.md` status work package yang selesai.

### Error Code Yang Dipakai

```text
ANALYTICS_EXPORT_FAILED
```

### Test Minimal

```text
tests/Feature/Growth/AnalyticsExportGrowthReadinessTest.php
```

Test yang wajib ada:

- User berwenang bisa request export.
- Tenant A tidak bisa export tenant B.
- Export redacts token/signature/secret.
- Failed export menyimpan failure reason.
- Growth critical path regression lulus.

### Done Jika

- Export minimum tersedia.
- Redaction test lulus.
- Runbook readiness ada.
- `composer quality` lulus.
- `npm run build` lulus bila frontend berubah.

## Checklist Pull Request Per Work Package

Setiap PR Phase 08 wajib mencantumkan:

```text
Work package:
Docs followed:
Tables added/changed:
Actions added/changed:
Routes added/changed:
Security/privacy notes:
Tests added:
Commands run:
```

Minimal command:

```bash
composer quality
```

Jika frontend berubah:

```bash
npm run build
```

Jika migration berubah, sertakan:

```bash
php artisan migrate:fresh --env=testing
```

Gunakan command testing project yang sudah disepakati di runbook/development conventions jika environment lokal berbeda.

## Stop Rule Phase 08

Berhenti dan tanyakan product owner/architecture owner jika implementasi membutuhkan:

- loyalty points,
- campaign stacking,
- marketplace delivery,
- full CRM/customer account,
- multi-provider payment settlement reconciliation,
- card storage,
- real payment provider secret production,
- tax/service charge calculation baru,
- offline QR ordering,
- public customer authentication,
- data warehouse/BI pipeline.

## Final Readiness Checklist

Phase 08 dianggap siap pilot jika:

- QR session expiry/revocation/security tests lulus.
- Staff confirmation idempotency tests lulus.
- Waiter permission dan table session regression lulus.
- Webhook signature/idempotency tests lulus.
- Promotion calculation snapshot tests lulus.
- Reservation lifecycle tests lulus.
- Export authorization/redaction tests lulus.
- Scheduler/queue baseline tetap sesuai `docs/runbooks/operational-baseline.md`.
- `composer quality` lulus.
- `npm run build` lulus jika ada frontend.
