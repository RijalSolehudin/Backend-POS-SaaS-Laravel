# ADR-041: Recipe and Procurement MVP Policy

- Status: Accepted
- Date: 2026-08-01

## Context

Phase 06 menghubungkan Catalog, Sales, dan Inventory. Tujuannya adalah recipe costing, stock deduction dari penjualan, supplier, purchase order, dan goods receipt tanpa merusak ledger Inventory Phase 05.

## Decision

### Module Boundary

- Module `Recipe` memiliki recipe, recipe version, recipe ingredient, yield, dan product/variant mapping.
- Module `Procurement` memiliki supplier, supplier item, purchase order, purchase order approval, goods receipt, dan purchase return minimum.
- Inventory tetap pemilik stock movement ledger dan balance.
- Sales tidak menulis Inventory langsung; Sales memicu deduction melalui application boundary Recipe/Inventory setelah order completed.

### Recipe Versioning

- Recipe wajib versioned.
- Recipe version memiliki status `draft`, `active`, `archived`.
- Hanya satu active recipe version per sellable variant dalam tenant.
- Order completed menyimpan snapshot recipe version id dan ingredient usage/cost untuk deduction audit.
- Perubahan recipe tidak mengubah historical deduction/cost.

### Yield And Units

- Ingredient quantity memakai base unit Inventory item.
- Recipe output memakai sellable quantity dan optional yield percent.
- Conversion kompleks lintas packaging tetap ditunda; Phase 06 memakai base unit Inventory dari Phase 05.
- Quantity precision mengikuti Inventory: decimal fixed precision 3 digit.

### Sales Deduction

- Deduction terjadi saat order completed berhasil.
- Deduction idempotent per sales order item.
- Jika stock tidak cukup, complete order ditolak sebelum payment/receipt final.
- Deduction movement type Inventory: `sales_deduction`.
- Void/refund tidak otomatis mengembalikan stock; stock return karena operasional dicatat lewat adjustment/reversal policy terpisah.

### Recipe Costing

- Recipe cost memakai current moving average cost dari Inventory saat deduction.
- Cost snapshot disimpan pada deduction record.
- Historical cost tidak dihitung ulang saat average cost berubah.

### Procurement

- Supplier tenant-scoped.
- Purchase order lifecycle: `draft`, `submitted`, `approved`, `ordered`, `partially_received`, `received`, `cancelled`.
- Purchase order membutuhkan approval saat submitted menuju approved.
- Goods receipt menghasilkan Inventory inbound movement.
- Partial receipt diizinkan.
- Over-receipt ditolak.
- Purchase return minimum hanya reversal outbound terhadap received quantity dan membutuhkan reason.

## Consequences

- Sales complete menjadi lebih ketat karena bisa gagal saat stock tidak cukup.
- Recipe versioning menambah tabel, tetapi mencegah historical cost berubah diam-diam.
- Procurement tetap sederhana dan belum masuk accounting payable.

## Deferred Scope

- Supplier payment/accounts payable.
- Landed cost.
- Multi-currency purchase.
- Batch/expiry.
- Complex unit conversion dan packaging hierarchy.
- Auto stock return dari refund.

## Approval

Product owner menyetujui keputusan ini sebagai pre-accepted Phase 06 implementation policy pada 2026-08-01.
