# Phase 04 Implementation Contract

Dokumen ini mengunci detail teknis Phase 04 Catalog and Pricing Expansion agar work package P04-01 sampai P04-08 dapat dipahami tanpa membuat ulang keputusan product, variant, modifier, outlet override, atau snapshot.

## Module Ownership

- Catalog memiliki category, product, variant, modifier group, modifier option, outlet availability, dan outlet price override.
- Sales boleh membaca resolved catalog melalui Catalog application action.
- Sales menyimpan snapshot order item sendiri dan tidak menghitung transaksi historis dari catalog live.
- Inventory, recipe, promotion, tax/service, dan accounting tetap di luar Phase 04.

## Tables

Baseline Phase 04 memakai:

- `catalog_categories`
- `catalog_products`
- `catalog_product_outlet_availabilities`
- `catalog_product_variants`
- `catalog_modifier_groups`
- `catalog_modifier_options`
- `catalog_variant_outlet_availabilities`
- `catalog_modifier_option_outlet_overrides`
- additive columns pada `sales_order_items` untuk variant/modifier snapshot

Semua table Catalog wajib tenant-scoped. Availability/override wajib outlet-scoped.

## Product And Category

- Product adalah menu item konseptual.
- Variant adalah sellable unit yang dipilih POS.
- Category hierarchy dibatasi parent-child sederhana.
- Category dan product memiliki `display_order`.
- Product tidak tampil di POS jika product, category, atau parent category inactive.
- Outlet-specific category tree tidak masuk Phase 04.

## Variant

- Variant punya `name`, `sku`, `price_minor`, `currency`, `is_default`, `display_order`, dan `status`.
- SKU variant unik per tenant.
- Product sederhana tetap valid melalui compatibility/default variant path.
- POS catalog hanya menampilkan variant aktif dan available pada outlet.
- Outlet override boleh mengganti price variant; `null` berarti fallback ke base price.

## Modifier

- Modifier group dapat melekat ke product atau variant.
- Modifier group memiliki `required`, `min_selection`, `max_selection`, `display_order`, dan `status`.
- Modifier option memiliki `price_delta_minor`, `currency`, `display_order`, dan `status`.
- Required group wajib terpenuhi saat order item dibuat.
- Optional group tetap mematuhi min/max.
- Outlet override option boleh mengganti availability dan price delta; `null` berarti fallback ke base delta.
- Nested/conditional modifier dan modifier stock deduction tidak masuk Phase 04.

## POS Catalog Resolution

Resolved POS catalog harus:

- tenant/outlet scoped;
- hanya berisi active/available product, variant, group, dan option;
- mengembalikan price efektif server-side;
- mengurutkan parent category, category, product, variant, group, dan option secara deterministik;
- tidak memaksa POS client menghitung price dari struktur internal.

## Sales Snapshot Integration

Order item selection menerima `variant_id` dan modifier option IDs.

Saat add/update item:

- server memvalidasi variant tersedia pada outlet;
- server memvalidasi modifier group required/min/max;
- unit price final = variant effective price + selected modifier delta;
- order item menyimpan snapshot product, category, variant, selected modifier group/option, unit price, modifier total, line subtotal, dan currency;
- receipt snapshot membaca order item snapshot, bukan catalog live.

Perubahan catalog setelah order completed tidak boleh mengubah order/receipt historis.

## Import Export

- `catalog:export {tenant} --pretty` read-only dan menghasilkan JSON tenant catalog.
- `catalog:import-dry-run {path}` hanya validasi file dan tidak menulis database.
- Import write, spreadsheet sync, image/media pipeline, dan approval catalog change tidak masuk Phase 04.

## Testing Baseline

Setiap work package Phase 04 wajib memiliki:

- admin/workflow test untuk mutation Catalog yang dibuat;
- POS catalog response test;
- tenant/outlet isolation test;
- Sales snapshot regression bila menyentuh order item;
- receipt immutability test bila menyentuh snapshot;
- `composer quality`.

Jalankan `npm run build` bila mengubah frontend asset.

## Stop Rule

Berhenti jika implementasi membutuhkan inventory deduction, recipe costing, combo/bundle stock semantics, multi-currency, scheduled pricing, promotion engine, accounting export, payment/tax compliance detail, atau catalog image/media management.
