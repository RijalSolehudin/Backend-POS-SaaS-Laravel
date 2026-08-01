# Phase 05 Inventory Readiness

Phase 05 siap diterima bila seluruh evidence di bawah ini hijau.

## Demo Checklist

- Kelola unit dan inventory item pada tenant yang benar.
- Set outlet setting dan low-stock threshold.
- Record opening balance satu kali per item/outlet.
- Record adjustment increase tanpa approval.
- Request/approve/consume approval untuk adjustment decrease.
- Request/approve/consume approval untuk waste.
- Buat transfer antar outlet dalam tenant yang sama.
- Request dan approve transfer.
- Dispatch transfer dan pastikan source balance berkurang.
- Lihat `in_transit_quantity` pada source outlet selama transfer belum received.
- Receive transfer dan pastikan destination balance bertambah.
- Buka stock card item/outlet dan cek movement opening, adjustment, waste, transfer out, dan transfer in.
- Jalankan recovery check:

```bash
php artisan inventory:recovery-check --tenant=TENANT_ULID
```

## Automated Evidence

- `php artisan test tests/Feature/Inventory/InventoryModuleFoundationTest.php`
- `php artisan test tests/Feature/Catalog/MinimumCatalogTest.php`
- `php artisan test tests/Feature/Sales/PosCoreReadinessTest.php`
- `composer quality`
- `npm run build`

## Boundaries

Phase 05 tidak mencakup:

- Sales auto-deduction;
- recipe/BOM;
- procurement atau purchase order;
- batch/expiry traceability;
- FIFO atau landed cost;
- accounting journal;
- unit conversion kompleks.

Kebutuhan di atas masuk Phase 06+ atau membutuhkan ADR baru.
