# ADR-039: Catalog Expansion MVP Policy

- Status: Accepted
- Date: 2026-08-01

## Context

Phase 01 membangun minimum catalog berupa category, product, dan outlet availability. Phase 02/03 memakai product snapshot sederhana pada order item dan menjaga receipt/order history immutable.

Phase 04 perlu memperluas catalog untuk kebutuhan F&B seperti category hierarchy, variant, modifier, outlet-specific availability, dan outlet price override. Keputusan ini harus jelas sebelum migration dan API baru dibuat agar pricing tetap deterministik dan transaksi historis tidak berubah saat catalog diedit.

## Decision

### Product And Sellable Model

- `Product` tetap menjadi menu item konseptual yang terlihat oleh admin, misalnya "Latte" atau "Nasi Goreng".
- Phase 04 memperkenalkan sellable variant sebagai unit yang dipilih POS, misalnya "Latte Hot Regular" atau "Latte Iced Large".
- Product sederhana dari Phase 01 tetap valid melalui default sellable variant yang dibuat secara additive.
- SKU untuk sellable variant unik dalam tenant. Product SKU lama tetap dipertahankan untuk backward compatibility dan dapat menjadi SKU default variant.
- POS catalog harus mengembalikan pilihan sellable yang sudah resolved, bukan memaksa client menghitung struktur internal catalog.

### Category Hierarchy

- Category hierarchy Phase 04 dibatasi ke parent-child sederhana.
- Category memiliki `display_order` untuk urutan POS/admin yang deterministik.
- Product tetap memiliki satu category utama.
- Product tidak boleh tampil di POS jika category atau parent category inactive.
- Outlet-specific category tree ditunda.

### Variant Pricing

- Setiap sellable variant memiliki base price dalam money minor unit dan currency tenant.
- Price final untuk POS dihitung server-side.
- Variant inactive tidak muncul di POS, tetapi transaksi historis yang sudah memakai variant tetap valid melalui snapshot.
- Multi-currency tidak masuk Phase 04.

### Modifier Groups And Options

- Modifier group dapat dihubungkan ke product atau sellable variant sesuai kebutuhan implementasi, tetapi POS response harus menampilkan rules yang sudah resolved pada sellable target.
- Modifier group memiliki required/optional semantics, `min_selection`, `max_selection`, dan `display_order`.
- Modifier option memiliki price delta dalam money minor unit, currency tenant, status, dan `display_order`.
- Required group harus dipenuhi saat order item dibuat atau diubah.
- Optional group mengikuti min/max rule.
- Nested modifier, conditional modifier, dan modifier yang mengurangi stock ditunda.

### Outlet Availability And Price Override

- Availability dapat diatur per outlet untuk sellable variant dan modifier option.
- Outlet price override boleh mengganti variant base price dan modifier option price delta.
- Override `null` berarti fallback ke base price.
- Override currency harus sama dengan currency tenant.
- Scheduled pricing, happy hour, promotion engine, dan multi-currency ditunda.

### Order Item Snapshot

- Sales order item harus menyimpan snapshot immutable untuk semua pilihan yang memengaruhi harga:
  - product id/name;
  - category id/name;
  - variant id/name/SKU;
  - selected modifier group/option id/name;
  - unit price;
  - modifier total;
  - line subtotal;
  - currency.
- Receipt snapshot membaca snapshot order item, bukan catalog live.
- Perubahan product, variant, modifier, availability, atau price override setelah order completed tidak boleh mengubah order/receipt historis.

### Module Boundary

- Catalog memiliki definisi product, variant, modifier, availability, dan price override.
- Sales boleh membaca resolved sellable catalog melalui application action Catalog, lalu menyimpan snapshot lokal pada order item.
- Sales tidak boleh bergantung pada tabel internal Catalog untuk menghitung ulang transaksi historis.
- Inventory, recipe, kitchen, promotion, tax/service compliance, dan accounting tetap phase terpisah.

## Consequences

- Schema Phase 04 harus additive terhadap catalog dan sales order item yang sudah ada.
- POS client menjadi lebih sederhana karena menerima resolved catalog dan mengirim selection ID yang divalidasi server.
- Order item menjadi lebih kaya, tetapi historical immutability tetap dipertahankan.
- Import/export perlu memahami variant/modifier structure, sehingga bulk import write dapat ditunda sampai dry-run baseline aman.

## Resolved Questions

- Variant adalah sellable unit yang dipilih POS, bukan sekadar label display.
- Modifier option price adalah delta terhadap variant final price.
- Category hierarchy dibatasi satu parent level untuk Phase 04.
- Combo/bundle, recipe ownership, inventory deduction, promotion, scheduled pricing, tax/service calculation, dan multi-currency ditunda.
- Existing product sederhana harus tetap bisa dijual melalui default sellable variant atau compatibility path.

## Approval

Product owner menyetujui keputusan ini pada 2026-08-01 saat Phase 04 dimulai.
