# Vision and Scope

## Visi

Membangun sistem POS F&B multi-tenant yang aman dan dapat berkembang dari transaksi kasir dasar menuju operasi multi-outlet, inventory, kitchen, self-order, dan offline mobile.

## Sasaran MVP

MVP membuktikan satu alur bisnis utuh:

> Pengguna masuk, memilih outlet, membuka shift, membuat order, menerima pembayaran, menerbitkan receipt, menutup shift, dan melihat ringkasan penjualan harian.

MVP menggunakan dua presentation client resmi: Web Admin berbasis Blade + Alpine.js untuk konfigurasi/back-office, dan Flutter untuk operasional POS. Keduanya menjalankan application use cases yang sama melalui presentation adapter masing-masing.

## In Scope MVP

- Controlled provisioning untuk tenant, initial outlet, dan initial owner; tanpa public tenant registration.
- Web Admin shell dengan session authentication dan tenant/outlet context eksplisit.
- Web Admin untuk tenant, outlet, user, role minimum, device, catalog sederhana, dan laporan harian.
- Autentikasi dan pemilihan outlet.
- Tenant dan outlet minimum yang dibutuhkan untuk operasi.
- Role kasir dan pengelola minimum.
- Kategori, produk, dan harga dasar.
- Open dan close shift.
- Order dengan item dan kuantitas.
- Pembayaran tunai dan metode pembayaran manual.
- Receipt dan nomor transaksi.
- Void/cancel minimum dengan otorisasi dan audit.
- Ringkasan shift dan penjualan harian.
- Flutter POS untuk alur operasional kasir; konfigurasi master data tidak dilakukan dari Flutter.
- Automated tests untuk jalur kritis dan isolasi tenant.

## Out of Scope MVP

- Public self-service tenant registration dan email invitation lifecycle.
- Inventory dan recipe lengkap.
- Purchase order dan goods receiving.
- KDS real-time.
- Table/floor management lanjutan.
- QR self-order.
- Payment gateway otomatis.
- Flutter offline synchronization penuh.
- Reservation, waiter app terpisah, dan loyalty.
- Advanced analytics dan accounting integration.

Item di luar MVP tetap berada dalam roadmap, tetapi tidak boleh menghambat penyelesaian alur transaksi dasar.

## Sasaran Non-Fungsional

- Tidak ada akses data lintas tenant tanpa otorisasi eksplisit.
- Mutasi finansial dapat diaudit.
- Request kritis aman terhadap retry dan duplikasi.
- Nilai uang dihitung deterministik tanpa floating-point.
- Data transaksi menyimpan snapshot yang diperlukan untuk histori.
- API memiliki kontrak error dan versioning yang konsisten.
- Operasi kritis memiliki automated tests sejak phase pembuatannya.

## Non-Goals Arsitektur Awal

- Microservices.
- Multi-database tenancy.
- Dukungan beberapa database engine sekaligus.
- Repository abstraction untuk setiap Eloquent model.
- Event sourcing penuh.
