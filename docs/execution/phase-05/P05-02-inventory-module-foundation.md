# P05-02 — Inventory Module Foundation

Status: **Ready**

## Outcome

Backend memiliki module Inventory yang terpisah dari Catalog dan Sales, dengan model dasar item, unit, outlet scope, authorization, dan audit boundary.

## Scope

- Tambahkan struktur module Inventory mengikuti pola modular Laravel project.
- Tambahkan unit inventory dan inventory item tenant-scoped.
- Tambahkan outlet settings untuk minimum stock dan active/inactive per outlet.
- Tambahkan web admin baseline untuk create/update/deactivate item dan unit.
- Pastikan permission dan tenant isolation mengikuti pola module lain.
- Tambahkan audit event untuk perubahan master inventory.

## Out of Scope

- Stock mutation ledger.
- Transfer antar outlet.
- Auto-deduct dari Sales.
- Recipe/BOM.
- Supplier dan purchase order.
- Flutter/POS API inventory.

## Dependencies

- P05-01 selesai.
- Platform identity/RBAC tersedia.
- Tenancy/outlet module tersedia.

## Acceptance Criteria

- Inventory item dan unit tenant-scoped.
- Inactive inventory item tidak bisa dipakai untuk mutation baru.
- Unit precision mengikuti ADR.
- Cross-tenant reference ditolak.
- Admin/developer dapat melihat daftar item dan unit minimum.

## Implementation Contract

- Ikuti [Phase 05 Implementation Contract](implementation-contract.md).
- Buat table `inventory_units`, `inventory_items`, `inventory_item_outlet_settings`, dan `inventory_audit_events`.
- Buat enum `InventoryStatus` dengan value `active` dan `inactive`.
- Buat action: `CreateInventoryUnit`, `UpdateInventoryUnit`, `ChangeInventoryUnitStatus`, `CreateInventoryItem`, `UpdateInventoryItem`, `ChangeInventoryItemStatus`, dan `SetInventoryItemOutletSettings`.
- Unit field minimum: `tenant_id`, `name`, `symbol`, `precision`, `status`.
- Item field minimum: `tenant_id`, `unit_id`, `name`, `sku`, `status`.
- Outlet settings field minimum: `tenant_id`, `outlet_id`, `item_id`, `status`, `low_stock_threshold_quantity`.
- SKU item unik per tenant.
- Presentation P05-02 adalah web admin/back-office; jangan menambah Flutter/POS API inventory.
- Jangan buat stock movement, balance, transfer, atau Sales auto-deduction pada P05-02.

## Verification

- Feature tests untuk create/update/deactivate unit dan item.
- Tenant isolation tests.
- Authorization tests.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti jika foundation membutuhkan barcode standard, packaging hierarchy kompleks, atau supplier ownership yang belum diputuskan.
