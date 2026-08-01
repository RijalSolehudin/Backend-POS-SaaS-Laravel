# Inventory Recovery Runbook

Inventory recovery Phase 05 bersifat read-only. Jangan melakukan auto-repair, edit ledger, atau update balance manual tanpa ADR baru.

## Command

```bash
php artisan inventory:recovery-check --tenant=TENANT_ULID
```

Filter tambahan:

```bash
php artisan inventory:recovery-check --tenant=TENANT_ULID --outlet=OUTLET_ULID
php artisan inventory:recovery-check --tenant=TENANT_ULID --outlet=OUTLET_ULID --item=ITEM_ULID
```

## Jika Exit Code 0

- Ledger dan balance projection konsisten untuk scope yang dicek.
- Simpan output command ke catatan operasional bila ini bagian dari readiness gate.

## Jika Exit Code 1

- Baca baris discrepancy: tenant, outlet, item, expected quantity/cost dari ledger, actual quantity/cost dari projection, dan in-transit quantity.
- Buka stock card item/outlet untuk melihat movement terakhir.
- Cek apakah ada transfer `dispatched` yang belum `received`; nilai ini muncul sebagai `In Transit` dan bukan discrepancy selama balance source sudah berkurang.
- Cek audit event Inventory untuk mutation terakhir pada item tersebut.
- Jangan edit `inventory_stock_movements`; ledger immutable.
- Jangan update `inventory_balances` manual di production.
- Buat issue recovery dengan bukti command output, stock card, audit event, dan idempotency key mutation terkait.

## Recovery Policy

Phase 05 tidak memiliki auto-repair. Koreksi bisnis yang sah dilakukan lewat adjustment/reversal yang disetujui sesuai approval policy. Koreksi teknis data corruption membutuhkan ADR dan migration/repair script terpisah.
