# P05-08 — Inventory Readiness

Status: **Planned**

## Outcome

Phase 05 siap dinyatakan selesai berdasarkan evidence item/unit, ledger, adjustment/waste, transfer, stock card, dan reconciliation.

## Scope

- End-to-end readiness matrix untuk opening balance, adjustment, waste, transfer, stock card, dan low stock.
- Reconciliation checklist antara ledger dan balance.
- Historical immutability checklist untuk movement lama.
- Operational checklist untuk approval, idempotency, audit, dan recovery.
- Final status update untuk roadmap dan execution docs.

## Out of Scope

- Recipe auto-deduction.
- Procurement.
- Accounting export.
- Advanced costing.
- Batch/expiry bila tidak disetujui Phase 05.

## Dependencies

- P05-02 selesai.
- P05-03 selesai.
- P05-04 selesai.
- P05-05 selesai.
- P05-06 selesai.
- P05-07 selesai.

## Acceptance Criteria

- Inventory item/unit dapat dikelola tenant-scoped.
- Opening balance dan semua movement dapat ditelusuri.
- Current balance cocok dengan ledger.
- Transfer antar outlet memiliki state dan ownership yang jelas.
- Reconciliation/recovery check tersedia.
- Regression Catalog/Sales tidak rusak.
- Phase 05 roadmap status diperbarui sesuai evidence.

## Implementation Contract

- Ikuti [Phase 05 Implementation Contract](implementation-contract.md).
- Buat runbook Phase 05 readiness yang mencakup demo opening balance, adjustment increase/decrease, waste, transfer dispatch/receive, stock card, low stock, in transit, dan recovery check.
- Pastikan roadmap Phase 05 hanya berubah ke `Done` setelah semua P05-02 sampai P05-07 selesai.
- Pastikan readiness mencatat batasan: tidak ada Sales auto-deduction, recipe, batch/expiry, FIFO, procurement, atau accounting journal.
- Pastikan evidence berisi Inventory suite, Sales/Catalog regression critical path, `composer quality`, dan `npm run build` bila frontend berubah.

## Verification

- Inventory suite lulus.
- Regression Catalog/Sales critical path lulus.
- `composer quality` lulus.
- `npm run build` lulus bila ada perubahan frontend.
- Manual/demo checklist documented untuk product owner acceptance.

## Architecture Stop Rule

Berhenti jika readiness membutuhkan recipe deduction, purchase order, accounting journal, atau production stock compliance yang belum disetujui.
