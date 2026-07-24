# Phase 06: Recipe and Procurement

Status: **Not Started**

## Outcome

Penjualan dapat dihubungkan ke pemakaian bahan dan proses pembelian dapat ditelusuri hingga stock receipt.

## Candidate Scope

- Recipe, sub-recipe, yield, dan recipe costing.
- Supplier dan supplier item.
- Purchase order approval.
- Partial/full goods receipt.
- Idempotent sales-to-stock deduction.
- COGS, food cost, purchase, dan waste reporting.

## Architecture Decisions Required

- Recipe module ownership.
- Stock deduction timing.
- Recipe version/snapshot behavior.
- Costing and yield precision.
- PO approval, partial receipt, over-receipt, dan return policy.

## Acceptance Criteria

- Recipe cost dapat dijelaskan dari ingredient cost dan conversion.
- Sales deduction menghasilkan movement tepat satu kali.
- Recipe berubah tidak mengubah historical cost tanpa kebijakan eksplisit.
- Goods receipt dan inventory movement dapat direkonsiliasi.

