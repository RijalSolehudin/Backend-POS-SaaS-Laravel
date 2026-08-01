# P04-06 — Order Item Snapshot Integration

Status: **Done**

## Outcome

Sales order item dapat merekam variant dan modifier selection sebagai immutable snapshot sehingga perubahan catalog tidak mengubah transaksi historis.

## Scope

- Perluas add/update order item agar menerima sellable variant dan modifier selections.
- Validasi required/optional modifier rules saat order item dibuat atau diubah.
- Hitung line subtotal dari variant base price plus modifier price deltas.
- Simpan snapshot nama, SKU, category, variant, modifier, quantity, unit price, modifier totals, dan line subtotal.
- Pastikan receipt snapshot dan shift/daily summary tetap konsisten.

## Out of Scope

- Partial item refund.
- Kitchen ticket.
- Inventory deduction.
- Discount/tax/service calculation.
- Order item split/merge.

## Dependencies

- P04-03 selesai.
- P04-04 selesai.
- P04-05 selesai.

## Acceptance Criteria

- Order item dengan variant/modifier menghasilkan total yang deterministik.
- Modifier required/min/max divalidasi.
- Receipt menampilkan snapshot pilihan yang memengaruhi harga.
- Perubahan catalog setelah order completed tidak mengubah order/receipt lama.
- Retry/idempotency order item tetap tidak membuat duplicate selection.

## Verification

- Feature tests untuk add/update/remove item dengan variant/modifier.
- Regression tests untuk product sederhana Phase 02.
- Receipt snapshot tests diperluas.
- `composer quality` lulus.

## Delivered

- Sales order item menyimpan snapshot variant dan modifier selection secara additive.
- POS add item menerima `variant_id` dan `modifiers` opsional.
- Harga unit dihitung dari resolved variant price plus selected modifier price deltas.
- Required/min/max modifier rules divalidasi server-side.
- Receipt snapshot membaca variant/modifier snapshot dari order item, bukan catalog live.
- Product sederhana lama tetap kompatibel tanpa payload variant/modifier.

## Evidence

- Laravel Boost ApplicationInfo digunakan untuk memverifikasi stack Laravel aktif.
- `php artisan test tests/Feature/Sales/DraftOrderItemManagementTest.php`
- `php artisan test tests/Feature/Catalog/MinimumCatalogTest.php tests/Feature/Sales/DraftOrderItemManagementTest.php tests/Feature/Sales/ReceiptSnapshotTest.php`
- `composer quality`
- `npm run build`

## Architecture Stop Rule

Berhenti jika calculation membutuhkan tax, service charge, discount allocation, bundle allocation, atau stock semantics.
