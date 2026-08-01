# ADR-040: Inventory Ledger MVP Policy

- Status: Accepted
- Date: 2026-08-01

## Context

Phase 05 membangun inventory setelah Catalog dan Sales stabil. Targetnya bukan sekadar menyimpan angka stok saat ini, tetapi membuat setiap perubahan balance dapat ditelusuri melalui ledger movement yang immutable.

POS SaaS ini perlu mendukung operasional outlet F&B awal seperti opening balance, adjustment, waste, transfer antar outlet, stock card, low-stock, dan valuation minimum. Recipe auto-deduction dari Sales sengaja ditunda sampai Phase 06 agar ownership recipe/BOM, yield, dan costing tidak menjadi dependency tersembunyi Phase 05.

## Decision

### Module Boundary

- Inventory menjadi module terpisah dari Catalog dan Sales.
- Catalog mendefinisikan menu/product yang dijual.
- Inventory mendefinisikan stock item operasional, unit, balance, movement, adjustment, waste, transfer, dan stock card.
- Phase 05 tidak menghubungkan product variant/modifier ke stock deduction otomatis.
- Mapping Catalog product ke Inventory item ditunda sampai recipe/procurement decision Phase 06, kecuali dibutuhkan sebagai referensi manual non-mutating.

### Inventory Item And Unit

- Inventory item adalah barang fisik yang dihitung stoknya, misalnya "Biji Kopi Arabica", "Susu UHT", atau "Cup 12 oz".
- Unit dasar disimpan per inventory item, misalnya gram, ml, atau pcs.
- Phase 05 memakai satu base unit per item untuk semua movement.
- Unit conversion kompleks seperti box ke pcs, kg ke gram otomatis, yield, dan packaging hierarchy ditunda.
- Quantity disimpan sebagai decimal fixed precision, bukan float.
- Precision minimum mengikuti kebutuhan operasional F&B: sampai 3 digit desimal untuk quantity.
- Request quantity diterima sebagai string decimal dan dinormalisasi ke 3 digit desimal.

### Negative Stock

- Negative stock ditolak untuk mutation normal.
- Opening balance, adjustment decrease, waste, dan transfer dispatch tidak boleh membuat balance di bawah nol.
- Koreksi khusus yang membutuhkan negative stock harus berhenti dan membutuhkan keputusan baru.
- Sistem harus mengembalikan business error yang eksplisit saat stok tidak cukup.

### Ledger And Balance

- Stock movement ledger adalah sumber kebenaran audit.
- Current balance wajib disimpan sebagai read model/projection untuk performa, locking, dan query operasional, tetapi harus dapat direkonsiliasi ulang dari ledger.
- Movement bersifat immutable setelah dicatat.
- Pembatalan kesalahan dilakukan dengan reversal movement, bukan edit/delete movement lama.
- Setiap movement menyimpan tenant, outlet, item, unit, signed quantity, source type, source id, actor, reason bila relevan, idempotency key, dan occurred_at.
- Opening balance hanya boleh satu kali per tenant/outlet/item; koreksi berikutnya memakai adjustment.

### Costing And Valuation

- Phase 05 menggunakan moving average cost sederhana berbasis `total_cost_minor` untuk valuation minimum.
- Cost disimpan dalam money minor unit dan currency tenant.
- Opening balance dan inbound movement boleh membawa unit cost.
- Opening balance dan inbound movement wajib membawa `total_cost_minor`; average cost adalah nilai turunan dari `total_cost_minor / quantity`.
- Outbound movement mengurangi `total_cost_minor` secara proporsional terhadap quantity keluar dan memakai average cost saat movement dicatat.
- Jika balance quantity menjadi nol, balance `total_cost_minor` menjadi nol.
- FIFO, landed cost, multi-currency valuation, dan accounting journal ditunda.

### Adjustment And Waste

- Adjustment increase/decrease tersedia untuk koreksi stok.
- Waste tersedia sebagai outbound movement khusus untuk barang rusak, hilang, expired operasional, atau tidak layak pakai.
- Reason wajib untuk adjustment dan waste.
- Approval wajib untuk adjustment decrease/waste/transfer di atas threshold quantity yang dikonfigurasi.
- Default threshold Phase 05 adalah `0.000`, sehingga semua adjustment decrease, waste, dan transfer dengan quantity positif membutuhkan approval.
- Approval mengikuti pola Operational Safety Phase 03: approver berbeda dari requester, tenant/outlet scoped, single-use, dan auditable.

### Transfer Lifecycle

- Transfer hanya berlaku antar outlet dalam tenant yang sama.
- Lifecycle minimum: `draft`, `requested`, `approved`, `dispatched`, `received`, `cancelled`.
- Dispatch mengurangi balance source outlet.
- Receive menambah balance destination outlet.
- Transfer yang sudah `dispatched` tetapi belum `received` dilaporkan sebagai `in_transit_quantity`.
- Cancel hanya boleh sebelum dispatch.
- Partial receive dan receiving variance ditunda kecuali diputuskan ulang.
- Transfer movement source dan destination harus dapat ditelusuri ke transfer yang sama.

### Idempotency, Locking, And Recovery

- Semua stock mutation memakai idempotency key.
- Retry dengan idempotency key dan payload sama mengembalikan hasil yang sama.
- Retry dengan idempotency key sama tetapi payload berbeda ditolak sebagai conflict.
- Idempotency scope adalah tenant, outlet, user, action, dan idempotency key.
- Mutation stock berjalan dalam database transaction.
- Balance per tenant/outlet/item dikunci saat mutation untuk mencegah race condition.
- Recovery check harus bisa membandingkan balance projection dengan ledger.
- Auto-repair tidak masuk Phase 05; recovery awal bersifat read-only report.

### Batch And Expiry

- Batch/lot dan expiry tracking ditunda.
- Expired operational dapat dicatat sebagai waste dengan reason manual.
- Jika target bisnis berubah ke domain yang wajib traceability batch/expiry, Phase 05 harus berhenti dan ADR baru dibuat.

## Consequences

- Ledger menjadi pusat desain Inventory, sehingga implementasi lebih auditable walau sedikit lebih banyak tabel/action.
- POS Sales belum otomatis mengurangi stok sampai recipe/BOM Phase 06 siap.
- Moving average memberi valuation cukup untuk MVP, tetapi belum memenuhi kebutuhan accounting detail.
- Menolak negative stock membuat data lebih bersih, tetapi operator perlu opening balance/adjustment yang disiplin.
- Transfer dispatch/receive membuat ownership antar outlet jelas dan mudah direkonsiliasi.

## Resolved Questions

- Negative stock ditolak untuk mutation normal.
- Unit conversion kompleks ditunda; Phase 05 memakai satu base unit per item.
- Quantity memakai decimal fixed precision sampai 3 digit desimal.
- Current balance projection wajib ada dan dikunci saat mutation.
- Costing memakai moving average sederhana berbasis total cost minor.
- Opening balance hanya boleh satu kali per tenant/outlet/item.
- Batch/expiry ditunda.
- Dispatch mengurangi source outlet, receive menambah destination outlet, dan dispatched stock dilaporkan sebagai in transit.
- Approval wajib untuk adjustment decrease, waste, dan transfer di atas threshold quantity; default threshold `0.000`.
- Recipe auto-deduction dari Sales ditunda sampai Phase 06.

## Approval

Product owner menyetujui keputusan ini pada 2026-08-01 saat Phase 05 dimulai.
