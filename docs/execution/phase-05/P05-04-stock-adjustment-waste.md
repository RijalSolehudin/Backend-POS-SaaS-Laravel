# P05-04 — Stock Adjustment and Waste

Status: **Done**

## Outcome

Operator dapat mencatat koreksi stok dan waste dengan alasan yang jelas, approval sesuai policy, dan jejak audit yang dapat ditinjau.

## Scope

- Tambahkan adjustment increase/decrease.
- Tambahkan waste movement untuk barang rusak/hilang/expired operasional.
- Terapkan reason wajib.
- Terapkan approval lifecycle bila quantity melewati threshold ADR.
- Pastikan adjustment dan waste menghasilkan ledger movement immutable.
- Tambahkan audit event untuk request, approval, rejection, dan completion.

## Out of Scope

- Transfer antar outlet.
- Stock opname batch besar.
- Disposal compliance.
- Recipe yield/waste calculation.

## Dependencies

- P05-03 selesai.
- Sensitive action approval pattern Phase 03 tersedia.

## Acceptance Criteria

- Adjustment increase/decrease mengubah balance sesuai ledger.
- Waste mengurangi balance sesuai policy negative stock.
- Reason wajib untuk semua adjustment/waste.
- Approval required ketika policy ADR terpenuhi.
- Approval tidak bisa dipakai ulang atau lintas tenant/outlet.

## Implementation Contract

- Ikuti [Phase 05 Implementation Contract](implementation-contract.md).
- Buat action `RecordStockAdjustment` dan `RecordWaste`.
- Adjustment increase memakai movement type `adjustment_increase`.
- Adjustment decrease memakai movement type `adjustment_decrease`.
- Waste memakai movement type `waste`.
- Reason wajib dan disimpan pada movement dan audit event.
- Adjustment decrease dan waste dengan quantity positif wajib approval secara default karena threshold `0.000`.
- Approval fingerprint wajib mencakup outlet, item, movement type, quantity, reason, dan idempotency/action target.
- Adjustment increase tidak membutuhkan approval pada MVP.
- Outbound mutation wajib menolak balance minus dengan `INVENTORY_INSUFFICIENT_STOCK`.

## Verification

- Feature tests adjustment.
- Feature tests waste.
- Approval lifecycle tests.
- Audit tests.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti jika adjustment membutuhkan cycle count massal, stock opname multi-step, atau compliance disposal yang belum disetujui.
