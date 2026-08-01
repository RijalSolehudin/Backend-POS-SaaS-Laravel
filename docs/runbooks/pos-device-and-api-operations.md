# POS Device and API Operations

Dokumen ini berlaku untuk operator yang mengelola device POS terdaftar dan token Flutter API.

## Device Registration

1. Login Tenant Admin sebagai Tenant Owner, atau Outlet Manager yang ditugaskan pada outlet target.
2. Buka `Admin > Devices`.
3. Daftarkan `installation_id` dari aplikasi Flutter, nama device, platform, app version, dan outlet.
4. Pastikan audit `pos_device.registered` tercatat pada `tenancy_audit_events`.

Installation ID bukan secret. Jangan gunakan IMEI, MAC address, atau hardware fingerprint sebagai prasyarat.

## Device Reassignment

1. Login sebagai Tenant Owner.
2. Buka `Admin > Devices`.
3. Pilih outlet baru dalam tenant yang sama.
4. Isi reason operasional yang dapat diaudit.
5. Setelah reassignment, seluruh token terkait device dicabut dan POS harus login ulang.

## Device Revocation

1. Login sebagai Tenant Owner.
2. Buka `Admin > Devices`.
3. Isi reason revocation.
4. Jalankan revoke.
5. Pastikan token lama menerima error API dengan stable code `DEVICE_REVOKED` atau tidak lagi terautentikasi.

Device record tidak dihapus agar audit lifecycle tetap tersedia.

## Flutter API Token

- Login POS memakai `POST /api/v1/pos/auth/login`.
- Token berlaku maksimal 30 hari.
- Login ulang pada user-device yang sama mengganti token lama.
- Logout memakai `POST /api/v1/pos/auth/logout` dan hanya mencabut current token.
- Scheduler menjalankan `sanctum:prune-expired --hours=24` harian untuk membersihkan expired token yang sudah melewati retention window.

## POS Core Demo Path

Gunakan data tenant/outlet/device/catalog yang sudah aktif.

1. Login POS dengan `POST /api/v1/pos/auth/login`.
2. Ambil catalog outlet dengan `GET /api/v1/pos/outlets/{outlet}/catalog`.
3. Buka shift dengan `POST /api/v1/pos/outlets/{outlet}/shifts/open`.
4. Buat draft order dengan `POST /api/v1/pos/outlets/{outlet}/orders` dan header `Idempotency-Key`.
5. Tambah item dengan `POST /api/v1/pos/outlets/{outlet}/orders/{order}/items`.
6. Complete order dengan exact payment melalui `POST /api/v1/pos/outlets/{outlet}/orders/{order}/complete` dan header `Idempotency-Key`.
7. Ambil receipt snapshot dengan `GET /api/v1/pos/outlets/{outlet}/orders/{order}/receipt`.
8. Tutup shift dengan `POST /api/v1/pos/outlets/{outlet}/shifts/{shift}/close`.
9. Cocokkan shift summary melalui `GET /api/v1/pos/outlets/{outlet}/shifts/{shift}/summary`.
10. Cocokkan daily sales pada `GET /admin/tenants/{tenant}/sales/daily?date=YYYY-MM-DD`.

Operational checks:

- Retry create order dan complete payment dengan idempotency key yang sama tidak boleh membuat data ganda.
- Order total, payment amount, receipt total, shift summary, dan daily sales harus konsisten.
- Setelah shift closed, order baru harus ditolak dengan `ORDER_ACTIVE_SHIFT_REQUIRED`.
- Cross-tenant/outlet/device access harus ditolak dengan stable Problem Details response.

## Sensitive Action Approval

Void completed order sekarang membutuhkan supervisor approval yang valid.

- Cashier membuat approval request melalui application workflow untuk action `orders.void`.
- Tenant Owner atau Outlet Manager pada outlet terkait menyetujui approval tersebut.
- Cashier menjalankan `POST /api/v1/pos/outlets/{outlet}/orders/{order}/void` dengan header `Idempotency-Key`, `reason`, dan `approval_id`.
- Approval hanya valid untuk performer, action, order, outlet, tenant, reason fingerprint, dan target yang sama.
- Approval yang sudah dipakai berubah menjadi `consumed` dan tidak bisa dipakai untuk mutation baru.
- Setiap lifecycle approval dicatat pada `sales_audit_events`.

## Full Manual Refund

Refund Phase 03 adalah full manual refund, bukan gateway settlement.

- Cashier meminta approval untuk action `payments.refund` dengan target `sales_order`.
- Supervisor menyetujui approval sesuai policy P03-02.
- Cashier menjalankan `POST /api/v1/pos/outlets/{outlet}/orders/{order}/refund` dengan header `Idempotency-Key`, `amount_minor`, `currency`, `reason`, dan `approval_id`.
- Refund amount harus sama dengan recorded payment amount tersisa.
- Original order, payment, dan receipt snapshot tetap immutable.
- Shift summary dan daily sales menampilkan gross sales, refunds, dan net sales.

## Cash Movement and Shift Discrepancy

Cash movement dipakai untuk menjelaskan perubahan uang tunai di drawer selain order/refund.

- Cashier mencatat cash in/out melalui `POST /api/v1/pos/outlets/{outlet}/shifts/{shift}/cash-movements`.
- Payload minimum: `type`, `amount_minor`, `currency`, `reason`, dan optional `approval_id`.
- `cash_in` tidak membutuhkan supervisor approval.
- `cash_out` dengan amount di atas threshold membutuhkan approval action `cash_movements.cash_out`.
- Shift summary menghitung expected cash dari opening cash, cash payments, cash refunds, cash in, dan cash out.
- Close shift menyimpan expected cash dan counted cash. Jika variance tidak nol, audit event `shift.discrepancy.recorded` dibuat.

## Troubleshooting

- `DEVICE_NOT_REGISTERED`: installation ID belum terdaftar pada tenant user.
- `DEVICE_REVOKED`: device pernah direvoke; daftarkan device baru atau investigasi reason.
- `OUTLET_NOT_FOUND`: route outlet tidak cocok dengan binding device atau outlet tidak aktif.
- `TENANCY_FORBIDDEN`: user tidak punya role/assignment yang cukup.
- `ORDER_ACTIVE_SHIFT_REQUIRED`: cashier belum membuka shift aktif atau shift sudah closed.
- `IDEMPOTENCY_CONFLICT`: idempotency key pernah dipakai untuk request fingerprint berbeda.
- `APPROVAL_REQUIRED`: action sensitif membutuhkan approval valid.
- `APPROVAL_NOT_FOUND`: approval tidak ditemukan pada tenant/outlet target.
- `APPROVAL_FORBIDDEN`: approver tidak memenuhi role atau outlet scope.
- `APPROVAL_TARGET_MISMATCH`: approval tidak cocok dengan performer/action/target/fingerprint request.
- `APPROVAL_ALREADY_CONSUMED`: approval pernah dipakai untuk mutation sebelumnya.
- `REFUND_ORDER_NOT_REFUNDABLE`: order/payment tidak memenuhi syarat full refund.
- `REFUND_AMOUNT_MISMATCH`: amount refund tidak sama dengan refundable amount tersisa.
- `REFUND_CURRENCY_MISMATCH`: currency refund tidak sama dengan payment original.
- `REFUND_ALREADY_RECORDED`: payment sudah memiliki refund record.
- `CASH_MOVEMENT_SHIFT_NOT_OPEN`: cash movement dicoba pada shift yang bukan `open`.
- `CASH_MOVEMENT_REASON_REQUIRED`: reason cash movement kosong.
- `SHIFT_CURRENCY_MISMATCH`: currency cash movement tidak sama dengan currency shift.

Gunakan `X-Request-ID` atau `trace_id` dari error body untuk korelasi log.

Audit event Sales, retention, dan redaction didokumentasikan di [Sales Audit Events](sales-audit-events.md).

Retry aman, recovery check, dan cara membaca ambiguous financial state didokumentasikan di [Sales Retry and Recovery](sales-retry-and-recovery.md).

Checklist final sebelum outlet pilot memakai transaksi riil tersedia di [Phase 03 Pilot Readiness](phase-03-pilot-readiness.md).
