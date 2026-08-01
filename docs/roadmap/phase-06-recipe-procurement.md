# Phase 06: Recipe and Procurement

Status: **Done**

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

- Accepted decision: [ADR-041 Recipe and Procurement MVP Policy](../architecture/decisions/041-recipe-procurement-mvp-policy.md).
- Recipe dan Procurement menjadi module terpisah.
- Stock deduction terjadi saat order completed dan idempotent per order item.
- Recipe version/snapshot wajib untuk historical cost.
- Partial goods receipt diizinkan dan over-receipt ditolak.
- Refund/void tidak otomatis mengembalikan stock.

## Acceptance Criteria

- Recipe cost dapat dijelaskan dari ingredient cost dan conversion.
- Sales deduction menghasilkan movement tepat satu kali.
- Recipe berubah tidak mengubah historical cost tanpa kebijakan eksplisit.
- Goods receipt dan inventory movement dapat direkonsiliasi.
