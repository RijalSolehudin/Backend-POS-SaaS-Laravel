# Manual Test Cases

Status: **Living Document**

Dokumen ini adalah checklist manual untuk mencoba seluruh capability sistem POS SaaS F&B yang sudah dibangun sampai Phase 09. Tujuannya bukan menggantikan automated test, tetapi membantu product owner, QA, dan developer memahami alur, ketentuan, desain sistem, dan expected behavior secara menyeluruh.

## Cara Pakai

- Jalankan test secara berurutan dari setup, platform, tenant, catalog, POS, lalu modul operasional lanjutan.
- Catat hasil sebagai `Pass`, `Fail`, `Blocked`, atau `Not Applicable`.
- Untuk setiap `Fail`, simpan URL, request id, user/role, tenant, outlet, payload, screenshot, dan waktu kejadian.
- Untuk API POS, gunakan token Sanctum dari login POS dan pastikan request memiliki outlet id yang sesuai dengan device binding.
- Untuk fitur finansial, pastikan hasil dapat direkonsiliasi dari order, payment, receipt, shift, audit, dan reporting.

## Status Runtime

- Feature test berbasis MariaDB membutuhkan service `mariadb-testing` pada `127.0.0.1:33067`.
- Jika Docker daemon atau MariaDB belum aktif, test manual yang membutuhkan database ditandai `Blocked`.
- Phase 01 sampai Phase 09 sudah lulus automated verification di MariaDB testing.
- Production readiness tetap membutuhkan manual QA end-to-end, staging smoke test, dan deployment/integration evidence.

## Global Design Rules

- Backend memakai modular monolith. Setiap modul punya ownership domain sendiri.
- Tenant isolation wajib berlaku pada semua data tenant, outlet, user, device, transaksi, inventory, kitchen, reporting, dan sync.
- ID utama memakai lowercase ULID.
- Uang disimpan sebagai integer minor units, misalnya IDR 50.000 menjadi `50000`.
- Mutation penting memakai idempotency key agar retry tidak menggandakan transaksi.
- Order, payment, receipt, stock movement, audit, conflict, dan sync history tidak boleh di-hard-delete.
- Server adalah source of truth untuk order number, receipt, payment status final, inventory deduction, dan audit.
- API error harus berbentuk problem details dan membawa business error code bila berasal dari business rule.
- POS API memakai `/api/v1/...` dan guard `auth:sanctum`.
- Web admin tenant memakai `/admin/tenants/{tenant}/...`.
- Platform admin memakai `/platform/...`.

## Execution Surfaces

Tidak semua capability memiliki layar atau endpoint publik pada tahap ini. Gunakan surface berikut saat menjalankan manual test:

| Surface | Dipakai Untuk | Catatan |
|---|---|---|
| Platform Web | Platform Identity, tenant provisioning | Route `/platform/...`. |
| Tenant Web Admin | Tenant admin, outlet, users/roles, device, catalog, inventory, recipe, procurement foundation, dining, sales daily | Route `/admin/tenants/{tenant}/...`. |
| POS REST API | POS auth/context, catalog, shift, order, payment manual/cash, refund/void, KDS snapshot, Sync | Route `/api/v1/...`, gunakan bearer token POS. |
| Public REST API | QR public catalog | Route `/api/v1/qr/{token}`. |
| Console Command | Bootstrap, recovery, import/export, baseline | Jalankan dari terminal pada environment target. |
| Action Harness/Tinker | Capability yang sudah ada di application layer tetapi belum punya UI/API publik | Gunakan hanya di local/staging, atau bungkus sementara dalam feature test/manual script. |

Jika sebuah test case tidak menyebut URL/route spesifik, jalankan melalui Web Admin bila tersedia. Jika belum ada layar/endpoint, jalankan via action harness/Tinker dan catat sebagai `Application-layer manual verification`.

## Test Data Minimum

Siapkan data berikut sebelum menjalankan semua test:

- 1 Platform Administrator dengan MFA aktif.
- 1 Tenant aktif, misalnya `Acme POS`.
- 2 Outlet aktif, misalnya `MAIN` dan `SECOND`.
- 1 Tenant Owner.
- 1 Outlet Manager.
- 1 Cashier.
- 1 POS device aktif untuk outlet `MAIN`.
- 1 POS device revoked untuk negative test.
- Minimal 1 category aktif.
- Minimal 2 product aktif, salah satunya punya variant dan modifier.
- Minimal 1 inventory unit, inventory item, dan opening balance.
- Minimal 1 supplier dan supplier item.
- Minimal 1 dining floor dan 2 meja.
- Minimal 1 kitchen station default.

## Environment Smoke Test

| ID | Area | Langkah | Expected Result |
|---|---|---|---|
| ENV-01 | App health | Buka `/up`. | Response healthy. |
| ENV-02 | Home redirect | Buka `/`. | Aplikasi merespons tanpa error 500. |
| ENV-03 | Route registration | Jalankan `php artisan route:list`. | Route platform, admin, POS API, QR, KDS, Sync muncul. |
| ENV-04 | Console commands | Jalankan `php artisan list`. | Command tenancy, platform, catalog, inventory, sales, sync terdaftar. |
| ENV-05 | Static quality | Jalankan `composer quality:static`. | Composer validate, Pint, PHPStan, dan deptrac lulus. |
| ENV-06 | Unit suite | Jalankan `php artisan test --testsuite=Unit`. | Semua unit test lulus. |
| ENV-07 | Feature suite | Jalankan `php artisan test --testsuite=Feature`. | Semua feature test lulus bila MariaDB testing aktif. |

## Platform Identity

### Desain

Platform Identity mengelola akun platform administrator secara terpisah dari tenant user. Platform admin tidak otomatis punya akses tenant operational. Area platform memakai MFA, session policy, recovery code, recent confirmation untuk aksi sensitif, dan security event.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| PLT-01 | Bootstrap admin pertama | Jalankan command bootstrap platform admin sesuai runbook. | Platform admin dibuat sekali, credential awal tersedia sesuai prosedur aman. |
| PLT-02 | Duplicate bootstrap | Jalankan bootstrap lagi dengan email sama. | Tidak membuat admin duplikat; muncul pesan controlled failure. |
| PLT-03 | Login password valid | Buka `/platform/login`, masukkan credential valid. | Masuk ke MFA challenge/enroll sesuai status akun. |
| PLT-04 | Login password invalid | Masukkan password salah. | Login ditolak, tidak masuk session platform. |
| PLT-05 | MFA enrollment | Login admin baru lalu enroll TOTP. | QR/secret enrollment tampil, kode TOTP valid mengaktifkan MFA. |
| PLT-06 | MFA challenge valid | Masukkan kode TOTP valid. | Masuk ke `/platform`. |
| PLT-07 | MFA challenge invalid | Masukkan kode TOTP salah. | Ditolak dan tetap di challenge. |
| PLT-08 | Recovery code | Login memakai recovery code yang belum dipakai. | Akses diterima dan recovery code tidak dapat dipakai ulang. |
| PLT-09 | Regenerate recovery code | Dari `/platform/security`, regenerate recovery codes. | Kode lama invalid, kode baru tersedia. |
| PLT-10 | Session limit | Login dari lebih dari batas session aktif. | Flow session replacement muncul sesuai policy. |
| PLT-11 | Revoke session | Revoke session lain dari `/platform/security`. | Session target invalid, session saat ini tetap aktif. |
| PLT-12 | Idle timeout | Diam lebih dari idle timeout. | Session diminta login ulang. |
| PLT-13 | Absolute timeout | Simulasikan melewati absolute session age. | Session expired walaupun aktif. |
| PLT-14 | Recent confirmation | Buka `/platform/confirm-sensitive-action`. | Recent confirmation valid untuk aksi sensitif. |
| PLT-15 | Logout | Klik logout. | Session platform invalid dan redirect login. |
| PLT-16 | Disabled admin | Disable admin lalu coba login. | Login ditolak. |
| PLT-17 | Emergency recovery | Jalankan command recovery platform access. | Credential/reset flow terkendali dan security event tercatat. |

## Tenancy

### Desain

Tenancy mengelola tenant, outlet, membership, role assignment tenant, device registry, dan context resolution. Semua operasi tenant/outlet harus divalidasi terhadap tenant aktif, outlet aktif, membership, role, dan device binding.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| TEN-01 | Provision tenant via platform | Login platform, buka `/platform/tenants/create`, buat tenant + owner + outlet. | Tenant, outlet, owner membership, dan owner role dibuat atomik. |
| TEN-02 | Provision duplicate tenant code | Buat tenant dengan code yang sudah ada. | Ditolak tanpa data setengah jadi. |
| TEN-03 | View tenant detail | Buka `/platform/tenants/{tenant}`. | Detail tenant dan status tampil. |
| TEN-04 | Disable tenant | Disable tenant dari platform. | Tenant tidak dapat dipakai login/operasi tenant baru. |
| TEN-05 | Tenant admin login | Buka `/admin/login`, login owner tenant. | Masuk ke `/admin/tenants/{tenant}`. |
| TEN-06 | Tenant logout | Logout dari tenant admin. | Session tenant invalid. |
| TEN-07 | Force password change | Login user yang wajib ganti password. | Dialihkan ke halaman change password. |
| TEN-08 | Change password valid | Ubah password sesuai policy. | Password berubah dan user bisa lanjut. |
| TEN-09 | Create outlet | Buka `/admin/tenants/{tenant}/outlets/create`, buat outlet baru. | Outlet aktif dibuat dalam tenant yang sama. |
| TEN-10 | Update outlet | Edit nama/code outlet. | Perubahan tersimpan dan tetap tenant scoped. |
| TEN-11 | Disable outlet | Disable outlet. | Outlet tidak bisa dipakai POS context baru. |
| TEN-12 | Assign outlet user | Tambahkan user ke outlet. | User muncul pada outlet assignment. |
| TEN-13 | Remove outlet user | Hapus assignment user. | User tidak bisa mengakses outlet tersebut. |
| TEN-14 | Assign role | Tambah role Tenant Owner/Outlet Manager/Cashier. | Role tersimpan sesuai predefined role policy. |
| TEN-15 | Remove role | Hapus role user. | Permission terkait hilang. |
| TEN-16 | Cross-tenant access | User tenant A buka `/admin/tenants/{tenantB}`. | Access denied atau not found. |
| TEN-17 | Register POS device | Buka `/admin/tenants/{tenant}/devices`, register device outlet MAIN. | Device aktif, terikat tenant/outlet. |
| TEN-18 | Reassign POS device | Reassign device ke outlet lain. | Binding berubah dan token/context outlet lama tidak valid. |
| TEN-19 | Revoke POS device | Revoke device aktif. | Device revoked, token POS tidak boleh dipakai sync/operasi baru. |

## Identity

### Desain

Identity mengelola tenant user credential, login tenant admin, password reset, role assignment, dan POS token issuance via Tenancy flow. Role MVP bersifat predefined.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| IDN-01 | Tenant login valid | Login `/admin/login` sebagai tenant user aktif. | Masuk tenant admin. |
| IDN-02 | Tenant login invalid | Gunakan password salah. | Login ditolak. |
| IDN-03 | Disabled tenant user | Disable user atau cabut akses lalu login. | Login/operasi ditolak. |
| IDN-04 | Password reset request | Buka `/admin/forgot-password`, submit email valid. | Reset link dikirim melalui mailer environment. |
| IDN-05 | Password reset token valid | Buka link reset dan set password baru. | Password berubah dan token tidak bisa dipakai ulang. |
| IDN-06 | Password reset token invalid | Pakai token salah/expired. | Reset ditolak. |
| IDN-07 | Role visibility | Login sebagai Cashier. | Tidak bisa mengakses fitur admin yang membutuhkan owner/manager. |
| IDN-08 | Owner permission | Login sebagai Tenant Owner. | Bisa mengelola tenant user, outlet, catalog, device. |

## POS Authentication and Context API

### Desain

Flutter/POS mendapatkan token melalui `/api/v1/pos/auth/login`. Token dikaitkan dengan `pos_device_id`. Semua route outlet scoped harus memakai outlet yang cocok dengan binding device dan membership user.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| POS-AUTH-01 | Login POS valid | POST `/api/v1/pos/auth/login` dengan credential user, device installation, dan outlet valid. | Token Sanctum terbit dan punya `pos_device_id`. |
| POS-AUTH-02 | Login POS wrong outlet | Login memakai device outlet MAIN untuk outlet SECOND. | Ditolak. |
| POS-AUTH-03 | Login POS revoked device | Login memakai device revoked. | Ditolak. |
| POS-AUTH-04 | Get context | GET `/api/v1/pos/outlets/{outlet}/context` pakai token valid. | Tenant/outlet/device/user context benar. |
| POS-AUTH-05 | Context cross outlet | GET context outlet berbeda dari binding device. | Ditolak. |
| POS-AUTH-06 | Logout POS | POST `/api/v1/pos/auth/logout`. | Token current access revoked. |
| POS-AUTH-07 | Reuse revoked token | Pakai token lama setelah logout. | Unauthorized. |

## Catalog

### Desain

Catalog memiliki category, product, variant, modifier, outlet availability, dan price override. Sales order item menyimpan snapshot agar perubahan catalog tidak mengubah order lama.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| CAT-01 | Create category | POST category dari `/admin/tenants/{tenant}/catalog`. | Category aktif muncul sesuai display order. |
| CAT-02 | Update category | Ubah nama/display order category. | Perubahan tampil di admin dan POS catalog. |
| CAT-03 | Disable category | Nonaktifkan category. | Product dalam category tidak muncul di POS catalog. |
| CAT-04 | Create product | Buat product SKU unik, harga IDR, category aktif. | Product tersimpan aktif. |
| CAT-05 | Duplicate SKU | Buat product SKU sama dalam tenant. | Ditolak. |
| CAT-06 | Update product | Ubah nama/harga product. | POS catalog memakai data baru untuk order baru. |
| CAT-07 | Disable product | Nonaktifkan product. | Tidak muncul di POS catalog. |
| CAT-08 | Outlet availability on | Set product available di outlet MAIN. | Product muncul di `/api/v1/pos/outlets/{MAIN}/catalog`. |
| CAT-09 | Outlet availability off | Set unavailable. | Product hilang dari catalog outlet tersebut. |
| CAT-10 | Outlet price override | Set price override untuk outlet MAIN. | POS catalog MAIN memakai price override; outlet lain tetap base price. |
| CAT-11 | Create variant | Tambah variant product. | Variant muncul di POS catalog dan bisa dipilih saat add item. |
| CAT-12 | Disable variant | Nonaktifkan variant. | Variant tidak bisa dipilih untuk order baru. |
| CAT-13 | Modifier required | Buat modifier group required dengan min/max. | Add item tanpa modifier required ditolak. |
| CAT-14 | Modifier optional | Buat modifier optional. | Add item dengan/tanpa modifier valid. |
| CAT-15 | Modifier price delta | Pilih modifier berharga. | Line subtotal bertambah sesuai price delta. |
| CAT-16 | Catalog API isolation | Token tenant A request catalog outlet tenant B. | Unauthorized/not found. |
| CAT-17 | Export catalog | Jalankan command export catalog. | File/output export berisi data tenant scoped. |
| CAT-18 | Import dry run | Jalankan command import dry run dengan sample valid dan invalid. | Valid dihitung, invalid dilaporkan tanpa write. |

## Sales and POS Core

### Desain

Sales mengelola shift, order, item, payment, receipt, cancel, void, refund, cash movement, audit, dan recovery. Draft order hanya bisa dibuat saat shift open. Completion payment harus exact amount dan idempotent.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| SAL-01 | Current shift empty | GET `/api/v1/pos/outlets/{outlet}/shifts/current` sebelum open. | Response menunjukkan tidak ada open shift. |
| SAL-02 | Open shift | POST `/shifts/open` dengan opening cash. | Shift `open` dibuat untuk user/outlet. |
| SAL-03 | Duplicate open shift | Open shift lagi untuk user/outlet sama. | Ditolak atau mengembalikan open shift sesuai policy. |
| SAL-04 | Create draft order | POST `/orders` dengan `Idempotency-Key`. | Draft order dibuat, order number server-side. |
| SAL-05 | Retry create draft | Ulang request dengan idempotency key sama. | Order sama dikembalikan, tidak duplikat. |
| SAL-06 | Idempotency conflict | Pakai idempotency key sama untuk payload berbeda bila tersedia. | Ditolak sebagai conflict. |
| SAL-07 | Add simple item | POST `/orders/{order}/items`. | Item tersimpan, subtotal/total naik. |
| SAL-08 | Add variant item | Add item dengan `variant_id`. | Snapshot variant SKU/nama/harga tersimpan. |
| SAL-09 | Add modifier item | Add item dengan modifiers valid. | Modifier snapshot dan price delta tersimpan. |
| SAL-10 | Invalid modifier | Kirim modifier di luar group/min/max. | Ditolak. |
| SAL-11 | Update item quantity | PUT item quantity. | Line subtotal dan order total recalculated. |
| SAL-12 | Remove item | DELETE item. | Item hilang, total turun. |
| SAL-13 | Complete cash exact | POST `/orders/{order}/complete` method `cash`, amount sama total. | Order `completed`, payment recorded, receipt dibuat. |
| SAL-14 | Complete manual non-cash | Complete method `manual_non_cash`. | Payment recorded non-cash, expected cash tidak bertambah. |
| SAL-15 | Complete wrong amount | Complete dengan amount tidak sama total. | Ditolak, order tetap draft. |
| SAL-16 | Complete wrong currency | Complete dengan currency berbeda. | Ditolak. |
| SAL-17 | Retry completion | Ulang completion dengan idempotency key sama. | Tidak membuat payment/receipt ganda. |
| SAL-18 | Modify completed order | Coba add/update/remove item setelah completed. | Ditolak. |
| SAL-19 | Receipt lookup | GET `/orders/{order}/receipt`. | Receipt snapshot immutable tampil. |
| SAL-20 | Catalog mutation after receipt | Ubah harga/nama product lalu lihat receipt lama. | Receipt/order item lama tidak berubah. |
| SAL-21 | Cancel draft | POST `/orders/{order}/cancel` dengan reason. | Order `cancelled`, actor/reason/timestamp tersimpan. |
| SAL-22 | Cancel completed | Coba cancel order completed. | Ditolak. |
| SAL-23 | Void completed | POST `/orders/{order}/void` dengan approval valid. | Order `voided`, audit dan reason tersimpan. |
| SAL-24 | Void without approval | Coba void tanpa approval. | Ditolak. |
| SAL-25 | Refund completed | POST `/orders/{order}/refund` dengan approval valid. | Refund tercatat, payment reversal/audit tersedia. |
| SAL-26 | Refund without approval | Refund tanpa approval valid. | Ditolak. |
| SAL-27 | Cash in | POST `/shifts/{shift}/cash-movements` cash in. | Cash movement tercatat dan summary shift berubah. |
| SAL-28 | Cash out approval | Cash out yang butuh approval. | Tanpa approval ditolak; dengan approval valid diterima. |
| SAL-29 | Shift summary | GET `/shifts/{shift}/summary`. | Gross sales, expected cash, payments, refunds, movements akurat. |
| SAL-30 | Close shift exact | POST `/shifts/{shift}/close` dengan counted cash sesuai. | Shift `closed`, summary tersimpan. |
| SAL-31 | Close shift discrepancy | Close dengan selisih kas. | Discrepancy tercatat sesuai policy. |
| SAL-32 | Operation after shift closed | Buat order baru dengan shift closed. | Ditolak sampai shift baru dibuka. |
| SAL-33 | Sales daily admin report | GET `/admin/tenants/{tenant}/sales/daily`. | Daily sales summary sesuai transaksi. |
| SAL-34 | Sales recovery check | Jalankan `php artisan sales:recovery-check --json`. | Tidak ada finding pada data normal; finding muncul untuk state sengaja rusak. |

## Operational Safety

### Desain

Operational safety memperkuat aksi sensitif dengan approval, audit append-only, recovery checks, dan idempotency. Aksi seperti void, refund, cash out, dan data finansial kritis tidak boleh silent mutation tanpa actor/reason.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| OPS-01 | Create approval request | Dari alur void/refund/cash out, buat approval bila action membutuhkan supervisor. | Approval pending/approved sesuai policy. |
| OPS-02 | Consume approval once | Pakai approval untuk void/refund. | Approval menjadi consumed dan tidak dapat dipakai ulang. |
| OPS-03 | Wrong approver | Pakai approval dari tenant/outlet/user tidak sesuai. | Ditolak. |
| OPS-04 | Audit event append-only | Lakukan cancel, void, refund, cash movement. | Audit event bertambah, tidak menghapus event lama. |
| OPS-05 | Recovery ambiguous payment | Simulasikan completed order tanpa payment di DB/staging khusus. | `sales:recovery-check` menemukan finding. |
| OPS-06 | Prune audit events | Jalankan `php artisan sales:prune-audit-events`. | Event sesuai retention diproses sesuai konfigurasi. |

## Inventory

### Desain

Inventory memakai ledger stock movement. Balance adalah proyeksi dari movement. Adjustment, waste, transfer, low stock, stock card, dan recovery check wajib tenant/outlet scoped.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| INV-01 | Create unit | Buat unit inventory dari `/admin/tenants/{tenant}/inventory`. | Unit aktif tersimpan. |
| INV-02 | Duplicate unit symbol | Buat unit dengan symbol sama. | Ditolak. |
| INV-03 | Create inventory item | Buat inventory item SKU unik dengan base unit. | Item aktif tersimpan. |
| INV-04 | Duplicate item SKU | Buat SKU sama dalam tenant. | Ditolak. |
| INV-05 | Opening balance | Tambah opening balance outlet MAIN. | Movement opening balance tercatat dan balance naik. |
| INV-06 | Retry opening balance | Ulang idempotency key sama bila tersedia. | Tidak menggandakan movement. |
| INV-07 | Stock adjustment positive | Tambah adjustment plus. | Balance naik dan stock card mencatat movement. |
| INV-08 | Stock adjustment negative valid | Adjustment minus di bawah balance. | Balance turun. |
| INV-09 | Negative stock prevention | Adjustment minus melebihi balance. | Ditolak. |
| INV-10 | Record waste | Catat waste. | Movement waste tercatat, balance turun, reason tersimpan. |
| INV-11 | Stock card | GET halaman stock card item/outlet. | Semua movement muncul urut dan balance akhir benar. |
| INV-12 | Outlet settings | Set low stock threshold. | Threshold tersimpan per outlet/item. |
| INV-13 | Low stock report | Turunkan balance di bawah threshold lalu buka low stock outlet. | Item muncul sebagai low stock. |
| INV-14 | Transfer create/send/receive | Buat transfer antar outlet dan jalankan lifecycle kirim/terima. | Source turun saat send/receive sesuai policy; destination naik saat receive. |
| INV-15 | Transfer duplicate receive | Retry receive yang sama. | Tidak menggandakan movement. |
| INV-16 | Inventory recovery check | Jalankan `php artisan inventory:recovery-check --json`. | Tidak ada finding pada data normal. |

## Recipe

### Desain

Recipe menghubungkan variant sellable dengan ingredient inventory, recipe version, costing, dan sales deduction. Deduction dilakukan idempotent saat order selesai dan tidak boleh membuat balance negatif.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| RCP-01 | Create recipe | Buat recipe dari `/admin/tenants/{tenant}/recipes`. | Recipe aktif/draft tersimpan sesuai status. |
| RCP-02 | Add recipe version | Tambah version dengan ingredients. | Version punya cost snapshot. |
| RCP-03 | Activate version | Aktifkan version recipe. | Version aktif menjadi acuan deduction. |
| RCP-04 | Map variant to recipe | Hubungkan product variant ke recipe. | Variant membutuhkan deduction saat sales. |
| RCP-05 | Complete sales with stock | Jual variant yang punya recipe dan stock cukup. | Order completed, inventory movement sales deduction dibuat satu kali. |
| RCP-06 | Complete sales insufficient stock | Jual variant dengan stock bahan tidak cukup. | Completion ditolak atau conflict sesuai flow, order tidak selesai diam-diam. |
| RCP-07 | Retry deduction | Retry completion/idempotency. | Deduction tidak dobel. |
| RCP-08 | Recipe inactive version | Jual variant yang mapping ke version inactive. | Ditolak dengan business error. |

## Procurement

### Desain

Procurement mengelola supplier, supplier item mapping, purchase order, approval lifecycle, goods receipt, purchase return, dan integrasi inventory movement.

Jalur manual saat ini: supplier dan supplier item tersedia melalui Tenant Web Admin; PO, goods receipt, dan return diverifikasi melalui action harness/Tinker atau feature test sampai route publiknya ditambahkan.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| PRC-01 | Create supplier | Buka `/admin/tenants/{tenant}/procurement`, buat supplier. | Supplier aktif tersimpan. |
| PRC-02 | Update supplier | Ubah data supplier. | Perubahan tersimpan tenant scoped. |
| PRC-03 | Disable supplier | Nonaktifkan supplier. | Supplier tidak bisa dipakai PO baru. |
| PRC-04 | Supplier item mapping | Buat mapping supplier item ke inventory item. | Mapping tersimpan dengan unit/price. |
| PRC-05 | Create purchase order | Buat PO ke supplier aktif. | PO draft/pending tersimpan. |
| PRC-06 | Approve PO | Approve PO sesuai role. | Status berubah approved, audit tercatat. |
| PRC-07 | Cancel PO | Cancel PO dengan reason. | Status cancelled, tidak bisa receive. |
| PRC-08 | Receive goods partial | Receive sebagian item PO. | Goods receipt posted, inventory movement bertambah. |
| PRC-09 | Receive goods full | Receive sisa item PO. | PO menjadi received/closed sesuai policy. |
| PRC-10 | Duplicate receipt retry | Retry receipt idempotency. | Movement inventory tidak dobel. |
| PRC-11 | Purchase return | Catat return ke supplier. | Stock movement keluar tercatat dan PO/receipt reference jelas. |
| PRC-12 | Over receipt | Receive melebihi PO policy. | Ditolak bila over-receipt tidak diizinkan. |

## Dining

### Desain

Dining mengelola floor, table, table session, linking order ke meja, transfer, merge, close, dan cancel. Status meja harus mengikuti session aktif.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| DIN-01 | Create floor | Buka `/admin/tenants/{tenant}/dining`, buat floor. | Floor aktif tersimpan. |
| DIN-02 | Update floor | Ubah nama/code/display order floor. | Perubahan tampil. |
| DIN-03 | Disable floor | Nonaktifkan floor. | Floor/table terkait tidak tersedia untuk session baru. |
| DIN-04 | Create table | Buat table pada floor aktif. | Table aktif tersimpan. |
| DIN-05 | Update table | Ubah kapasitas/nama table. | Perubahan tersimpan. |
| DIN-06 | Disable table | Nonaktifkan table. | Table tidak bisa dibuka session baru. |
| DIN-07 | Open table session | Buka session untuk meja kosong. | Session open, table occupied. |
| DIN-08 | Open occupied table | Coba buka session kedua pada meja occupied. | Ditolak. |
| DIN-09 | Link order to session | Buat order lalu link ke table session. | Order terkait session, table tetap occupied. |
| DIN-10 | Transfer table | Transfer session dari table A ke B kosong. | Table A available, table B occupied. |
| DIN-11 | Transfer to occupied table | Transfer ke table occupied. | Ditolak. |
| DIN-12 | Merge sessions | Merge session dua meja sesuai policy. | Session target menyimpan relation/merged state, source tidak aktif. |
| DIN-13 | Close table session | Close session setelah order selesai. | Session closed, table available. |
| DIN-14 | Cancel table session | Cancel session tanpa transaksi final. | Session cancelled, table available. |

## Kitchen

### Desain

Kitchen mengelola station, routing rule, kitchen ticket, ticket item, ticket event, KDS snapshot, realtime channel, print job, retry, dan reprint. Kitchen tidak mengubah financial snapshot Sales.

Jalur manual saat ini: KDS snapshot tersedia melalui POS REST API; station/routing/ticket/print lifecycle diverifikasi melalui action harness/Tinker atau feature test sampai Web Admin/endpoint operasionalnya ditambahkan.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| KDS-01 | Create station | Buat kitchen station default/non-default. | Station aktif tersimpan. |
| KDS-02 | Routing category | Buat routing rule category ke station. | Item category tersebut masuk station. |
| KDS-03 | Routing product | Buat routing product. | Product rule mengalahkan category rule. |
| KDS-04 | Routing variant | Buat routing variant. | Variant rule mengalahkan product/category. |
| KDS-05 | Fallback station | Hapus rule eksplisit, pakai station default. | Ticket masuk fallback station. |
| KDS-06 | Create ticket for completed order | Complete order dengan item. | Kitchen ticket dan items dibuat idempotent. |
| KDS-07 | Retry ticket creation | Jalankan creation lagi. | Tidak ada ticket/item duplikat. |
| KDS-08 | KDS snapshot | GET `/api/v1/pos/outlets/{outlet}/kds/snapshot`. | Ticket aktif muncul sesuai station/filter. |
| KDS-09 | Status preparing | Ubah ticket ke preparing. | Status dan ticket event tercatat. |
| KDS-10 | Status ready | Ubah ticket ke ready. | KDS snapshot menampilkan ready. |
| KDS-11 | Broadcast authorization | Subscribe channel tenant/outlet yang benar dan salah. | Hanya context valid yang authorized. |
| KDS-12 | Print job success | Dispatch print job dengan dispatcher berhasil. | Print job `sent`. |
| KDS-13 | Print job failed | Simulasikan printer offline. | Print job `failed` dengan reason. |
| KDS-14 | Retry print | Retry failed print. | Print job baru/reference retry tercatat. |
| KDS-15 | Reprint | Reprint ticket dengan reason. | Print job `reprint` tercatat append-only. |

## Ordering Channel and QR Self Order

### Desain

Ordering Channel mengelola QR session, public QR catalog, customer cart, order request, staff confirmation, dan waiter order. Public QR tidak boleh menjadi authority final Sales; staff confirmation tetap melewati Sales boundary.

Jalur manual saat ini: public QR catalog tersedia melalui Public REST API; QR session, cart, submit, staff confirmation, dan waiter workflow diverifikasi melalui action harness/Tinker atau feature test sampai endpoint/layer UI ditambahkan.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| ORD-CH-01 | Create QR session | Buat QR session untuk outlet/table/pickup. | Token signed/opaque dibuat. |
| ORD-CH-02 | Public QR catalog | GET `/api/v1/qr/{token}`. | Catalog outlet tampil tanpa data tenant sensitif. |
| ORD-CH-03 | Invalid QR token | GET token salah/expired. | Ditolak. |
| ORD-CH-04 | Create customer cart | Customer mengisi nama/phone dan membuat cart. | Cart dibuat scoped ke QR session. |
| ORD-CH-05 | Add cart item | Tambah product valid ke cart. | Cart total berubah. |
| ORD-CH-06 | Add unavailable item | Tambah product unavailable. | Ditolak. |
| ORD-CH-07 | Submit order request | Submit cart. | Order request `submitted/pending` dibuat. |
| ORD-CH-08 | Staff confirm request | Staff confirm order request. | Sales order dibuat lewat Sales action dan request `confirmed`. |
| ORD-CH-09 | Confirm duplicate | Confirm request yang sama dengan idempotency sama. | Order sama dikembalikan, tidak duplikat. |
| ORD-CH-10 | Reject/cancel request | Staff reject/cancel request bila tersedia. | Status request berubah dan tidak membuat Sales order. |
| ORD-CH-11 | Waiter order | Staff buat waiter order untuk table/pickup. | Sales draft/order item dibuat melalui Sales boundary. |
| ORD-CH-12 | Waiter table order | Buat waiter order untuk active table session. | Order terhubung ke dining session. |

## Payments Gateway

### Desain

Payments Gateway menyediakan abstraction provider, payment intent, webhook inbox, HMAC signature, idempotency, replay safety, dan redaction data sensitif. Provider default saat ini fake/local.

Jalur manual saat ini: payment intent dan webhook gateway diverifikasi melalui action harness/Tinker atau feature test. Payment cash/manual non-cash reguler tersedia melalui POS Sales API.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| PAY-GW-01 | Create payment intent | Buat Sales order lalu create payment intent. | Intent pending dibuat dengan provider reference. |
| PAY-GW-02 | Intent duplicate | Retry create intent idempotent. | Intent sama dikembalikan. |
| PAY-GW-03 | Signed paid webhook | Kirim webhook signed `payment_intent.paid`. | Event accepted, intent paid, order completed. |
| PAY-GW-04 | Webhook replay | Kirim webhook sama lagi. | No-op/idempotent, tidak membuat payment ganda. |
| PAY-GW-05 | Invalid signature | Kirim webhook dengan signature salah. | Ditolak. |
| PAY-GW-06 | Redaction | Sertakan `card_number` atau data sensitif. | Payload tersimpan tanpa field sensitif. |
| PAY-GW-07 | Unknown intent | Webhook untuk intent tidak dikenal. | Ditolak atau recorded sebagai failed sesuai policy. |
| PAY-GW-08 | Offline gateway denied | Kirim sync offline yang membawa gateway provider/intent. | Sync rejected, tidak membuat Sales order/payment. |

## Reservation

### Desain

Reservation mengelola booking minimum dan seating ke table session. Seating harus menghubungkan reservation dengan Dining session yang sah.

Jalur manual saat ini: reservation lifecycle diverifikasi melalui action harness/Tinker atau feature test sampai UI/endpoint publik ditambahkan.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| RSV-01 | Create reservation | Buat reservation untuk outlet, waktu, party size, customer phone. | Reservation `booked` dibuat. |
| RSV-02 | Invalid reservation time | Buat reservation waktu lampau atau data invalid. | Ditolak. |
| RSV-03 | Seat reservation | Buka table session lalu seat reservation. | Reservation `seated`, `table_session_id` terisi. |
| RSV-04 | Seat duplicate | Seat reservation yang sudah seated. | Ditolak. |
| RSV-05 | Cancel reservation | Cancel reservation sebelum seated bila tersedia. | Status cancelled, tidak ada table session baru. |
| RSV-06 | Cross outlet seating | Seat reservation outlet A ke table session outlet B. | Ditolak. |

## Promotion

### Desain

Promotion mengelola rule fixed/percentage dan snapshot discount pada Sales order. Saat ini satu promotion per order. Discount mengubah total order tetapi snapshot discount harus tetap rekonsilable.

Jalur manual saat ini: promotion rule dan apply-to-order diverifikasi melalui action harness/Tinker atau feature test sampai UI/endpoint publik ditambahkan.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| PRM-01 | Create fixed promotion | Buat rule fixed amount. | Promotion aktif tersimpan. |
| PRM-02 | Create percentage promotion | Buat rule percentage. | Promotion aktif tersimpan. |
| PRM-03 | Apply fixed promotion | Apply ke draft order. | `sales_order_discounts` dibuat dan total turun sesuai fixed amount. |
| PRM-04 | Apply percentage promotion | Apply ke draft order. | Discount dihitung persentase dari eligible subtotal. |
| PRM-05 | Apply duplicate promotion | Apply promotion kedua ke order yang sama. | Ditolak sesuai one-promotion MVP. |
| PRM-06 | Apply to completed order | Apply promotion ke completed order. | Ditolak. |
| PRM-07 | Discount floor | Fixed discount melebihi subtotal. | Total tidak negatif; discount dibatasi sesuai policy. |
| PRM-08 | Receipt snapshot | Complete order setelah promotion. | Receipt memuat discount snapshot. |

## Reporting

### Desain

Reporting membaca lintas modul untuk summary dan export, tetapi tidak mengubah source-of-truth. Data sensitif harus diredaksi pada analytics export.

Jalur manual saat ini: Sales daily tersedia melalui Tenant Web Admin; analytics export diverifikasi melalui action harness/Tinker atau feature test sampai UI/endpoint publik ditambahkan.

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| RPT-01 | Daily sales summary | Buat beberapa order completed, buka report sales daily. | Gross/net/payment breakdown sesuai transaksi. |
| RPT-02 | Void/cancel summary | Buat cancelled dan voided order. | Summary audit menampilkan jumlah/nilai sesuai. |
| RPT-03 | Shift summary reconciliation | Bandingkan shift summary dengan reporting. | Angka bisa direkonsiliasi. |
| RPT-04 | Analytics export normal | Buat analytics export dengan filter outlet/date. | Export record dibuat dan result sesuai data. |
| RPT-05 | Analytics export redaction | Sertakan `customer_phone`, `card_number`, atau PII lain pada filter. | Data sensitif dihapus/diredaksi. |
| RPT-06 | Tenant isolation reporting | User tenant A mencoba report tenant B. | Ditolak. |

## Sync and Offline Scale

### Desain

Sync mengelola offline bootstrap, catalog snapshot, inbox mutation, outbox pull, device state, offline order draft/event, conflict, performance baseline, dan recovery objective. Offline mutation scope sangat terbatas. Server tetap authority final.

### Endpoint Utama

- `GET /api/v1/pos/outlets/{outlet}/sync/bootstrap`
- `GET /api/v1/pos/outlets/{outlet}/sync/catalog-snapshot`
- `POST /api/v1/pos/outlets/{outlet}/sync/push`
- `GET /api/v1/pos/outlets/{outlet}/sync/pull`

### Manual Test Cases

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| SYN-01 | Bootstrap active device | GET sync bootstrap dengan token POS aktif. | Retention policy tampil, encryption required, server tidak menerima local encryption key. |
| SYN-02 | Bootstrap revoked device | GET bootstrap dengan device revoked. | Ditolak oleh POS context/device guard. |
| SYN-03 | Catalog snapshot | GET sync catalog snapshot. | Response punya `version`, `catalog`, dan `retention_hours`. |
| SYN-04 | Snapshot outbox | Setelah snapshot, pull outbox. | Ada event `catalog.snapshot.generated`. |
| SYN-05 | Push create draft | POST sync push action `offline_order.create_draft`. | Inbox accepted, offline draft dibuat. |
| SYN-06 | Push duplicate same hash | Ulang request scope dan hash sama. | Status duplicate, resource sama, tidak dobel. |
| SYN-07 | Push same scope different hash | Ulang scope sama dengan hash berbeda. | Conflict dibuat, no silent overwrite. |
| SYN-08 | Push denied mutation | Kirim action refund/void/inventory/catalog/device/gateway. | Status rejected atau business error `SYNC_OPERATION_NOT_ALLOWED_OFFLINE`. |
| SYN-09 | Sequence conflict | Kirim sequence lebih kecil/sama tanpa inbox matching. | Conflict `sequence_conflict`. |
| SYN-10 | Add offline item | Push `offline_order.add_item`. | Offline order event append-only tercatat. |
| SYN-11 | Update offline item | Push `offline_order.update_item`. | Event tercatat, draft tetap queued. |
| SYN-12 | Remove offline item | Push `offline_order.remove_item`. | Event tercatat. |
| SYN-13 | Complete cash offline | Push `offline_order.complete_cash` dengan items/amount/currency valid. | Sales order completed, payment cash, receipt, outbox `offline_order.accepted`. |
| SYN-14 | Complete manual offline | Push `offline_order.complete_manual`. | Sales order completed dengan manual non-cash. |
| SYN-15 | Complete wrong amount | Payload amount tidak sama total. | Ditolak; tidak membuat financial state ambiguous. |
| SYN-16 | Stale catalog conflict | Kirim `catalog_version` lama. | Conflict dibuat, Sales order tidak dibuat. |
| SYN-17 | Insufficient stock conflict | Kirim payload `insufficient_stock=true` atau `stock_available=false`. | Conflict dibuat, draft status conflict. |
| SYN-18 | Offline gateway denied | Payload membawa `payment_provider`, `gateway`, atau `gateway_intent_id`. | Mutation rejected, draft rejected, tidak ada Sales order. |
| SYN-19 | Pull outbox cursor | GET sync pull tanpa cursor lalu dengan `after_cursor`. | Batch pertama muncul; batch kedua hanya records setelah cursor. |
| SYN-20 | Device state cursor | Pull outbox berhasil. | `last_outbox_cursor` dan `last_synced_at` device state berubah. |
| SYN-21 | Resolve conflict | Operator resolve conflict dengan reason. | Conflict `resolved`, actor/reason/timestamp tersimpan. |
| SYN-22 | Dismiss conflict | Operator dismiss conflict bila valid. | Conflict `dismissed`, tidak mengubah financial data. |
| SYN-23 | Revoked sync state | Set `sync_device_states.revoked_at`, push mutation baru. | Ditolak `SYNC_DEVICE_REVOKED`. |
| SYN-24 | Performance baseline pass | Jalankan `php artisan sync:performance-baseline sync_push_p95 900 --target=1000 --json`. | Baseline `passed` tersimpan. |
| SYN-25 | Performance baseline breach | Jalankan command dengan measured > target dan `--fail-on-breach`. | Exit non-zero dan baseline failed/exception sesuai flow. |
| SYN-26 | Recovery objective evidence | Jalankan action/check di staging untuk RPO/RTO. | Evidence baseline tercatat dan breach menggagalkan readiness. |

## Security and Isolation Regression

| ID | Scenario | Langkah | Expected Result |
|---|---|---|---|
| SEC-01 | API without token | Panggil POS endpoint tanpa bearer token. | 401 unauthorized. |
| SEC-02 | API with wrong token | Pakai token user lain/device lain. | 401/403. |
| SEC-03 | Tenant URL tampering | Ubah `{tenant}` di URL admin ke tenant lain. | Access denied/not found. |
| SEC-04 | Outlet URL tampering | Ubah `{outlet}` di POS API ke outlet lain. | Ditolak. |
| SEC-05 | Disabled outlet | Pakai outlet disabled. | Ditolak. |
| SEC-06 | Revoked device token | Pakai token device revoked. | Ditolak untuk operasi outlet/sync. |
| SEC-07 | Role restriction cashier | Cashier akses user/device/catalog admin. | Ditolak. |
| SEC-08 | Sensitive action no reason | Void/refund/cancel tanpa reason. | Validation error. |
| SEC-09 | Mass assignment attempt | Kirim payload dengan `tenant_id`, `status`, `total_minor` palsu ke endpoint mutation. | Field tidak dipercaya; backend tetap memakai context/server calculation. |
| SEC-10 | PII redaction | Kirim card/phone pada webhook/export. | Tidak tersimpan mentah di payload yang seharusnya redacted. |

## Cross-Module End-to-End Journeys

### Journey 01: Tenant to First Sale

1. Platform admin bootstrap dan login MFA.
2. Provision tenant, owner, outlet.
3. Owner login tenant admin.
4. Register POS device.
5. Buat category dan product available di outlet.
6. POS login dengan device.
7. Open shift.
8. Create draft order.
9. Add item.
10. Complete cash exact.
11. Ambil receipt.
12. Close shift.
13. Buka daily sales report.

Expected result: tenant siap operasi, order completed punya payment dan receipt, shift summary dan report bisa direkonsiliasi.

### Journey 02: Catalog Snapshot Integrity

1. Buat product dengan variant dan modifier.
2. Ambil POS catalog.
3. Buat order dan complete.
4. Ubah nama/harga product, variant, modifier.
5. Ambil receipt lama.

Expected result: catalog baru berubah, tetapi snapshot order/receipt lama tetap sama.

### Journey 03: Inventory and Recipe Deduction

1. Buat inventory unit/item.
2. Input opening balance.
3. Buat recipe version dan mapping ke product variant.
4. Jual variant tersebut.
5. Cek stock card.

Expected result: sales completion membuat deduction satu kali, balance turun sesuai recipe.

### Journey 04: Procurement to Inventory

1. Buat supplier dan supplier item.
2. Buat purchase order.
3. Approve PO.
4. Receive goods.
5. Buka inventory stock card.

Expected result: receipt menghasilkan movement inventory dan balance naik.

### Journey 05: Dine-In to Kitchen

1. Buat floor/table.
2. Buat kitchen station dan routing.
3. Open table session.
4. Buat waiter/dine-in order.
5. Complete/confirm order sesuai flow.
6. Generate kitchen ticket.
7. Buka KDS snapshot.
8. Ubah ticket preparing lalu ready.
9. Dispatch print job dan reprint.

Expected result: meja occupied sampai session close, ticket routed benar, kitchen events append-only, print retry/reprint tercatat.

### Journey 06: QR Self Order

1. Buat QR session untuk outlet/table.
2. Customer buka public QR catalog.
3. Customer buat cart dan submit order request.
4. Staff confirm request.
5. Sales order dibuat dan dapat dilanjutkan ke payment/kitchen.

Expected result: public customer tidak menentukan final financial state; staff confirmation memakai Sales boundary.

### Journey 07: Gateway Payment

1. Buat Sales order draft dengan item.
2. Buat payment intent.
3. Kirim signed webhook paid.
4. Retry webhook sama.
5. Cek order, payment, receipt, webhook inbox.

Expected result: order completed satu kali, webhook replay aman, payload sensitif redacted.

### Journey 08: Offline Recovery

1. POS login dan ambil sync bootstrap.
2. Ambil catalog snapshot.
3. Simulasikan offline create draft dan add item di client.
4. Saat online, push mutation berurutan.
5. Push completion cash/manual.
6. Pull outbox.
7. Simulasikan duplicate push dan payload conflict.

Expected result: mutation accepted idempotent, duplicate aman, completion menjadi Sales order final, conflict masuk queue review.

## Production Readiness Checklist

| ID | Area | Check | Expected Result |
|---|---|---|---|
| PRD-01 | Migration | Jalankan migration fresh di MariaDB testing/staging. | Semua migration berhasil. |
| PRD-02 | Static quality | `composer quality:static`. | Lulus tanpa violation. |
| PRD-03 | Feature tests | `php artisan test --testsuite=Feature`. | Lulus di MariaDB aktif. |
| PRD-04 | API docs | Cocokkan endpoint API aktual dengan `docs/api/openapi.yaml`. | Tidak ada endpoint critical yang hilang dari dokumentasi. |
| PRD-05 | Backup restore | Restore backup staging. | Data tenant, sales, inventory, sync tetap konsisten. |
| PRD-06 | RPO/RTO evidence | Catat recovery objective. | Target terpenuhi atau readiness gagal. |
| PRD-07 | Load baseline | Catat p95 catalog/order/sync. | Baseline passed atau ada corrective action. |
| PRD-08 | Audit review | Sampling audit sensitive actions. | Actor, reason, timestamp, resource lengkap. |
| PRD-09 | Tenant isolation | Jalankan negative cross-tenant suite manual. | Tidak ada data leak. |
| PRD-10 | Device revocation | Revoke device lalu coba POS/sync. | Semua mutation baru ditolak. |

## Defect Report Template

```text
ID:
Tanggal/Jam:
Tester:
Environment:
Tenant:
Outlet:
User/Role:
Device:
Module:
Test Case ID:
Expected:
Actual:
Steps to Reproduce:
Request ID:
Payload/URL:
Screenshot/Log:
Severity:
Notes:
```
