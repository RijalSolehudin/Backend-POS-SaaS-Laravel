# P04-02 — Category Hierarchy and Display Ordering

Status: **Done**

## Outcome

Tenant admin dapat menyusun category catalog dengan hierarchy dan urutan tampil yang stabil untuk POS.

## Scope

- Tambahkan parent category untuk hierarchy sederhana.
- Tambahkan display order pada category.
- Pastikan API catalog POS mengurutkan category dan product secara deterministik.
- Pastikan category inactive/parent inactive tidak bocor ke POS.
- Tambahkan admin workflow untuk mengubah parent/order/status.

## Out of Scope

- Drag-and-drop UI kompleks.
- Multi-level unlimited navigation bila tidak disetujui di P04-01.
- Menu per daypart atau scheduled availability.

## Dependencies

- P04-01 selesai.
- Catalog MVP P01-10.

## Acceptance Criteria

- Category dapat memiliki parent sesuai policy ADR.
- Urutan category dan product konsisten di API POS.
- Tenant isolation tetap terjaga.
- Inactive parent/category tidak menampilkan product aktif di POS.
- Existing product tanpa parent tetap valid melalui migration additive.

## Verification

- Feature tests untuk admin category hierarchy dan POS catalog ordering.
- `composer quality` lulus.

## Delivered

- Category mendukung `parent_id` dan `display_order`.
- Product mendukung `display_order`.
- Tenant admin dapat mengisi parent category dan display order dari halaman catalog.
- POS catalog mengurutkan parent/category/product secara deterministik.
- POS catalog menyembunyikan product jika category atau parent category inactive.

## Evidence

- Laravel Boost ApplicationInfo digunakan untuk memverifikasi stack Laravel aktif.
- `php artisan test tests/Feature/Catalog/MinimumCatalogTest.php`
- `composer quality`

## Architecture Stop Rule

Berhenti jika hierarchy membutuhkan menu versioning, scheduled menu, atau outlet-specific category tree.
