# P05-02 — Inventory Module Foundation

Status: **Ready**

## Outcome

Backend memiliki module Inventory yang terpisah dari Catalog dan Sales, dengan model dasar item, unit, outlet scope, authorization, dan audit boundary.

## Scope

- Tambahkan struktur module Inventory mengikuti pola modular Laravel project.
- Tambahkan unit inventory dan inventory item tenant-scoped.
- Tambahkan mapping item ke outlet jika diperlukan oleh ADR.
- Tambahkan admin/API baseline untuk create/update/deactivate item dan unit.
- Pastikan permission dan tenant isolation mengikuti pola module lain.
- Tambahkan audit event untuk perubahan master inventory.

## Out of Scope

- Stock mutation ledger.
- Transfer antar outlet.
- Auto-deduct dari Sales.
- Recipe/BOM.
- Supplier dan purchase order.

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

## Verification

- Feature tests untuk create/update/deactivate unit dan item.
- Tenant isolation tests.
- Authorization tests.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti jika foundation membutuhkan barcode standard, packaging hierarchy kompleks, atau supplier ownership yang belum diputuskan.
