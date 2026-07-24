# Cross-Module Business Rules

Aturan rinci berada pada dokumen modul. Dokumen ini memuat invariant lintas modul.

## Tenant dan Outlet

- Setiap data bisnis dimiliki tenant secara langsung atau melalui outlet yang tervalidasi.
- Relasi lintas tenant dilarang, termasuk melalui foreign key tidak langsung.
- Konteks tenant tidak boleh dipercaya hanya dari input client; server harus memvalidasi membership pengguna.

## Uang dan Transaksi

- Monetary amount final menggunakan signed integer minor units dan tidak menggunakan floating-point.
- Untuk IDR, satu minor unit sama dengan satu rupiah.
- Quantity, costing, dan intermediate calculation menggunakan fixed-point decimal.
- Percentage rate menggunakan basis points ketika presisi `0,01%` mencukupi.
- Financial result dibulatkan half-up ke minor unit.
- Currency, calculation order tax/discount/service charge, dan allocation rule harus eksplisit.
- Order item menyimpan snapshot nama, harga, pajak, discount, variant, dan modifier yang memengaruhi transaksi.
- Nomor bisnis terpisah dari ULID primary key.
- Transaksi finansial tidak di-hard-delete.
- Void, cancel, refund, discount override, dan cash adjustment mencatat actor, alasan, serta waktu.
- Request create order/payment yang dapat diulang harus mendukung idempotency.

## Shift

- Aksi penjualan kasir harus terhubung ke shift aktif bila kebijakan outlet mengharuskannya.
- Satu user tidak boleh memiliki lebih dari satu shift aktif pada outlet yang sama kecuali diputuskan berbeda.
- Close shift menghasilkan snapshot ringkasan yang dapat direkonsiliasi.

## Stock

- Stock tidak boleh diubah langsung tanpa stock movement atau dokumen sumber.
- Waktu pengurangan stock dan metode costing masih merupakan keputusan arsitektur terbuka.
- Retry event tidak boleh menggandakan stock deduction.

## Waktu

- Timestamp persisten menggunakan UTC.
- Tanggal bisnis dan nomor harian dihitung menggunakan timezone outlet.

## Keputusan Terbuka

- Currency awal serta tax inclusive/exclusive dan urutan discount/tax/service charge.
- Status lifecycle order serta titik order dianggap final.
- Waktu stock deduction.
- Refund parsial, split payment, dan reopen order pada MVP.
