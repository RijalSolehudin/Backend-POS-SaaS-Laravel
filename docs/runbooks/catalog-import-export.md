# Catalog Import Export

Runbook ini berlaku untuk baseline Phase 04. Export bersifat read-only, sedangkan import saat ini hanya dry-run validation dan tidak menulis database.

## Export

Gunakan command berikut untuk review catalog tenant:

```bash
php artisan catalog:export {tenant_id} --pretty
```

Output JSON berisi:

- `categories`
- `products`
- `product_outlet_availabilities`
- `variants`
- `variant_outlet_availabilities`
- `modifier_groups`
- `modifier_options`
- `modifier_option_outlet_overrides`

Gunakan output ini untuk review menu sebelum pilot, membandingkan outlet override, dan membuat fixture import manual.

## Import Dry Run

Validasi file import tanpa menulis database:

```bash
php artisan catalog:import-dry-run storage/app/catalog-import.json
```

Required sections:

- `categories`
- `products`

Optional sections:

- `product_outlet_availabilities`
- `variants`
- `variant_outlet_availabilities`
- `modifier_groups`
- `modifier_options`
- `modifier_option_outlet_overrides`

Dry run sukses jika file valid JSON object, section wajib tersedia sebagai array, dan section opsional yang muncul juga berupa array.

## Write Policy

Bulk import write belum tersedia pada P04-07. Tambahkan write import hanya setelah format disetujui dari hasil dry-run dan product owner menerima policy partial failure, audit reason, dan rollback.
