# Phase 06 Recipe Procurement Readiness

Status: **Ready**

## Scope Evidence

- Recipe header, versioned ingredients, variant mapping, costing, and sales deduction are implemented in `app/Modules/Recipe`.
- Procurement supplier, supplier item, purchase order, goods receipt, and purchase return are implemented in `app/Modules/Procurement`.
- Inventory ledger supports `sales_deduction`, `purchase_receipt`, and `purchase_return` movement types.
- Sales completion invokes recipe deduction inside the same transaction before payment and receipt finalization.

## Demo Path

1. Create supplier and supplier item mapping from tenant Procurement admin.
2. Create purchase order draft with supplier item lines.
3. Submit, approve, and mark PO ordered.
4. Record partial goods receipt and verify Inventory balance increases.
5. Record remaining goods receipt and verify PO becomes received.
6. Record purchase return and verify Inventory balance decreases.
7. Create recipe version from Inventory average cost, map active version to catalog variant, complete a sales order, and verify recipe deduction snapshot.

## Verification Commands

```bash
php artisan test tests/Feature/Recipe/RecipePhaseSixTest.php
php artisan test tests/Feature/Procurement/ProcurementPhaseSixTest.php
composer quality
```

## Out Of Scope

- Supplier payment and accounts payable.
- Landed cost allocation.
- Batch/expiry compliance.
- Complex unit conversion beyond Inventory base unit.
- Automatic stock return from sales refund or void.
