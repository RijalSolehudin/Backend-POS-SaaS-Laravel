# Phase 05: Inventory

Status: **Not Started**

## Outcome

Outlet mempunyai ledger stock yang dapat ditelusuri dan direkonsiliasi.

## Candidate Scope

- Unit dan inventory item.
- Opening balance dan stock movement ledger.
- Stock adjustment dan waste.
- Inter-outlet transfer.
- Stock card, balance, low-stock, dan valuation minimum.

## Architecture Decisions Required

- Negative stock policy.
- Unit conversion precision.
- Batch/expiry scope.
- Costing method.
- Concurrency dan locking stock.
- Adjustment/transfer approval lifecycle.

## Acceptance Criteria

- Setiap perubahan balance dapat ditelusuri ke movement dan sumbernya.
- Retry tidak membuat movement ganda.
- Transfer memiliki state dan ownership yang konsisten pada kedua outlet.
- Stock card dapat direkonsiliasi dengan balance.

## Out of Scope

- Auto-deduct recipe sampai keputusan dan capability Phase 06 siap.

