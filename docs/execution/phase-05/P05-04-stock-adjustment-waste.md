# P05-04 — Stock Adjustment and Waste

Status: **Planned**

## Outcome

Operator dapat mencatat koreksi stok dan waste dengan alasan yang jelas, approval sesuai policy, dan jejak audit yang dapat ditinjau.

## Scope

- Tambahkan adjustment increase/decrease.
- Tambahkan waste movement untuk barang rusak/hilang/expired operasional.
- Terapkan reason wajib.
- Terapkan approval lifecycle bila quantity/value melewati threshold ADR.
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

## Verification

- Feature tests adjustment.
- Feature tests waste.
- Approval lifecycle tests.
- Audit tests.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti jika adjustment membutuhkan cycle count massal, stock opname multi-step, atau compliance disposal yang belum disetujui.
