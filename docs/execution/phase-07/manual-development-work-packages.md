# Phase 07 Manual Development Work Packages

Status: **Draft for manual implementation**

Dokumen ini dibuat untuk membantu developer junior melanjutkan Phase 07 secara manual, bertahap, dan tetap mengikuti dokumen yang sudah dikunci.

## Dokumen Acuan Wajib

Baca dokumen ini sebelum mulai coding:

- `docs/product/vision-and-scope.md`
- `docs/product/business-rules.md`
- `docs/roadmap/phase-07-dining-kitchen.md`
- `docs/execution/phase-07/README.md`
- `docs/execution/phase-07/implementation-contract.md`
- `docs/architecture/decisions/042-dining-kitchen-mvp-policy.md`
- `docs/architecture/development-conventions.md`
- `docs/architecture/web-admin-conventions.md`

Jika ada kebutuhan yang bertentangan dengan dokumen di atas, jangan langsung ubah desain. Berhenti dulu dan lakukan architecture review.

## Tujuan Phase 07

Phase 07 menambahkan fitur dine-in dan kitchen workflow:

- Tenant admin dapat mengelola floor dan table per outlet.
- Table dapat memiliki table session sebagai sumber status occupancy.
- Sales order dapat dihubungkan ke table session.
- Confirmed Sales order item dapat masuk ke kitchen station yang benar.
- Kitchen ticket dapat diproses dari `queued` sampai `served`.
- Realtime KDS bersifat notifikasi, bukan sumber data utama.
- Printer dispatch bersifat best-effort dan tidak boleh membatalkan order/payment.

## Aturan Yang Tidak Boleh Dilanggar

- Sales tetap pemilik order/payment lifecycle.
- Dining tidak boleh mengubah nilai finansial order.
- Kitchen tidak boleh mengubah nilai finansial order.
- Table occupancy source of truth adalah `dining_table_sessions`.
- Satu table hanya boleh memiliki satu session `open` per outlet.
- Kitchen ticket dibuat idempotent per tenant, outlet, order item, dan station.
- Cancel order/item membuat kitchen event, bukan menghapus ticket lama.
- Realtime event tidak boleh membawa data rahasia.
- Client KDS yang reconnect wajib ambil snapshot API.
- Print failure tidak membatalkan order/payment.
- Reprint harus membuat print job baru dengan reason dan actor.

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
- memanggil action,
- return view atau redirect.

Controller tidak boleh menyimpan workflow bisnis.

## Urutan Kerja Yang Direkomendasikan

Kerjakan berurutan:

1. `P07-02` Dining Floor and Table Foundation
2. `P07-03` Table Session Lifecycle
3. `P07-04` Kitchen Station Routing
4. `P07-05` Kitchen Ticket Lifecycle
5. `P07-06` Realtime KDS Updates
6. `P07-07` Printer Dispatch and Reprint
7. `P07-08` Dining Kitchen Readiness

Jangan loncat ke ticket kitchen sebelum table session dan routing minimum siap.

## P07-02 - Dining Floor And Table Foundation

Referensi: `docs/execution/phase-07/P07-02-dining-floor-table-foundation.md`

### Outcome

Tenant admin dapat mengelola floor dan table per outlet.

### Scope

- Buat module `Dining`.
- Buat table database `dining_floors`.
- Buat table database `dining_tables`.
- Buat Web Admin baseline untuk CRUD floor/table.
- Terapkan tenant/outlet isolation.
- Terapkan status `active` dan `inactive`.

### Tidak Termasuk

- Jangan membuat Sales order.
- Jangan membuat table session.
- Jangan membuat kitchen ticket.

### File Yang Perlu Dibuat

```text
app/Modules/Dining/Domain/Enums/TableStatus.php
app/Modules/Dining/Domain/Models/DiningFloor.php
app/Modules/Dining/Domain/Models/DiningTable.php
app/Modules/Dining/Application/Actions/CreateDiningFloor.php
app/Modules/Dining/Application/Actions/UpdateDiningFloor.php
app/Modules/Dining/Application/Actions/ChangeDiningFloorStatus.php
app/Modules/Dining/Application/Actions/CreateDiningTable.php
app/Modules/Dining/Application/Actions/UpdateDiningTable.php
app/Modules/Dining/Application/Actions/ChangeDiningTableStatus.php
app/Modules/Dining/Application/Data/DiningFloorInput.php
app/Modules/Dining/Application/Data/DiningTableInput.php
app/Modules/Dining/Application/Exceptions/DiningException.php
app/Modules/Dining/Infrastructure/Persistence/Migrations/<timestamp>_create_dining_floor_table_foundation.php
app/Modules/Dining/Infrastructure/Providers/DiningServiceProvider.php
app/Modules/Dining/Presentation/Http/Routes/web.php
```

Daftarkan provider di:

```text
bootstrap/providers.php
```

### Migration Draft

`dining_floors`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
name string
display_order unsigned integer default 0
status string
created_at
updated_at
```

`dining_tables`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
dining_floor_id ULID
code string
name string nullable
seats unsigned small integer default 2
display_order unsigned integer default 0
status string
created_at
updated_at
```

Tambahkan unique/index yang membantu:

```text
unique tenant_id + outlet_id + code
index tenant_id + outlet_id
index dining_floor_id
```

### Langkah Coding

1. Buat enum `TableStatus` dengan value `active` dan `inactive`.
2. Buat migration floor/table.
3. Buat model `DiningFloor` dan `DiningTable`.
4. Buat input DTO untuk floor dan table.
5. Buat action create/update/change status.
6. Pastikan setiap action menerima `tenant_id` dan `outlet_id` eksplisit.
7. Pastikan action memvalidasi floor/table milik tenant dan outlet yang sama.
8. Buat provider untuk load migration dan route.
9. Buat route tenant admin untuk floor/table.
10. Buat controller sederhana yang memanggil action.
11. Buat Blade view sederhana: index, create, edit.

### Test Minimal

```text
tests/Feature/Dining/DiningFloorTableFoundationTest.php
```

Test yang wajib ada:

- Tenant owner bisa membuat floor.
- Tenant owner bisa membuat table di floor.
- Table code unik per outlet.
- Table di outlet A tidak bisa memakai floor outlet B.
- Tenant A tidak bisa membaca/mengubah data tenant B.
- Status table bisa diubah menjadi inactive.

### Done Jika

- CRUD floor/table jalan.
- Tenant/outlet isolation lulus test.
- Tidak ada table session/order/kitchen yang dibuat.
- `composer quality` lulus.

## P07-03 - Table Session Lifecycle

Referensi: `docs/execution/phase-07/P07-03-table-session-lifecycle.md`

### Outcome

Table occupancy dapat dibuka, dipindah, digabung, ditutup, dan dibatalkan tanpa merusak Sales order.

### Scope

- Buat `dining_table_sessions`.
- Buat `dining_table_session_orders`.
- Implement action open, transfer, merge, close, cancel.
- Link table session ke Sales order.

### Enum

```text
TableSessionStatus:
- open
- merged
- transferred
- closed
- cancelled
```

### Migration Draft

`dining_table_sessions`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
dining_table_id ULID
status string
opened_by_actor_id string
opened_at timestamp
closed_at timestamp nullable
transferred_from_table_id ULID nullable
merged_into_session_id ULID nullable
created_at
updated_at
```

`dining_table_session_orders`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
dining_table_session_id ULID
sales_order_id ULID
created_at
updated_at
```

Tambahkan guard database:

```text
index tenant_id + outlet_id + dining_table_id + status
unique dining_table_session_id + sales_order_id
```

Catatan: untuk rule "satu open session per table", implementasikan di action dengan transaction + lock. Jika database mendukung partial unique index secara aman, boleh ditambahkan, tetapi jangan bergantung pada fitur database yang tidak cocok dengan MariaDB.

### Action Yang Dibuat

```text
OpenTableSession
TransferTableSession
MergeTableSession
CloseTableSession
CancelTableSession
LinkOrderToTableSession
```

### Langkah Coding

1. Buat enum `TableSessionStatus`.
2. Buat migration session dan pivot order.
3. Buat model `DiningTableSession` dan `DiningTableSessionOrder`.
4. Di `OpenTableSession`, cek table aktif dan belum punya session `open`.
5. Gunakan database transaction saat membuka session.
6. Di `TransferTableSession`, cek target table aktif dan kosong.
7. Di `MergeTableSession`, target harus `open`, source harus `open`, source menjadi `merged`.
8. Di `CloseTableSession`, cek semua linked Sales order sudah selesai/cancelled/voided.
9. Di `CancelTableSession`, hanya session tanpa order aktif yang boleh cancelled.
10. Di `LinkOrderToTableSession`, pastikan order milik tenant/outlet yang sama.

### Error Code Yang Dipakai

```text
DINING_TABLE_NOT_FOUND
DINING_TABLE_OCCUPIED
DINING_TABLE_SESSION_NOT_FOUND
DINING_TABLE_SESSION_INVALID_STATE
```

### Test Minimal

```text
tests/Feature/Dining/TableSessionLifecycleTest.php
```

Test yang wajib ada:

- Bisa open session untuk table aktif.
- Tidak bisa open dua session untuk table yang sama.
- Bisa transfer session ke table kosong.
- Tidak bisa transfer ke table yang sudah occupied.
- Bisa merge source session ke target session.
- Source session berubah menjadi `merged`.
- Close ditolak jika masih ada linked order aktif.
- Close diterima jika linked order completed/cancelled/voided.
- Transfer/merge tidak mengubah status order/payment.

### Done Jika

- Occupancy table berasal dari session.
- Tidak ada financial mutation di module Dining.
- Semua lifecycle test lulus.
- Sales critical regression tetap lulus.

## P07-04 - Kitchen Station Routing

Referensi: `docs/execution/phase-07/P07-04-kitchen-station-routing.md`

### Outcome

Catalog item dapat dirutekan ke kitchen station yang benar secara server-side.

### Scope

- Buat module `Kitchen`.
- Buat `kitchen_stations`.
- Buat `kitchen_routing_rules`.
- Rule dapat mengacu category, product, atau variant.
- Fallback station outlet bersifat optional.

### File Yang Perlu Dibuat

```text
app/Modules/Kitchen/Domain/Models/KitchenStation.php
app/Modules/Kitchen/Domain/Models/KitchenRoutingRule.php
app/Modules/Kitchen/Application/Actions/CreateKitchenStation.php
app/Modules/Kitchen/Application/Actions/UpdateKitchenStation.php
app/Modules/Kitchen/Application/Actions/CreateKitchenRoutingRule.php
app/Modules/Kitchen/Application/Actions/ResolveKitchenStationForOrderItem.php
app/Modules/Kitchen/Application/Exceptions/KitchenException.php
app/Modules/Kitchen/Infrastructure/Persistence/Migrations/<timestamp>_create_kitchen_station_routing_tables.php
app/Modules/Kitchen/Infrastructure/Providers/KitchenServiceProvider.php
```

Daftarkan `KitchenServiceProvider` di `bootstrap/providers.php`.

### Migration Draft

`kitchen_stations`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
name string
code string
is_fallback boolean default false
status string
display_order unsigned integer default 0
created_at
updated_at
```

`kitchen_routing_rules`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
kitchen_station_id ULID
category_id ULID nullable
product_id ULID nullable
variant_id ULID nullable
priority unsigned integer default 100
created_at
updated_at
```

### Routing Priority

Gunakan urutan ini:

1. Variant rule
2. Product rule
3. Category rule
4. Fallback station outlet
5. Jika tetap tidak ada routing, item tetap orderable tetapi muncul di kitchen exception report

### Langkah Coding

1. Buat module `Kitchen`.
2. Buat model station dan routing rule.
3. Buat action CRUD station minimum.
4. Buat action create/update routing rule.
5. Buat action resolver station.
6. Resolver menerima tenant, outlet, dan snapshot/order item data.
7. Resolver tidak boleh membaca data tenant/outlet lain.
8. Buat report/query untuk missing routing.

### Error Code Yang Dipakai

```text
KITCHEN_STATION_NOT_FOUND
KITCHEN_ROUTING_MISSING
```

### Test Minimal

```text
tests/Feature/Kitchen/KitchenStationRoutingTest.php
```

Test yang wajib ada:

- Variant rule menang dari product/category.
- Product rule menang dari category.
- Category rule dipakai jika tidak ada product/variant rule.
- Fallback station dipakai jika tidak ada rule.
- Missing routing masuk exception report.
- Station outlet A tidak bisa dipakai outlet B.
- Tenant A tidak bisa melihat routing tenant B.

### Done Jika

- Routing server-side jalan.
- Routing tenant/outlet-scoped.
- Missing routing tidak memblokir Sales order.
- `composer quality` lulus.

## P07-05 - Kitchen Ticket Lifecycle

Referensi: `docs/execution/phase-07/P07-05-kitchen-ticket-lifecycle.md`

### Outcome

Confirmed Sales order item menghasilkan kitchen ticket tepat satu kali dan dapat diproses di KDS.

### Scope

- Buat kitchen ticket header.
- Buat kitchen ticket item.
- Buat append-only kitchen ticket event.
- Generate ticket dari confirmed order item.
- Implement state transition `queued`, `preparing`, `ready`, `served`, `cancelled`.

### Enum

```text
KitchenTicketStatus:
- queued
- preparing
- ready
- served
- cancelled
```

### Migration Draft

`kitchen_tickets`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
kitchen_station_id ULID
sales_order_id ULID
status string
ticket_number string
queued_at timestamp
started_at timestamp nullable
ready_at timestamp nullable
served_at timestamp nullable
cancelled_at timestamp nullable
created_at
updated_at
```

`kitchen_ticket_items`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
kitchen_ticket_id ULID
kitchen_station_id ULID
sales_order_id ULID
sales_order_item_id ULID
item_name_snapshot string
quantity decimal/string sesuai pola repo
status string
notes text nullable
created_at
updated_at
```

`kitchen_ticket_events`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
kitchen_ticket_id ULID
kitchen_ticket_item_id ULID nullable
event_type string
from_status string nullable
to_status string nullable
actor_type string
actor_id string
reason text nullable
occurred_at timestamp
metadata json nullable
created_at
updated_at
```

Tambahkan idempotency unique:

```text
unique tenant_id + outlet_id + sales_order_item_id + kitchen_station_id
```

### Action Yang Dibuat

```text
CreateKitchenTicketForConfirmedOrderItem
AdvanceKitchenTicketStatus
CancelKitchenTicketItem
RecordKitchenTicketEvent
```

### Langkah Coding

1. Buat enum status ticket.
2. Buat migration ticket, item, event.
3. Buat model ticket, item, event.
4. Buat action create ticket dari confirmed Sales order item.
5. Panggil resolver routing dari P07-04.
6. Pastikan create ticket idempotent.
7. Saat ticket dibuat, catat event `ticket_created`.
8. Buat action advance status.
9. Validasi transisi status agar tidak loncat sembarangan.
10. Saat order/item cancelled, buat event cancellation dan ubah status item/ticket sesuai kebutuhan.

### State Transition Minimum

```text
queued -> preparing
preparing -> ready
ready -> served
queued/preparing/ready -> cancelled
```

Jangan izinkan:

```text
served -> preparing
cancelled -> ready
served -> cancelled
```

### Error Code Yang Dipakai

```text
KITCHEN_TICKET_NOT_FOUND
KITCHEN_TICKET_INVALID_STATE
KITCHEN_ROUTING_MISSING
```

### Test Minimal

```text
tests/Feature/Kitchen/KitchenTicketLifecycleTest.php
```

Test yang wajib ada:

- Confirmed order item membuat ticket.
- Retry create ticket tidak menggandakan ticket item.
- Ticket masuk station sesuai routing.
- Status bisa berubah queued -> preparing -> ready -> served.
- Invalid transition ditolak.
- Cancel item membuat event cancellation.
- Cancel order tidak menghapus ticket lama.
- Kitchen tidak mengubah order/payment financial state.

### Done Jika

- Ticket lifecycle berjalan.
- Idempotency aman.
- Event history append-only.
- Sales cancellation regression lulus.

## P07-06 - Realtime KDS Updates

Referensi: `docs/execution/phase-07/P07-06-realtime-kds-updates.md`

### Outcome

KDS mendapat update realtime yang aman per tenant/outlet dan dapat recover lewat snapshot API.

### Scope

- Broadcast event untuk ticket created/updated.
- Authorization channel tenant/outlet.
- Snapshot endpoint untuk latest KDS state.

### Channel Naming Draft

Gunakan channel yang menyertakan tenant dan outlet:

```text
tenant.{tenantId}.outlet.{outletId}.kitchen
```

Payload event hanya data yang dibutuhkan UI:

```text
ticket_id
station_id
status
changed_at
event_type
```

Jangan kirim:

```text
session token
password
payment secret
internal credential
data outlet lain
```

### Endpoint Snapshot Draft

```text
GET /admin/tenants/{tenant}/outlets/{outlet}/kitchen/snapshot
```

Snapshot mengembalikan ticket aktif berdasarkan database, bukan berdasarkan event terakhir.

### Langkah Coding

1. Buat event `KitchenTicketChanged`.
2. Broadcast setelah ticket created/updated.
3. Tambahkan authorization channel.
4. Buat endpoint snapshot.
5. Pastikan snapshot filter tenant/outlet.
6. Buat test payload redaction.

### Test Minimal

```text
tests/Feature/Kitchen/RealtimeKdsUpdateTest.php
```

Test yang wajib ada:

- User outlet A bisa subscribe channel outlet A.
- User outlet A tidak bisa subscribe channel outlet B.
- Event payload tidak berisi secret.
- Snapshot mengembalikan latest ticket state.
- Jika event hilang, snapshot tetap benar.

### Done Jika

- Realtime tidak menjadi source of truth.
- Channel tenant/outlet-scoped.
- Snapshot endpoint tersedia.
- `composer quality` lulus.

## P07-07 - Printer Dispatch And Reprint

Referensi: `docs/execution/phase-07/P07-07-printer-dispatch-reprint.md`

### Outcome

Kitchen chit/receipt print job dapat dikirim, gagal, retry, dan reprint secara auditable.

### Scope

- Buat `kitchen_print_jobs`.
- Buat printer dispatch abstraction.
- Implement retry.
- Implement reprint dengan reason.

### Enum

```text
PrintJobStatus:
- queued
- sent
- failed
- cancelled
```

### Migration Draft

`kitchen_print_jobs`:

```text
id ULID primary
tenant_id ULID
outlet_id ULID
kitchen_ticket_id ULID
status string
payload json
failure_reason text nullable
reprint_reason text nullable
requested_by_actor_type string
requested_by_actor_id string
sent_at timestamp nullable
failed_at timestamp nullable
created_at
updated_at
```

### Contract Draft

```text
KitchenPrinterDispatcher
- dispatch(printJob): PrintDispatchResult
```

Infrastructure adapter awal boleh sederhana, misalnya `LogKitchenPrinterDispatcher`, agar workflow bisa dites tanpa hardware.

### Action Yang Dibuat

```text
QueueKitchenPrintJob
DispatchKitchenPrintJob
RetryKitchenPrintJob
ReprintKitchenTicket
```

### Rules

- Print job append-only untuk reprint.
- Reprint membuat job baru.
- Retry boleh mencoba job failed yang sama.
- Print failure tidak mengubah Sales order/payment.
- Semua failure harus punya alasan.

### Test Minimal

```text
tests/Feature/Kitchen/PrinterDispatchTest.php
```

Test yang wajib ada:

- Dispatch success mengubah status menjadi `sent`.
- Dispatch failure mengubah status menjadi `failed`.
- Failure reason tersimpan.
- Retry failed job bisa menjadi `sent`.
- Reprint membuat job baru.
- Print failure tidak mengubah order/payment.

### Done Jika

- Print job auditable.
- Reprint tidak overwrite job lama.
- Failure aman untuk Sales.
- `composer quality` lulus.

## P07-08 - Dining Kitchen Readiness

Referensi: `docs/execution/phase-07/P07-08-dining-kitchen-readiness.md`

### Outcome

Phase 07 siap dinyatakan selesai dengan evidence test, demo flow, dan update dokumen.

### Checklist Readiness

- Dining suite lulus.
- Kitchen suite lulus.
- Sales critical path lulus.
- Catalog routing regression lulus.
- `composer quality` lulus.
- `npm run build` lulus jika frontend berubah.
- Runbook dining/kitchen dibuat.
- Status dokumen execution diupdate dari `Planned` ke `Done` setelah evidence lengkap.

### Demo Flow Manual

Gunakan flow berikut untuk validasi akhir:

1. Login sebagai tenant owner.
2. Buat outlet jika belum ada.
3. Buat floor.
4. Buat table aktif.
5. Open table session.
6. Buat Sales order untuk table session.
7. Tambahkan item yang punya routing kitchen.
8. Confirm order item.
9. Pastikan kitchen ticket terbentuk satu kali.
10. Ubah ticket: queued -> preparing -> ready -> served.
11. Coba reprint kitchen ticket.
12. Simulasikan print failed.
13. Pastikan order/payment tidak berubah karena print failed.
14. Close table session setelah order selesai/cancelled/voided.

### Evidence Yang Dicatat

Buat ringkasan readiness, misalnya:

```text
docs/runbooks/phase-07-dining-kitchen-readiness.md
```

Isi minimal:

- tanggal test,
- command yang dijalankan,
- hasil test,
- demo flow yang berhasil,
- known limitation,
- deferred scope.

## Command Harian

Start test database:

```shell
docker compose up -d mariadb-testing
```

Jalankan test spesifik:

```shell
php artisan test --filter=Dining
php artisan test --filter=Kitchen
```

Jalankan quality gate penuh:

```shell
composer quality
```

Jika mengubah CSS/JS/Blade frontend:

```shell
npm run build
```

## Tips Untuk Coding Manual

- Kerjakan satu work package sampai test hijau sebelum pindah ke work package berikutnya.
- Buat migration lebih dulu, lalu model, lalu action, lalu controller/view, lalu test.
- Jangan copy business logic ke controller.
- Selalu bawa `tenant_id` dan `outlet_id` sebagai input eksplisit.
- Untuk mutasi penting, gunakan database transaction.
- Untuk rule idempotency, pikirkan retry request dan duplicate event sejak awal.
- Jika bingung dependency lintas module, cari dulu action/contract yang sudah ada. Jangan langsung akses internal model module lain tanpa alasan jelas.
- Jika butuh behavior baru yang tidak tertulis di dokumen terkunci, tulis sebagai open question dulu.

## Definition Of Done Phase 07

Phase 07 selesai jika:

- Semua work package P07-02 sampai P07-07 selesai.
- P07-08 readiness evidence lengkap.
- Tidak ada regression pada Sales/Catalog critical path.
- Tenant/outlet isolation terbukti lewat test.
- Tidak ada scope deferred yang ikut terimplementasi diam-diam.
- `composer quality` lulus.
