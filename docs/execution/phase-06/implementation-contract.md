# Phase 06 Implementation Contract

Dokumen ini mengunci detail teknis Phase 06 agar implementer tidak membuat keputusan recipe/procurement baru.

## Module Structure

Gunakan module `Recipe` dan `Procurement` dengan struktur standar `Application`, `Domain`, `Infrastructure`, dan `Presentation`.

## Tables

| Table | Owner | Purpose |
|---|---|---|
| `recipe_recipes` | P06-02 | Recipe header tenant-scoped |
| `recipe_versions` | P06-03 | Versioned recipe snapshot |
| `recipe_ingredients` | P06-03 | Ingredient lines per version |
| `recipe_variant_mappings` | P06-03 | Active recipe version per catalog variant |
| `recipe_sales_deductions` | P06-04 | Deduction idempotency/audit per order item |
| `procurement_suppliers` | P06-05 | Supplier tenant-scoped |
| `procurement_supplier_items` | P06-05 | Supplier item to inventory item mapping |
| `procurement_purchase_orders` | P06-06 | PO header |
| `procurement_purchase_order_lines` | P06-06 | PO line |
| `procurement_goods_receipts` | P06-07 | Goods receipt header |
| `procurement_goods_receipt_lines` | P06-07 | Received quantity |
| `procurement_purchase_returns` | P06-07 | Return header |
| `procurement_purchase_return_lines` | P06-07 | Returned quantity |
| `procurement_idempotency_records` | P06-06/P06-07 | Idempotency audit for PO lifecycle, receipt, and return |

## Enums

| Enum | Values |
|---|---|
| `RecipeVersionStatus` | `draft`, `active`, `archived` |
| `PurchaseOrderStatus` | `draft`, `submitted`, `approved`, `ordered`, `partially_received`, `received`, `cancelled` |
| `GoodsReceiptStatus` | `recorded`, `voided` |
| `PurchaseReturnStatus` | `recorded`, `voided` |

## Recipe Rules

- Satu catalog variant hanya boleh punya satu active recipe version.
- Recipe ingredient memakai Inventory item base unit.
- Quantity decimal fixed precision `decimal(15, 3)`.
- Recipe cost dihitung dari current Inventory average cost saat costing/deduction.
- Recipe version archived tidak boleh dipakai untuk order baru kecuali historical snapshot.

## Sales Deduction

- Trigger deduction setelah Sales order completed, sebelum response complete dikembalikan.
- Deduction harus dalam transaction yang sama atau punya compensating recovery check; MVP memilih transaction yang sama.
- Satu `sales_order_item_id` hanya boleh punya satu successful deduction.
- Jika recipe mapping missing, variant dianggap belum dikelola Recipe dan order tetap boleh complete tanpa deduction. Mapping dengan `requires_recipe=true` wajib menunjuk active recipe version sebelum completion.
- Inventory insufficient stock membuat complete order gagal dengan business error.

## Procurement Rules

- PO approval memakai approval pattern Phase 03.
- PO submitted tidak boleh diedit kecuali dikembalikan ke draft melalui action eksplisit; MVP tidak menyediakan return-to-draft.
- Partial receipt boleh, over-receipt ditolak.
- Goods receipt membuat Inventory inbound movement type `purchase_receipt`.
- Purchase return membuat Inventory outbound movement type `purchase_return`.

## Error Codes

- `RECIPE_NOT_FOUND`
- `RECIPE_VERSION_NOT_FOUND`
- `RECIPE_VERSION_INVALID_STATE`
- `RECIPE_MAPPING_REQUIRED`
- `RECIPE_INSUFFICIENT_STOCK`
- `RECIPE_DEDUCTION_ALREADY_RECORDED`
- `PROCUREMENT_SUPPLIER_NOT_FOUND`
- `PROCUREMENT_PO_NOT_FOUND`
- `PROCUREMENT_PO_INVALID_STATE`
- `PROCUREMENT_PO_APPROVAL_REQUIRED`
- `PROCUREMENT_RECEIPT_OVER_RECEIVED`
- `PROCUREMENT_RETURN_QUANTITY_INVALID`

## Testing Baseline

- Recipe version activation and snapshot tests.
- Costing tests against Inventory average cost.
- Sales completion deduction idempotency tests.
- PO approval lifecycle tests.
- Goods receipt and return reconciliation tests.
- `composer quality`.
