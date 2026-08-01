# P05-03 — Opening Balance and Stock Ledger

Status: **Planned**

## Outcome

Setiap perubahan stok outlet dicatat sebagai ledger movement yang immutable dan dapat direkonsiliasi.

## Scope

- Tambahkan stock movement ledger tenant/outlet scoped.
- Tambahkan opening balance sebagai movement sumber awal.
- Tambahkan current balance projection atau query read model sesuai ADR.
- Tambahkan idempotency key untuk mutation stok.
- Terapkan database transaction dan locking untuk mutation yang memengaruhi balance.
- Pastikan movement tidak dapat diedit setelah dicatat.

## Out of Scope

- Adjustment/waste operational flow.
- Transfer antar outlet.
- Sales auto-deduction.
- Batch/expiry bila ditunda oleh ADR.

## Dependencies

- P05-02 selesai.
- ADR negative stock dan locking policy selesai.

## Acceptance Criteria

- Opening balance menghasilkan movement dan balance yang cocok.
- Retry dengan idempotency key sama tidak membuat movement ganda.
- Retry dengan payload berbeda ditolak sebagai conflict.
- Negative stock mengikuti policy ADR.
- Stock movement menyimpan actor, source type, source id, quantity, unit, cost, dan occurred_at.

## Verification

- Feature tests opening balance.
- Idempotency tests.
- Concurrent mutation/locking tests bila feasible.
- `composer quality` lulus.

## Architecture Stop Rule

Berhenti jika ledger harus mendukung retroactive costing, multi-currency valuation, atau source document external yang belum disetujui.
