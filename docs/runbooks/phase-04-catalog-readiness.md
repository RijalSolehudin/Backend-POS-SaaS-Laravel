# Phase 04 Catalog Readiness

Dokumen ini adalah gate akhir Phase 04 sebelum catalog expanded dipakai untuk pilot POS. Fokusnya memastikan category, variant, modifier, outlet override, order snapshot, dan transaksi historis sudah dapat dijelaskan dari sudut pandang user dan developer.

## Demo Path

Gunakan tenant aktif, outlet aktif, device POS aktif, cashier, dan owner/admin tenant.

| Step | Scenario | Expected result |
|---:|---|---|
| 1 | Owner membuat category parent dan child | category aktif tersimpan dengan urutan display |
| 2 | Owner membuat product aktif pada child category | product tampil tenant-scoped dan SKU unik per tenant |
| 3 | Owner membuat variant default dan variant tambahan | variant aktif tampil sebagai pilihan jual POS |
| 4 | Owner membuat modifier group required | cashier wajib memilih jumlah option sesuai min/max |
| 5 | Owner membuat modifier group optional | cashier bisa memilih option sampai batas max |
| 6 | Owner mengaktifkan product di outlet | product tersedia pada catalog POS outlet tersebut |
| 7 | Owner menambahkan outlet price override variant | harga POS memakai override outlet |
| 8 | Owner menonaktifkan salah satu variant/option untuk outlet | POS tidak menampilkan pilihan yang unavailable |
| 9 | Cashier membaca catalog POS | response berisi category hierarchy, variant, modifier, dan harga efektif |
| 10 | Cashier membuat draft order dan memilih variant/modifier | item tersimpan dengan snapshot catalog selection |
| 11 | Cashier menyelesaikan payment | order completed, receipt snapshot merekam pilihan dan harga saat transaksi |
| 12 | Owner mengubah catalog setelah order completed | receipt/order historis tidak berubah |
| 13 | Developer export catalog tenant | JSON export berisi section catalog expanded |
| 14 | Developer import dry-run file valid | command lulus tanpa write database |
| 15 | Developer import dry-run file invalid | command gagal dengan error yang dapat ditindak |

## Reconciliation Checklist

Operator dan developer harus bisa menelusuri:

- unit price item = harga variant efektif outlet + total modifier delta efektif outlet;
- line total = unit price final x quantity;
- order total sama dengan jumlah semua line total;
- receipt snapshot memakai nilai saat payment selesai, bukan nilai catalog terbaru;
- shift summary dan daily sales membaca order completed, refund, void, dan cash movement sesuai Phase 02/03;
- refund tidak mengubah original payment dan original receipt snapshot;
- void tidak menghapus jejak transaksi dan tetap auditable.

## Historical Immutability Checklist

| Change after completed order | Expected result |
|---|---|
| Product name berubah | receipt lama tetap memakai snapshot nama lama |
| Variant price berubah | order item lama tetap memakai unit price lama |
| Modifier option price berubah | order item lama tetap memakai modifier price lama |
| Variant dinonaktifkan | transaksi lama tetap dapat direkonsiliasi |
| Product tidak tersedia di outlet | transaksi lama tetap dapat direkonsiliasi |

## Developer Checklist

| Area | Evidence |
|---|---|
| Catalog read model | POS catalog hanya menampilkan product/variant/modifier aktif dan available |
| Tenant isolation | query dan validation selalu scoped ke tenant |
| Outlet isolation | availability dan price override scoped ke outlet |
| Order mutation | add item memakai resolver catalog efektif, bukan harga request mentah |
| Snapshot | order item dan receipt menyimpan variant/modifier selection |
| Import/export | export read-only, import baseline masih dry-run |
| Regression | Catalog suite dan Sales suite lulus |
| Quality | static analysis, formatting, dan build lulus |

## Import And Export Gate

Phase 04 hanya mengizinkan import dry-run. Import write baru boleh dibuka setelah aturan berikut disetujui:

- strategi validasi row-level dan error report;
- transaksi database untuk mencegah partial write;
- audit event untuk actor, tenant, target, dan reason;
- policy untuk deactivate vs delete;
- batas ukuran file dan mekanisme queue bila dibutuhkan.

## Known Limitations

- Belum ada inventory deduction.
- Belum ada recipe costing.
- Belum ada combo/bundle semantics.
- Belum ada scheduled pricing atau promotion engine.
- Belum ada tax/service category calculation.
- Belum ada catalog image/media management.

## Evidence Links

- [P04-02 Category Hierarchy and Display Ordering](../execution/phase-04/P04-02-category-hierarchy-display-ordering.md)
- [P04-03 Sellable Variants](../execution/phase-04/P04-03-sellable-variants.md)
- [P04-04 Modifier Groups and Options](../execution/phase-04/P04-04-modifier-groups-options.md)
- [P04-05 Outlet Availability and Price Overrides](../execution/phase-04/P04-05-outlet-availability-price-overrides.md)
- [P04-06 Order Item Snapshot Integration](../execution/phase-04/P04-06-order-item-snapshot-integration.md)
- [P04-07 Catalog Admin Operations and Import Baseline](../execution/phase-04/P04-07-catalog-admin-operations-import-baseline.md)
- [Catalog Import Export](catalog-import-export.md)

## Product Owner Sign-Off

Sebelum Phase 05 dimulai untuk inventory, product owner perlu menerima:

- bentuk catalog yang muncul di POS;
- aturan harga variant dan modifier;
- aturan outlet availability dan outlet override;
- bukti bahwa transaksi historis tidak berubah setelah catalog update;
- batasan Phase 04 yang belum mencakup inventory, recipe, combo, promotion, dan tax/service calculation.
